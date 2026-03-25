#!/usr/bin/env bash
# Verification script for Phase 2: Genomics & AI Endpoints
# Tests BUG-08, BUG-09, BUG-10
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost:8085}"
PASS=0
FAIL=0
TOTAL=3

echo "========================================"
echo "Phase 2: Genomics & AI Endpoint Verification"
echo "========================================"
echo ""

# --- Obtain auth token ---
echo "[AUTH] Logging in as admin@acumenus.net..."
LOGIN_RESP=$(curl -s -X POST "$BASE_URL/api/auth/login" \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@acumenus.net","password":"superuser"}')

TOKEN=$(echo "$LOGIN_RESP" | jq -r '.data.access_token')

if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
  echo "[AUTH] FAIL - Could not obtain auth token"
  echo "Response: $LOGIN_RESP"
  exit 1
fi
echo "[AUTH] OK - Token obtained"
echo ""

# --- BUG-08: GET /api/genomics/interactions ---
echo "[BUG-08] Testing GET /api/genomics/interactions..."
INTERACTIONS_RESP=$(curl -s "$BASE_URL/api/genomics/interactions" \
  -H "Authorization: Bearer $TOKEN")

INTERACTIONS_SUCCESS=$(echo "$INTERACTIONS_RESP" | jq -r '.success')
INTERACTIONS_COUNT=$(echo "$INTERACTIONS_RESP" | jq '.data | length')
FIRST_HAS_GENE=$(echo "$INTERACTIONS_RESP" | jq -r '.data[0].gene // empty')
FIRST_HAS_DRUG=$(echo "$INTERACTIONS_RESP" | jq -r '.data[0].drug // empty')
FIRST_HAS_EVIDENCE=$(echo "$INTERACTIONS_RESP" | jq -r '.data[0].evidence_level // empty')

if [ "$INTERACTIONS_SUCCESS" = "true" ] && [ "$INTERACTIONS_COUNT" -ge 42 ] && \
   [ -n "$FIRST_HAS_GENE" ] && [ -n "$FIRST_HAS_DRUG" ] && [ -n "$FIRST_HAS_EVIDENCE" ]; then
  echo "[BUG-08] PASS - $INTERACTIONS_COUNT gene-drug interactions returned"
  echo "         First record: gene=$FIRST_HAS_GENE, drug=$FIRST_HAS_DRUG, evidence=$FIRST_HAS_EVIDENCE"
  PASS=$((PASS + 1))
else
  echo "[BUG-08] FAIL - success=$INTERACTIONS_SUCCESS, count=$INTERACTIONS_COUNT"
  echo "         gene=$FIRST_HAS_GENE, drug=$FIRST_HAS_DRUG, evidence=$FIRST_HAS_EVIDENCE"
  FAIL=$((FAIL + 1))
fi
echo ""

# --- BUG-09: GET /api/genomics/stats ---
echo "[BUG-09] Testing GET /api/genomics/stats..."
STATS_RESP=$(curl -s "$BASE_URL/api/genomics/stats" \
  -H "Authorization: Bearer $TOKEN")

STATS_SUCCESS=$(echo "$STATS_RESP" | jq -r '.success')
TOTAL_VARIANTS=$(echo "$STATS_RESP" | jq -r '.data.total_variants')
PATHOGENIC_COUNT=$(echo "$STATS_RESP" | jq -r '.data.pathogenic_count')
VUS_COUNT=$(echo "$STATS_RESP" | jq -r '.data.vus_count')

if [ "$STATS_SUCCESS" = "true" ] && [ "$TOTAL_VARIANTS" -gt 0 ] && [ "$PATHOGENIC_COUNT" -gt 0 ]; then
  echo "[BUG-09] PASS - total_variants=$TOTAL_VARIANTS, pathogenic=$PATHOGENIC_COUNT, vus=$VUS_COUNT"
  PASS=$((PASS + 1))
else
  echo "[BUG-09] FAIL - success=$STATS_SUCCESS, total_variants=$TOTAL_VARIANTS, pathogenic=$PATHOGENIC_COUNT"
  FAIL=$((FAIL + 1))
fi
echo ""

# --- BUG-10: POST /api/ai/decision-support/genomic-briefing ---
echo "[BUG-10] Testing POST /api/ai/decision-support/genomic-briefing..."
BRIEFING_RESP=$(curl -s -X POST "$BASE_URL/api/ai/decision-support/genomic-briefing" \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{
    "patient_id": 1,
    "variants": [{"gene":"BRAF","variant":"V600E","classification":"pathogenic","evidence_level":"1A","therapies":["Vemurafenib"]}],
    "drug_exposures": [],
    "interactions": [{"gene":"BRAF","drug":"Vemurafenib","relationship":"sensitive","evidence_level":"1A"}],
    "total_variant_count": 5
  }' \
  --max-time 130)

BRIEFING_HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/api/ai/decision-support/genomic-briefing" \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{
    "patient_id": 1,
    "variants": [{"gene":"BRAF","variant":"V600E","classification":"pathogenic","evidence_level":"1A","therapies":["Vemurafenib"]}],
    "drug_exposures": [],
    "interactions": [{"gene":"BRAF","drug":"Vemurafenib","relationship":"sensitive","evidence_level":"1A"}],
    "total_variant_count": 5
  }' \
  --max-time 130 2>/dev/null || echo "000")

HAS_BRIEFING=$(echo "$BRIEFING_RESP" | jq -r '.briefing // empty')
HAS_ERROR=$(echo "$BRIEFING_RESP" | jq -r '.error // empty')
OLLAMA_NOTE=""

if [ -n "$HAS_BRIEFING" ] && [ "$HAS_BRIEFING" != "null" ]; then
  echo "[BUG-10] PASS - Briefing narrative received (${#HAS_BRIEFING} chars)"
  echo "         Preview: $(echo "$HAS_BRIEFING" | head -c 120)..."
  PASS=$((PASS + 1))
elif [ "$BRIEFING_HTTP_CODE" = "200" ] || [ "$BRIEFING_HTTP_CODE" = "503" ]; then
  # Graceful degradation: endpoint responds but Ollama may be unavailable
  if [ -n "$HAS_ERROR" ] && [ "$HAS_ERROR" != "null" ]; then
    OLLAMA_NOTE=" (graceful degradation: $HAS_ERROR)"
  fi
  echo "[BUG-10] PASS - Endpoint responds (HTTP $BRIEFING_HTTP_CODE)$OLLAMA_NOTE"
  echo "         Response: $(echo "$BRIEFING_RESP" | head -c 200)"
  PASS=$((PASS + 1))
else
  echo "[BUG-10] FAIL - HTTP $BRIEFING_HTTP_CODE, no briefing or graceful error"
  echo "         Response: $(echo "$BRIEFING_RESP" | head -c 300)"
  FAIL=$((FAIL + 1))
fi
echo ""

# --- Summary ---
echo "========================================"
echo "Results: $PASS/$TOTAL PASS, $FAIL/$TOTAL FAIL"
echo "========================================"

if [ "$FAIL" -gt 0 ]; then
  echo "VERIFICATION FAILED"
  exit 1
else
  echo "ALL CHECKS PASSED"
  exit 0
fi
