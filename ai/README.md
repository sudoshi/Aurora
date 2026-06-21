# Aurora AI Service

FastAPI service for Aurora's AI features: Abby copilot, evidence-grounded
decision drafting (Claude + BioMCP), imaging AI (segmentation/volumetrics/
response assessment), genomic briefing, and LLM advisory decision support.

> **Research Use Only.** AI outputs are labeled with machine-readable provenance:
> imaging responses carry `data_source`/`verified`/`disclaimer`; advisory
> decision-support carries `evidence_grade="llm_advisory"`; every Ollama-dependent
> endpoint reports `ai_status` (`ok` | `degraded`) so a degraded LLM is never
> mistaken for a verified result.

## Toolchain & the canonical test path (W0-T05)

**Use the Docker image, not host Python, for tests/lint/type-checks.** Host
Python 3.14 on this machine cannot build the pinned dependency set —
`pydantic-core==2.27.2` has no 3.14 wheel and its PyO3 build fails. The service
targets **Python 3.12**, which is what the `aurora-ai:dev` image provides.

```bash
# From the ai/ directory. The image has the pinned deps (Python 3.12) baked in;
# the bind-mount runs your working-tree code against them.

# Tests + coverage (CI parity — pytest.ini enforces --cov=app --cov-fail-under)
docker run --rm -v "$PWD":/work -w /work aurora-ai:dev python -m pytest tests/ -q

# Lint, format check, and type check (CI runs these unmasked)
docker run --rm -v "$PWD":/work -w /work aurora-ai:dev sh -c \
  "pip install -q ruff mypy >/dev/null 2>&1; \
   ruff check app/ --select E,F,W --ignore E501 && \
   ruff format app/ --check && \
   mypy app/ --ignore-missing-imports --no-error-summary"
```

If the `aurora-ai:dev` image is missing, build it from the service Dockerfile
(`docker/Dockerfile.ai`) — it pins Python 3.12 and installs `requirements.txt`.

### Why not a host venv?
A host venv only works on a Python 3.12 interpreter. If you must run locally
without Docker, create the venv with an explicit 3.12 (e.g. `python3.12 -m venv
.venv`), not the system `python3` (3.14). The Docker path above is the supported,
reproducible one and matches CI.

## Coverage note
`pytest.ini` measures the whole `app/` package (`--cov=app`). The current floor
is intentionally honest (whole-package, not a curated subset); see the ratchet
TODO in `pytest.ini` to raise it toward 70% as module coverage improves.

## CI
The `ai` job in `.github/workflows/ci.yml` runs ruff (lint + format), mypy, and
pytest on Python 3.13 in GitHub Actions. The Docker image (3.12) is the local
equivalent; both must stay green (no `continue-on-error` masks).
