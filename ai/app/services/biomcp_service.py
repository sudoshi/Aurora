"""BioMCP evidence retrieval.

Fetches grounding evidence (PubMed articles, ClinicalTrials.gov trials, and
clinically-significant variants) for an MDT case from BioMCP. Every source
degrades to an empty list on any error or timeout, and :meth:`gather` never
raises — the decision draft must still be produced if evidence retrieval fails.
"""

from __future__ import annotations

import asyncio
import json
import logging

from biomcp.articles.search import PubmedRequest, search_articles
from biomcp.trials.search import TrialQuery, search_trials
from biomcp.variants.search import VariantQuery, search_variants

from app.config import settings

logger = logging.getLogger(__name__)

_TIMEOUT_S = 20.0


def _clean(items: list[str] | None) -> list[str]:
    return [str(x) for x in (items or []) if x]


class BioMcpService:
    """Live biomedical evidence retrieval via the ``biomcp`` package."""

    async def gather(
        self,
        genes: list[str],
        conditions: list[str],
        drugs: list[str],
        max_per_source: int = 5,
    ) -> dict[str, list[dict]]:
        """Return ``{"articles": [...], "trials": [...], "variants": [...]}``.

        Each item is normalized to ``{"type", "id", "title", "url"}``. Disabled
        via ``BIOMCP_ENABLED=false``. Never raises.
        """
        if not settings.biomcp_enabled:
            return {"articles": [], "trials": [], "variants": []}

        return {
            "articles": await self._safe(
                self._articles(genes, conditions, max_per_source)
            ),
            "trials": await self._safe(self._trials(conditions, max_per_source)),
            "variants": await self._safe(self._variants(genes, max_per_source)),
        }

    async def _safe(self, coro) -> list[dict]:
        try:
            return await asyncio.wait_for(coro, timeout=_TIMEOUT_S)
        except Exception as exc:  # noqa: BLE001 — degrade-safe by design
            logger.warning("BioMCP retrieval failed: %s", exc)
            return []

    async def _articles(
        self, genes: list[str], conditions: list[str], n: int
    ) -> list[dict]:
        genes, conditions = _clean(genes), _clean(conditions)
        if not genes and not conditions:
            return []
        raw = await search_articles(
            PubmedRequest(genes=genes, diseases=conditions), output_json=True, limit=n
        )
        records = json.loads(raw)
        items = records if isinstance(records, list) else []
        out: list[dict] = []
        for r in items[:n]:
            if not isinstance(r, dict):
                continue
            pmid = r.get("pmid")
            out.append(
                {
                    "type": "article",
                    "id": f"PMID:{pmid}" if pmid else (r.get("doi") or ""),
                    "title": r.get("title") or "",
                    "url": r.get("pubmed_url") or r.get("doi_url") or "",
                }
            )
        return out

    async def _trials(self, conditions: list[str], n: int) -> list[dict]:
        conditions = _clean(conditions)
        if not conditions:
            return []
        raw = await search_trials(TrialQuery(conditions=conditions), output_json=True)
        records = json.loads(raw)
        items = (
            records
            if isinstance(records, list)
            else (records.get("trials") or records.get("results") or [])
        )
        out: list[dict] = []
        for r in items[:n]:
            if not isinstance(r, dict):
                continue
            nct = r.get("NCT Number") or r.get("nct_id") or r.get("nctId")
            out.append(
                {
                    "type": "trial",
                    "id": nct or "",
                    "title": r.get("Study Title") or r.get("title") or "",
                    "url": r.get("Study URL")
                    or (f"https://clinicaltrials.gov/study/{nct}" if nct else ""),
                }
            )
        return out

    async def _variants(self, genes: list[str], n: int) -> list[dict]:
        genes = _clean(genes)
        if not genes:
            return []
        out: list[dict] = []
        for gene in genes[:2]:
            raw = await search_variants(
                VariantQuery(gene=gene, significance="pathogenic"), output_json=True
            )
            records = json.loads(raw)
            variants = (
                records.get("variants")
                if isinstance(records, dict)
                else (records if isinstance(records, list) else [])
            )
            for r in (variants or [])[:n]:
                if not isinstance(r, dict):
                    continue
                vid = r.get("_id") or r.get("rsid") or r.get("dbsnp_rsid") or ""
                hgvs = r.get("hgvsp") or r.get("hgvs") or ""
                out.append(
                    {
                        "type": "variant",
                        "id": str(vid),
                        "title": f"{gene} {hgvs}".strip(),
                        "url": f"https://www.ncbi.nlm.nih.gov/snp/{vid}"
                        if str(vid).startswith("rs")
                        else "",
                    }
                )
        return out[:n]
