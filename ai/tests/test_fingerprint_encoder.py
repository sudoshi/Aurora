"""Tests for fingerprint encoders."""

import pytest

from app.services.fingerprint_encoder import (
    _hash_to_vector,
    _normalize,
    _to_pgvector_string,
    encode_clinical,
    encode_genomic,
    encode_volumetric,
)


def test_normalize_zero_vector():
    import numpy as np
    vec = np.zeros(10)
    result = _normalize(vec)
    assert all(v == 0.0 for v in result)


def test_normalize_unit_vector():
    import numpy as np
    vec = np.array([3.0, 4.0])
    result = _normalize(vec)
    assert abs(np.linalg.norm(result) - 1.0) < 1e-6


def test_hash_to_vector_deterministic():
    v1 = _hash_to_vector("test", 256)
    v2 = _hash_to_vector("test", 256)
    assert (v1 == v2).all()


def test_hash_to_vector_different_inputs():
    v1 = _hash_to_vector("test_a", 256)
    v2 = _hash_to_vector("test_b", 256)
    assert not (v1 == v2).all()


def test_to_pgvector_string():
    import numpy as np
    vec = np.array([0.1, 0.2, 0.3])
    result = _to_pgvector_string(vec)
    assert result.startswith("[")
    assert result.endswith("]")
    assert "0.100000" in result


@pytest.mark.asyncio
async def test_encode_genomic_empty_raises():
    with pytest.raises(ValueError, match="No variants"):
        await encode_genomic(1, [])


@pytest.mark.asyncio
async def test_encode_genomic_produces_vector():
    variants = [
        {"gene": "BRAF", "variant": "V600E", "variant_type": "SNV",
         "allele_frequency": 0.45, "clinical_significance": "pathogenic"},
        {"gene": "TP53", "variant": "R175H", "variant_type": "SNV",
         "allele_frequency": 0.3, "clinical_significance": "pathogenic"},
    ]
    vector_str, confidence = await encode_genomic(1, variants)
    assert vector_str.startswith("[")
    assert 0.0 < confidence <= 1.0
    # Verify 256 dimensions
    values = vector_str.strip("[]").split(",")
    assert len(values) == 256


@pytest.mark.asyncio
async def test_encode_volumetric_empty_raises():
    with pytest.raises(ValueError, match="No imaging"):
        await encode_volumetric(1, [])


@pytest.mark.asyncio
async def test_encode_volumetric_produces_vector():
    studies = [
        {
            "modality": "CT",
            "body_part": "chest",
            "study_date": "2026-01-01",
            "measurements": [{"measurement_type": "RECIST", "value_numeric": 25.0, "unit": "mm"}],
            "segmentations": [{"volume_mm3": 15000.0, "label": "tumor"}],
        }
    ]
    vector_str, confidence = await encode_volumetric(1, studies)
    assert vector_str.startswith("[")
    assert 0.0 < confidence <= 1.0


@pytest.mark.asyncio
async def test_encode_clinical_empty_raises():
    with pytest.raises(ValueError, match="No clinical"):
        await encode_clinical(1, [], [], [], [], [])


@pytest.mark.asyncio
async def test_encode_clinical_produces_vector():
    vector_str, confidence = await encode_clinical(
        patient_id=1,
        conditions=[{"concept_name": "NSCLC", "domain": "oncology", "status": "active"}],
        medications=[{"drug_name": "pembrolizumab", "status": "active"}],
        drug_eras=[],
        measurements=[],
        visits=[{"visit_type": "outpatient"}],
    )
    assert vector_str.startswith("[")
    assert 0.0 < confidence <= 1.0
    values = vector_str.strip("[]").split(",")
    assert len(values) == 256
