#!/usr/bin/env python3
"""
Import somatic variants from GDC MAF files into Aurora's genomic_variants table.

Reads .maf.gz files from the GDC download directory, maps TCGA/CPTAC barcodes
to Aurora patients via patient_identifiers, and inserts real somatic mutations.

Usage:
    python3 import_maf_variants.py TCGA-PRAD              # Single project
    python3 import_maf_variants.py all                     # All projects
    python3 import_maf_variants.py TCGA-PRAD --dry-run     # Preview only
    python3 import_maf_variants.py TCGA-PRAD --limit 50    # Max variants per patient
"""

import argparse
import gzip
import os
import sys
from pathlib import Path

DB_NAME = os.environ.get("DB_NAME", "aurora")
DB_USER = os.environ.get("DB_USER", "smudoshi")
GENOMICS_ROOT = os.environ.get("GENOMICS_ROOT", "/media/smudoshi/DATA/TCIA-downloads/genomics")

# Variant classifications considered clinically significant
SIGNIFICANT_CLASSIFICATIONS = {
    "Missense_Mutation",
    "Nonsense_Mutation",
    "Frame_Shift_Del",
    "Frame_Shift_Ins",
    "In_Frame_Del",
    "In_Frame_Ins",
    "Splice_Site",
    "Splice_Region",
    "Translation_Start_Site",
    "Nonstop_Mutation",
}

# Map MAF Variant_Classification to our variant_type
VARIANT_TYPE_MAP = {
    "Missense_Mutation": "SNV",
    "Nonsense_Mutation": "SNV",
    "Splice_Site": "SNV",
    "Splice_Region": "SNV",
    "Translation_Start_Site": "SNV",
    "Nonstop_Mutation": "SNV",
    "Frame_Shift_Del": "indel",
    "Frame_Shift_Ins": "indel",
    "In_Frame_Del": "indel",
    "In_Frame_Ins": "indel",
}

# Known cancer driver genes for clinical significance annotation
KNOWN_DRIVERS = {
    # Prostate
    "TMPRSS2", "ERG", "PTEN", "TP53", "SPOP", "FOXA1", "AR", "RB1",
    "BRCA1", "BRCA2", "ATM", "CDK12", "MYC", "PIK3CA", "AKT1",
    # Pan-cancer
    "KRAS", "NRAS", "BRAF", "EGFR", "ALK", "ROS1", "MET", "RET",
    "VHL", "PBRM1", "SETD2", "BAP1", "CTNNB1", "TERT", "IDH1", "IDH2",
    "SMAD4", "CDKN2A", "APC", "FBXW7", "KEAP1", "STK11", "NF1", "NF2",
    "MTOR", "TSC1", "TSC2", "HER2", "ERBB2", "FGFR1", "FGFR2", "FGFR3",
    "KIT", "PDGFRA", "JAK2", "MPL", "CALR", "NPM1", "FLT3", "DNMT3A",
    "PIK3R1", "ARID1A", "KMT2D", "KMT2C", "NOTCH1", "NOTCH2",
}


def connect_db():
    import psycopg2
    return psycopg2.connect(dbname=DB_NAME, user=DB_USER)


def get_patient_mapping(conn) -> dict[str, int]:
    """Map TCGA barcodes (first 12 chars, e.g., TCGA-G9-6498) to Aurora patient IDs."""
    cur = conn.cursor()
    cur.execute("""
        SELECT pi.identifier_value, pi.patient_id
        FROM clinical.patient_identifiers pi
        WHERE pi.identifier_type IN ('tcga_barcode', 'cptac_barcode', 'tcia_subject')
    """)
    mapping = {}
    for row in cur.fetchall():
        mapping[row[0]] = row[1]
    cur.close()
    return mapping


def extract_tcga_patient_id(barcode: str) -> str:
    """Extract patient ID from TCGA barcode: TCGA-G9-6498-01A-... → TCGA-G9-6498"""
    parts = barcode.split("-")
    if len(parts) >= 3 and parts[0] == "TCGA":
        return "-".join(parts[:3])
    return barcode


def determine_significance(gene: str, classification: str, sift: str, polyphen: str) -> str:
    """Determine clinical significance based on gene and predictions."""
    if gene in KNOWN_DRIVERS:
        if classification in ("Nonsense_Mutation", "Frame_Shift_Del", "Frame_Shift_Ins"):
            return "pathogenic"
        if classification == "Missense_Mutation":
            if "deleterious" in sift.lower() or "damaging" in polyphen.lower():
                return "likely_pathogenic"
            return "VUS"
        return "likely_pathogenic"

    if classification in ("Nonsense_Mutation", "Frame_Shift_Del", "Frame_Shift_Ins"):
        return "likely_pathogenic"

    return "VUS"


def parse_maf_file(filepath: Path, patient_mapping: dict, limit: int = 0) -> list[dict]:
    """Parse a single .maf.gz file and return variant records."""
    variants = []

    opener = gzip.open if str(filepath).endswith(".gz") else open

    with opener(filepath, "rt") as f:
        headers = None
        for line in f:
            if line.startswith("#"):
                continue
            if headers is None:
                headers = line.strip().split("\t")
                continue

            fields = line.strip().split("\t")
            if len(fields) < len(headers):
                continue

            row = dict(zip(headers, fields))

            # Filter to significant variant types
            classification = row.get("Variant_Classification", "")
            if classification not in SIGNIFICANT_CLASSIFICATIONS:
                continue

            # Map to Aurora patient
            barcode = row.get("Tumor_Sample_Barcode", "")
            patient_id_key = extract_tcga_patient_id(barcode)
            aurora_patient_id = patient_mapping.get(patient_id_key)
            if not aurora_patient_id:
                continue

            # Parse allele frequency
            t_depth = int(row.get("t_depth", "0") or "0")
            t_alt = int(row.get("t_alt_count", "0") or "0")
            af = round(t_alt / t_depth, 4) if t_depth > 0 else None

            # Parse variant details
            gene = row.get("Hugo_Symbol", "")
            hgvsp = row.get("HGVSp_Short", "")
            variant_str = hgvsp.replace("p.", "") if hgvsp else classification

            chrom = row.get("Chromosome", "").replace("chr", "")
            position = int(row.get("Start_Position", "0") or "0")
            ref = row.get("Reference_Allele", "")
            alt = row.get("Tumor_Seq_Allele2", "")

            sift = row.get("SIFT", "") or ""
            polyphen = row.get("PolyPhen", "") or ""

            significance = determine_significance(gene, classification, sift, polyphen)

            variants.append({
                "patient_id": aurora_patient_id,
                "gene": gene,
                "variant": variant_str,
                "variant_type": VARIANT_TYPE_MAP.get(classification, "SNV"),
                "chromosome": chrom,
                "position": position,
                "ref_allele": ref if len(ref) <= 20 else ref[:20],
                "alt_allele": alt if len(alt) <= 20 else alt[:20],
                "zygosity": "heterozygous",
                "allele_frequency": af,
                "clinical_significance": significance,
                "actionability": None,
            })

            if limit and len(variants) >= limit:
                break

    return variants


def import_variants(variants: list[dict], conn, dry_run: bool = False) -> dict:
    """Insert variants into genomic_variants table."""
    stats = {"inserted": 0, "skipped_duplicate": 0}
    cur = conn.cursor()

    for v in variants:
        # Check for duplicate (same patient + gene + chromosome + position)
        cur.execute("""
            SELECT id FROM clinical.genomic_variants
            WHERE patient_id = %s AND gene = %s AND chromosome = %s AND position = %s
              AND COALESCE(ref_allele, '') = %s AND COALESCE(alt_allele, '') = %s
        """, (v["patient_id"], v["gene"], v["chromosome"], v["position"],
              v["ref_allele"] or "", v["alt_allele"] or ""))

        if cur.fetchone():
            stats["skipped_duplicate"] += 1
            continue

        if dry_run:
            print(f"  DRY RUN: {v['gene']} {v['variant']} chr{v['chromosome']}:{v['position']} "
                  f"AF={v['allele_frequency']} [{v['clinical_significance']}]")
            stats["inserted"] += 1
            continue

        cur.execute("""
            INSERT INTO clinical.genomic_variants
                (patient_id, gene, variant, variant_type, chromosome, position,
                 ref_allele, alt_allele, zygosity, allele_frequency,
                 clinical_significance, actionability,
                 source_type, source_id, created_at, updated_at)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
                    'gdc_maf', 'tcga_maf_import_v1', NOW(), NOW())
        """, (
            v["patient_id"], v["gene"], v["variant"], v["variant_type"],
            v["chromosome"], v["position"], v["ref_allele"], v["alt_allele"],
            v["zygosity"], v["allele_frequency"],
            v["clinical_significance"], v["actionability"],
        ))
        stats["inserted"] += 1

    if not dry_run:
        conn.commit()

    cur.close()
    return stats


def main():
    parser = argparse.ArgumentParser(description="Import GDC MAF variants into Aurora")
    parser.add_argument("project", help="GDC project (e.g., TCGA-PRAD) or 'all'")
    parser.add_argument("--dry-run", action="store_true")
    parser.add_argument("--limit", type=int, default=0,
                        help="Max variants per MAF file (0=unlimited)")
    args = parser.parse_args()

    projects = []
    if args.project == "all":
        for d in sorted(Path(GENOMICS_ROOT).iterdir()):
            if d.is_dir() and d.name != "manifests":
                projects.append(d.name)
    else:
        projects = [args.project]

    print(f"=== MAF → Aurora Variant Import ===")
    if args.dry_run:
        print("  Mode: DRY RUN")
    if args.limit:
        print(f"  Limit: {args.limit} variants per file")
    print()

    conn = connect_db()
    patient_mapping = get_patient_mapping(conn)
    print(f"  Patient mapping: {len(patient_mapping)} identifiers")
    print()

    total_stats = {"inserted": 0, "skipped_duplicate": 0, "files": 0}

    for project in projects:
        project_dir = Path(GENOMICS_ROOT) / project
        if not project_dir.exists():
            print(f"  SKIP: {project} — directory not found")
            continue

        maf_files = sorted(project_dir.rglob("*.maf.gz"))
        print(f"=== {project}: {len(maf_files)} MAF files ===")

        project_variants = []
        for maf_file in maf_files:
            variants = parse_maf_file(maf_file, patient_mapping, args.limit)
            if variants:
                project_variants.extend(variants)
                total_stats["files"] += 1

        if not project_variants:
            print(f"  No matching variants found (no patients mapped)")
            print()
            continue

        print(f"  Parsed {len(project_variants)} significant variants from {total_stats['files']} files")

        result = import_variants(project_variants, conn, args.dry_run)
        total_stats["inserted"] += result["inserted"]
        total_stats["skipped_duplicate"] += result["skipped_duplicate"]

        print(f"  Inserted: {result['inserted']}, Duplicates skipped: {result['skipped_duplicate']}")
        print()

    conn.close()

    print(f"=== Complete ===")
    print(f"  Total inserted: {total_stats['inserted']}")
    print(f"  Duplicates skipped: {total_stats['skipped_duplicate']}")
    print(f"  Files processed: {total_stats['files']}")


if __name__ == "__main__":
    main()
