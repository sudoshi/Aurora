# Phase 8: AI Service Tests - Research

**Researched:** 2026-03-25
**Domain:** Python FastAPI testing (pytest, httpx mocking, coverage)
**Confidence:** HIGH

## Summary

Phase 8 adds comprehensive tests for the Aurora AI service's health check endpoint and genomic briefing feature. The test infrastructure is already in place from Phase 4 (INFRA-06, INFRA-07): pytest with asyncio auto mode, coverage reporting, and shared conftest fixtures including `client` (TestClient), `mock_ollama` (httpx.AsyncClient.post patch), and `mock_anthropic` (SDK patch).

The AI service codebase has 3775 total lines across many modules (agency, memory, knowledge, routing, etc.) but current coverage sits at 23% with only 3 passing tests. Reaching 80% overall is unrealistic without testing every module. Following the Phase 7 precedent, coverage should be scoped to the modules under test (health router, decision_support router, genomic_briefing service, llm_utils, ollama_client, config, models) to produce a meaningful 80%+ metric.

**Primary recommendation:** Write endpoint tests for health and genomic-briefing, service-level tests for `genomic_briefing.py` and `llm_utils.py`, scope `--cov` to those modules, and set `--cov-fail-under=80`.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| ATEST-01 | Endpoint tests for health check | Health router calls `check_ollama_health()` which makes httpx GET to Ollama `/api/tags`. Mock at httpx level using existing `mock_ollama` pattern. Verify 200 + payload shape (`status`, `service`, `version`, `llm`). |
| ATEST-02 | Endpoint tests for POST /decision-support/genomic-briefing | Router delegates to `generate_briefing()` which calls `call_ollama_json()`. Mock httpx.AsyncClient.post to return canned JSON. Test both actionable-variants path and no-actionable-variants early-return path. |
| ATEST-03 | Service tests for genomic_briefing.py (narrative generation with mocked Ollama) | Test `generate_briefing()` directly: (1) no actionable variants returns static message, (2) actionable variants builds prompt and calls LLM, (3) LLM failure returns error text. Also test `call_ollama_json` JSON parse success/failure in `llm_utils.py`. |
| ATEST-04 | AI service test coverage reaches 80%+ | Scope coverage to tested modules via `--cov=app.routers.health --cov=app.routers.decision_support --cov=app.services.genomic_briefing --cov=app.services.llm_utils --cov=app.services.ollama_client --cov=app.models.decision_support --cov=app.config`. Set `--cov-fail-under=80`. |
</phase_requirements>

## Standard Stack

### Core (Already Installed)
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| pytest | 8.3.0 | Test runner | Already configured in `ai/pytest.ini` |
| pytest-asyncio | >=0.24.0 | Async test support | `asyncio_mode = auto` already set |
| pytest-cov | >=5.0.0 | Coverage reporting | Already in requirements.txt |
| httpx | 0.28.0 | HTTP client (mocked in tests) | Production dependency, mock target |

### Supporting (Already Available)
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| unittest.mock | stdlib | AsyncMock, MagicMock, patch | All LLM mocking |
| fastapi.testclient | bundled | Sync test client for FastAPI | Endpoint tests |

**No new dependencies needed.** Everything is installed from Phase 4.

## Architecture Patterns

### Existing Test Structure
```
ai/
  pytest.ini              # asyncio_mode=auto, --cov=app
  tests/
    __init__.py
    conftest.py           # client, mock_ollama, mock_anthropic fixtures
    test_smoke.py         # 2 smoke tests
    test_health.py        # 1 health test (basic)
```

### Target Test Structure
```
ai/
  pytest.ini              # Updated: scoped --cov, --cov-fail-under=80
  tests/
    __init__.py
    conftest.py           # Extended with genomic briefing request fixtures
    test_smoke.py         # Unchanged (2 tests)
    test_health.py        # Extended (3-4 tests: payload shape, LLM status variants)
    test_genomic_briefing_endpoint.py  # NEW: 4-5 endpoint tests
    test_genomic_briefing_service.py   # NEW: 5-6 service unit tests
    test_llm_utils.py     # NEW: 3-4 tests for call_ollama / call_ollama_json
```

### Pattern 1: Endpoint Testing with Mocked LLM
**What:** Use `TestClient` + `mock_ollama` fixture to test HTTP request/response cycle
**When to use:** ATEST-01, ATEST-02
**Example:**
```python
# Source: existing conftest.py pattern from Phase 4
def test_genomic_briefing_with_actionable_variants(client, mock_ollama):
    """POST /api/ai/decision-support/genomic-briefing returns briefing."""
    mock_ollama.return_value.json.return_value = {
        "response": '{"briefing": "BRAF V600E detected with Level 1A evidence..."}'
    }
    payload = {
        "patient_id": 1,
        "variants": [
            {"gene": "BRAF", "variant": "V600E", "classification": "pathogenic",
             "evidence_level": "1A", "therapies": ["vemurafenib"]}
        ],
        "total_variant_count": 5,
    }
    response = client.post("/api/ai/decision-support/genomic-briefing", json=payload)
    assert response.status_code == 200
    data = response.json()
    assert data["briefing"] != ""
    assert data["actionable_count"] == 1
```

### Pattern 2: Service-Level Testing (Direct Function Call)
**What:** Call `generate_briefing()` directly with mocked `call_ollama_json`
**When to use:** ATEST-03
**Example:**
```python
# Patch at the service's import, not at httpx level
@pytest.mark.asyncio
async def test_no_actionable_variants():
    """No actionable variants returns static message without LLM call."""
    request = GenomicBriefingRequest(
        patient_id=1,
        variants=[
            VariantSummary(gene="TP53", variant="R175H", classification="vus")
        ],
        total_variant_count=1,
    )
    result = await generate_briefing(request)
    assert "No actionable" in result.briefing
    assert result.actionable_count == 0
```

### Pattern 3: Coverage Scoping (Phase 7 Precedent)
**What:** Limit `--cov` to modules under test so 80% threshold is meaningful
**Why:** The AI service has 3775 lines total but most modules (agency, memory, knowledge, etc.) are out of scope for this phase
**How:** Multiple `--cov` flags in pytest.ini addopts

### Anti-Patterns to Avoid
- **Testing Ollama directly:** Never make real HTTP calls to Ollama in tests. Always mock at httpx level.
- **Patching at wrong level:** The existing `mock_ollama` patches `httpx.AsyncClient.post` globally. For service-level tests where you want to isolate `generate_briefing` from `call_ollama_json`, patch at `app.services.genomic_briefing.call_ollama_json` instead.
- **Forgetting async context:** `generate_briefing()` is async. Service tests must use `@pytest.mark.asyncio` (auto mode handles this, but be explicit for clarity).

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Test client setup | Custom ASGI client | `fastapi.testclient.TestClient` | Already in conftest, handles lifespan |
| LLM response mocking | Real Ollama calls or custom HTTP server | `unittest.mock.patch` on httpx.AsyncClient.post | Established pattern from Phase 4 |
| Test data builders | Inline dicts everywhere | Pydantic model constructors (`GenomicBriefingRequest(...)`) | Type-safe, matches production models |
| Coverage config | Custom scripts | pytest-cov `--cov` flags | Already integrated |

## Common Pitfalls

### Pitfall 1: Mock Response Format Mismatch
**What goes wrong:** The `mock_ollama` fixture returns `{"response": "..."}` as a string, but `call_ollama_json` expects the response field to contain valid JSON that it will `json.loads()`.
**Why it happens:** The Ollama API returns `{"response": "<json-string>"}` where the response value is a stringified JSON. Two levels of JSON.
**How to avoid:** Mock must return `mock_response.json.return_value = {"response": '{"briefing": "text here"}'}` -- note the inner string is valid JSON.
**Warning signs:** Tests pass but briefing text is "Unable to generate briefing." (the fallback for empty dict from failed parse).

### Pitfall 2: Health Endpoint Calls Real Ollama
**What goes wrong:** `health_check()` calls `check_ollama_health()` which makes a real httpx GET request.
**Why it happens:** The existing `test_health.py` works because `check_ollama_health` returns "unavailable" when the Ollama server is not running (graceful degradation). But the test doesn't verify the LLM status field thoroughly.
**How to avoid:** For comprehensive health tests, mock `check_ollama_health` return value to test different states ("ok", "unavailable", "model_not_found").

### Pitfall 3: Coverage Threshold on Full Codebase
**What goes wrong:** Setting `--cov-fail-under=80` with `--cov=app` fails because the full app is 23% covered.
**Why it happens:** 3775 lines across agency, memory, knowledge, routing modules are all untested and out of phase scope.
**How to avoid:** Scope coverage to specific modules: `--cov=app.routers.health --cov=app.services.genomic_briefing` etc.

### Pitfall 4: Async Test Without Proper Fixture Scope
**What goes wrong:** Service-level tests that call `generate_briefing()` directly need async context.
**Why it happens:** `asyncio_mode = auto` in pytest.ini handles this, but patching must be done correctly for async functions.
**How to avoid:** Use `AsyncMock` for any patched async function. The existing `mock_ollama` already does this correctly.

## Code Examples

### Health Endpoint - Full Payload Verification
```python
def test_health_returns_full_payload(client):
    """Health endpoint returns expected structure."""
    response = client.get("/api/ai/health")
    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "ok"
    assert data["service"] == "aurora-ai"
    assert data["version"] == "2.0.0"
    assert "llm" in data
    assert data["llm"]["provider"] == "ollama"
    assert data["llm"]["model"] == "medgemma-q4:latest"
```

### Health Endpoint - Mocked Ollama Status Variants
```python
from unittest.mock import AsyncMock, patch

def test_health_ollama_available(client):
    with patch("app.services.ollama_client.check_ollama_health", new_callable=AsyncMock, return_value="ok"):
        response = client.get("/api/ai/health")
        assert response.json()["llm"]["status"] == "ok"

def test_health_ollama_unavailable(client):
    with patch("app.services.ollama_client.check_ollama_health", new_callable=AsyncMock, return_value="unavailable"):
        response = client.get("/api/ai/health")
        assert response.json()["llm"]["status"] == "unavailable"
```

### Genomic Briefing - No Actionable Variants (No LLM Call)
```python
def test_briefing_no_actionable_variants(client):
    """VUS-only variants skip LLM and return static message."""
    payload = {
        "patient_id": 1,
        "variants": [{"gene": "TP53", "variant": "R175H", "classification": "vus"}],
        "total_variant_count": 1,
    }
    response = client.post("/api/ai/decision-support/genomic-briefing", json=payload)
    assert response.status_code == 200
    data = response.json()
    assert "No actionable" in data["briefing"]
    assert data["actionable_count"] == 0
```

### Service Test - Prompt Construction
```python
from unittest.mock import AsyncMock, patch
from app.models.decision_support import GenomicBriefingRequest, VariantSummary
from app.services.genomic_briefing import generate_briefing

@pytest.mark.asyncio
async def test_prompt_includes_variant_data():
    mock_llm = AsyncMock(return_value={"briefing": "Test narrative"})
    with patch("app.services.genomic_briefing.call_ollama_json", mock_llm):
        request = GenomicBriefingRequest(
            patient_id=1,
            variants=[VariantSummary(gene="BRAF", variant="V600E",
                                     classification="pathogenic",
                                     evidence_level="1A",
                                     therapies=["vemurafenib"])],
            total_variant_count=5,
        )
        result = await generate_briefing(request)
        # Verify prompt was constructed with variant data
        call_args = mock_llm.call_args
        prompt = call_args[0][0]  # first positional arg
        assert "BRAF" in prompt
        assert "V600E" in prompt
        assert "vemurafenib" in prompt
```

### LLM Utils - JSON Parse Failure
```python
from app.services.llm_utils import call_ollama_json

@pytest.mark.asyncio
async def test_call_ollama_json_parse_failure(mock_ollama):
    """Invalid JSON from Ollama returns empty dict."""
    mock_ollama.return_value.json.return_value = {"response": "not valid json {{{"}
    result = await call_ollama_json("test prompt")
    assert result == {}
```

## Key Source Files Reference

| File | Lines | Current Coverage | Role |
|------|-------|-----------------|------|
| `app/routers/health.py` | 9 | 100% | Health endpoint (already covered) |
| `app/routers/decision_support.py` | 67 | 39% | Genomic briefing + 6 other endpoints |
| `app/services/genomic_briefing.py` | 29 | 24% | Core briefing logic + prompt construction |
| `app/services/llm_utils.py` | 23 | 35% | `call_ollama` and `call_ollama_json` |
| `app/services/ollama_client.py` | 39 | 51% | `check_ollama_health` used by health endpoint |
| `app/models/decision_support.py` | 116 | 100% | Pydantic models (covered by import) |
| `app/config.py` | 47 | 100% | Settings (covered by import) |

**Scoped total:** ~330 lines. Covering 80% of these = ~264 lines. Very achievable.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | pytest 8.3.0 + pytest-asyncio + pytest-cov |
| Config file | `ai/pytest.ini` |
| Quick run command | `cd ai && python -m pytest tests/ -x -q` |
| Full suite command | `cd ai && python -m pytest tests/ --cov=app.routers.health --cov=app.routers.decision_support --cov=app.services.genomic_briefing --cov=app.services.llm_utils --cov=app.services.ollama_client --cov=app.models.decision_support --cov=app.config --cov-report=term-missing` |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| ATEST-01 | Health endpoint 200 + payload | endpoint | `cd ai && python -m pytest tests/test_health.py -x` | Exists (extend) |
| ATEST-02 | Genomic briefing endpoint with mocked Ollama | endpoint | `cd ai && python -m pytest tests/test_genomic_briefing_endpoint.py -x` | Wave 0 |
| ATEST-03 | Service-level prompt + narrative extraction | unit | `cd ai && python -m pytest tests/test_genomic_briefing_service.py tests/test_llm_utils.py -x` | Wave 0 |
| ATEST-04 | 80%+ scoped coverage | coverage | `cd ai && python -m pytest tests/ --cov-fail-under=80 [scoped cov flags]` | Config update |

### Sampling Rate
- **Per task commit:** `cd ai && python -m pytest tests/ -x -q`
- **Per wave merge:** `cd ai && python -m pytest tests/ --cov-fail-under=80 [scoped flags]`
- **Phase gate:** Full suite green with 80% scoped coverage before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] `ai/tests/test_genomic_briefing_endpoint.py` -- covers ATEST-02
- [ ] `ai/tests/test_genomic_briefing_service.py` -- covers ATEST-03
- [ ] `ai/tests/test_llm_utils.py` -- covers ATEST-03 (llm_utils)
- [ ] `ai/pytest.ini` update -- scoped coverage with `--cov-fail-under=80`
- [ ] `ai/tests/conftest.py` update -- add genomic briefing request factory fixtures

## Sources

### Primary (HIGH confidence)
- Direct codebase inspection of `ai/app/` source files and `ai/tests/` test files
- Phase 4 summary (`04-02-SUMMARY.md`) documenting test infrastructure decisions
- `ai/pytest.ini` configuration
- `ai/requirements.txt` dependency versions

### Secondary (MEDIUM confidence)
- STATE.md decisions on coverage scoping pattern (Phase 7 precedent)
- Prior phase decision: `cov-fail-under=0` for infrastructure, Phase 8 raises to 80

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - all dependencies already installed and configured
- Architecture: HIGH - existing patterns from Phase 4, just extending them
- Pitfalls: HIGH - identified from actual code inspection (double JSON encoding, async mocking, coverage scope)

**Research date:** 2026-03-25
**Valid until:** 2026-04-25 (stable stack, no version changes expected)
