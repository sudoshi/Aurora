"""
Fingerprint encoders — three specialized encoders that produce 256-dim vectors
for genomic, volumetric, and clinical patient dimensions.

V1 approach: structured feature hashing + text embedding hybrid.
Each encoder extracts structured features, builds a text representation,
and uses Ollama embeddings to produce a dense vector. Confidence is
derived from data completeness.
"""

import hashlib
import logging
import struct
from typing import Any

import numpy as np

from app.services.embedding_service import compute_embedding

logger = logging.getLogger(__name__)

VECTOR_DIM = 256


def _normalize(vec: np.ndarray) -> np.ndarray:
    """L2-normalize a vector."""
    norm = np.linalg.norm(vec)
    if norm == 0:
        return vec
    return vec / norm


def _to_pgvector_string(vec: np.ndarray) -> str:
    """Convert numpy array to pgvector-compatible string."""
    return "[" + ",".join(f"{v:.6f}" for v in vec) + "]"


def _hash_to_vector(text: str, dim: int = VECTOR_DIM) -> np.ndarray:
    """Deterministic hash of text to a fixed-dimension vector."""
    h = hashlib.sha256(text.encode()).digest()
    # Extend hash to fill dimension
    extended = h * ((dim * 4 // len(h)) + 1)
    floats = struct.unpack(f"{dim}f", extended[: dim * 4])
    return _normalize(np.array(floats, dtype=np.float32))


async def encode_genomic(
    patient_id: int,
    variants: list[dict[str, Any]],
) -> tuple[str, float]:
    """Encode genomic profile into a 256-dim vector.

    Combines:
    1. Structured features: variant count, actionable count, significance distribution
    2. Text embedding: gene+variant descriptions via Ollama

    Returns (pgvector_string, confidence).
    """
    if not variants:
        raise ValueError("No variants to encode")

    # Build structured feature vector (first 64 dims)
    n_variants = len(variants)
    genes = {v.get("gene", "") for v in variants}
    actionable = sum(
        1 for v in variants if v.get("clinical_significance") in ("pathogenic", "likely_pathogenic")
    )
    vus_count = sum(
        1 for v in variants if v.get("clinical_significance") in ("VUS", "uncertain significance")
    )

    # Variant type distribution
    type_counts: dict[str, int] = {}
    for v in variants:
        vtype = v.get("variant_type", "unknown")
        type_counts[vtype] = type_counts.get(vtype, 0) + 1

    structured = np.zeros(64, dtype=np.float32)
    structured[0] = min(n_variants / 50.0, 1.0)  # normalized variant count
    structured[1] = min(actionable / 10.0, 1.0)   # normalized actionable count
    structured[2] = min(vus_count / 20.0, 1.0)     # normalized VUS count
    structured[3] = len(genes) / max(n_variants, 1)  # gene diversity
    structured[4] = type_counts.get("SNV", 0) / max(n_variants, 1)
    structured[5] = type_counts.get("indel", 0) / max(n_variants, 1)
    structured[6] = type_counts.get("fusion", 0) / max(n_variants, 1)
    structured[7] = type_counts.get("CNV", 0) / max(n_variants, 1)

    # Mean allele frequency
    afs = [v.get("allele_frequency") for v in variants if v.get("allele_frequency")]
    structured[8] = np.mean(afs) if afs else 0.0

    # Build text representation for embedding (remaining 192 dims)
    gene_variant_strs = []
    for v in variants:
        parts = [v.get("gene", "")]
        if v.get("variant"):
            parts.append(v["variant"])
        if v.get("clinical_significance"):
            parts.append(v["clinical_significance"])
        gene_variant_strs.append(" ".join(parts))

    text = f"Genomic profile: {n_variants} variants, {actionable} actionable. " + "; ".join(
        gene_variant_strs[:15]  # cap to avoid token limits
    )

    try:
        raw_embedding = await compute_embedding(text)
        # Truncate or pad to 192 dims
        emb = np.array(raw_embedding[:192], dtype=np.float32)
        if len(emb) < 192:
            emb = np.pad(emb, (0, 192 - len(emb)))
    except Exception:
        logger.warning("Ollama embedding failed for patient %d, using hash fallback", patient_id)
        emb = _hash_to_vector(text, 192)

    # Concatenate: [structured(64) | embedding(192)] = 256
    combined = _normalize(np.concatenate([structured, emb]))

    # Confidence based on data richness
    confidence = min(1.0, 0.3 + (n_variants / 15.0) * 0.4 + (actionable / 3.0) * 0.3)

    return _to_pgvector_string(combined), round(confidence, 4)


async def encode_volumetric(
    patient_id: int,
    studies: list[dict[str, Any]],
) -> tuple[str, float]:
    """Encode imaging/volumetric data into a 256-dim vector.

    Combines:
    1. Structured features: study count, modality mix, tumor volumes, RECIST
    2. Text embedding: imaging summary via Ollama

    Returns (pgvector_string, confidence).
    """
    if not studies:
        raise ValueError("No imaging studies to encode")

    # Structured features (first 64 dims)
    structured = np.zeros(64, dtype=np.float32)
    structured[0] = min(len(studies) / 10.0, 1.0)  # study count

    modalities = [s.get("modality", "") for s in studies]
    structured[1] = 1.0 if "CT" in modalities else 0.0
    structured[2] = 1.0 if "MRI" in modalities else 0.0
    structured[3] = 1.0 if "PET" in modalities else 0.0

    # Aggregate measurements and segmentations
    all_volumes = []
    all_recist = []
    total_measurements = 0

    for study in studies:
        for seg in study.get("segmentations", []):
            vol = seg.get("volume_mm3")
            if vol is not None:
                all_volumes.append(vol)

        for meas in study.get("measurements", []):
            total_measurements += 1
            if meas.get("measurement_type") == "RECIST":
                val = meas.get("value_numeric")
                if val is not None:
                    all_recist.append(val)

    if all_volumes:
        structured[4] = min(np.sum(all_volumes) / 100000.0, 1.0)  # total tumor burden
        structured[5] = min(np.max(all_volumes) / 50000.0, 1.0)   # largest lesion
        structured[6] = min(len(all_volumes) / 10.0, 1.0)          # lesion count

    if all_recist:
        structured[7] = min(np.mean(all_recist) / 100.0, 1.0)

    structured[8] = min(total_measurements / 20.0, 1.0)

    # Text representation
    body_parts = {s.get("body_part", "unknown") for s in studies}
    text = (
        f"Imaging profile: {len(studies)} studies, modalities: {', '.join(set(modalities))}. "
        f"Body parts: {', '.join(body_parts)}. "
        f"Lesions: {len(all_volumes)}, total volume: {sum(all_volumes):.0f}mm³. "
        f"Measurements: {total_measurements}."
    )

    try:
        raw_embedding = await compute_embedding(text)
        emb = np.array(raw_embedding[:192], dtype=np.float32)
        if len(emb) < 192:
            emb = np.pad(emb, (0, 192 - len(emb)))
    except Exception:
        logger.warning("Ollama embedding failed for patient %d volumetric, using hash fallback", patient_id)
        emb = _hash_to_vector(text, 192)

    combined = _normalize(np.concatenate([structured, emb]))

    confidence = min(1.0, 0.2 + (len(studies) / 4.0) * 0.3 + (len(all_volumes) / 5.0) * 0.3 + (total_measurements / 10.0) * 0.2)

    return _to_pgvector_string(combined), round(confidence, 4)


async def encode_clinical(
    patient_id: int,
    conditions: list[dict],
    medications: list[dict],
    drug_eras: list[dict],
    measurements: list[dict],
    visits: list[dict],
) -> tuple[str, float]:
    """Encode clinical trajectory into a 256-dim vector.

    Returns (pgvector_string, confidence).
    """
    has_any = conditions or medications or measurements

    if not has_any:
        raise ValueError("No clinical data to encode")

    # Structured features (first 64 dims)
    structured = np.zeros(64, dtype=np.float32)
    structured[0] = min(len(conditions) / 10.0, 1.0)
    structured[1] = min(len(medications) / 10.0, 1.0)
    structured[2] = min(len(drug_eras) / 5.0, 1.0)
    structured[3] = min(len(measurements) / 20.0, 1.0)
    structured[4] = min(len(visits) / 10.0, 1.0)

    # Condition domains
    domains = {c.get("domain", "") for c in conditions}
    structured[5] = 1.0 if "oncology" in domains else 0.0
    structured[6] = 1.0 if "surgical" in domains else 0.0
    structured[7] = 1.0 if "rare_disease" in domains else 0.0

    # Visit type distribution
    visit_types = [v.get("visit_type", "") for v in visits]
    structured[8] = sum(1 for t in visit_types if t == "emergency") / max(len(visits), 1)
    structured[9] = sum(1 for t in visit_types if t == "inpatient") / max(len(visits), 1)

    # Medication status distribution
    med_statuses = [m.get("status", "") for m in medications]
    structured[10] = sum(1 for s in med_statuses if s == "active") / max(len(medications), 1)
    structured[11] = sum(1 for s in med_statuses if s == "discontinued") / max(len(medications), 1)

    # Text representation
    condition_names = [c.get("concept_name", "") for c in conditions[:10]]
    drug_names = [m.get("drug_name", "") for m in medications[:10]]

    text = (
        f"Clinical profile: {len(conditions)} conditions ({', '.join(condition_names)}), "
        f"{len(medications)} medications ({', '.join(drug_names)}), "
        f"{len(visits)} visits, {len(measurements)} lab measurements."
    )

    try:
        raw_embedding = await compute_embedding(text)
        emb = np.array(raw_embedding[:192], dtype=np.float32)
        if len(emb) < 192:
            emb = np.pad(emb, (0, 192 - len(emb)))
    except Exception:
        logger.warning("Ollama embedding failed for patient %d clinical, using hash fallback", patient_id)
        emb = _hash_to_vector(text, 192)

    combined = _normalize(np.concatenate([structured, emb]))

    data_points = len(conditions) + len(medications) + len(measurements) + len(visits)
    confidence = min(1.0, 0.2 + (data_points / 30.0) * 0.8)

    return _to_pgvector_string(combined), round(confidence, 4)
