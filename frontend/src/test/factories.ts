import type { User } from "@/stores/authStore";
import type { GenomicVariant, GeneDrugInteraction } from "@/features/genomics/types";

export function createMockUser(overrides?: Partial<User>): User {
  return {
    id: 1,
    name: "Test User",
    email: "test@example.com",
    phone: null,
    avatar: null,
    phone_number: null,
    job_title: "Physician",
    department: "Cardiology",
    organization: "Test Hospital",
    bio: null,
    must_change_password: false,
    is_active: true,
    last_login_at: "2026-03-25T10:00:00Z",
    roles: ["physician"],
    permissions: ["view-patients", "edit-patients"],
    created_at: "2026-01-01T00:00:00Z",
    updated_at: "2026-03-25T10:00:00Z",
    ...overrides,
  };
}

export function createMockVariant(overrides?: Partial<GenomicVariant>): GenomicVariant {
  return {
    id: 1,
    upload_id: 1,
    source_id: 1,
    person_id: 100,
    sample_id: "SAMPLE-001",
    chromosome: "chr7",
    position: 55259515,
    reference_allele: "T",
    alternate_allele: "G",
    genome_build: "GRCh38",
    gene_symbol: "EGFR",
    hgvs_c: "c.2573T>G",
    hgvs_p: "p.Leu858Arg",
    variant_type: "SNV",
    variant_class: "missense_variant",
    consequence: "missense_variant",
    quality: 99.0,
    filter_status: "PASS",
    zygosity: "heterozygous",
    allele_frequency: 0.45,
    read_depth: 250,
    clinvar_id: "16609",
    clinvar_significance: "Pathogenic",
    cosmic_id: "COSM6224",
    measurement_concept_id: 0,
    mapping_status: "mapped",
    created_at: "2026-03-25T10:00:00Z",
    ...overrides,
  };
}

export function createMockInteraction(overrides?: Partial<GeneDrugInteraction>): GeneDrugInteraction {
  return {
    id: 1,
    gene: "EGFR",
    variant_pattern: "L858R",
    drug: "Erlotinib",
    drug_class: "EGFR TKI",
    relationship: "sensitive",
    evidence_level: "1",
    indication: "NSCLC",
    mechanism: "Activating mutation increases TKI binding affinity",
    source: "oncokb",
    source_url: "https://www.oncokb.org/gene/EGFR/L858R",
    oncokb_last_synced_at: "2026-03-01T00:00:00Z",
    last_verified_at: "2026-03-01T00:00:00Z",
    ...overrides,
  };
}
