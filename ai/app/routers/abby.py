"""
Abby AI router — clinical case analysis and page-aware conversational assistant.

Abby uses MedGemma (via Ollama) as the reasoning backbone:
  - /abby/analyze    → NL clinical case description → structured analysis JSON
  - /abby/chat       → page-aware conversational Q&A
  - /abby/chat/stream → SSE streaming version of chat

The analysis JSON is designed to be consumed by the Laravel backend,
which resolves concepts via SapBERT and assembles the clinical case summary.
"""

import json
import logging
import os
import re
from pathlib import Path
from typing import Any, AsyncGenerator

import httpx
from fastapi import APIRouter, HTTPException
from fastapi.responses import StreamingResponse
from pydantic import BaseModel, Field, model_validator

from app.config import settings

logger = logging.getLogger(__name__)
router = APIRouter()


# ── Session-scoped working memory (in-memory, cleared on service restart) ────

_session_state: dict[int, dict] = {}
_SESSION_MAX_SIZE = 1000


def _get_session(conversation_id: int | None) -> dict:
    """Get or create session state for a conversation."""
    if conversation_id is None:
        return {"topics": [], "turn": 0}
    if conversation_id not in _session_state:
        # Evict oldest entry if at capacity
        if len(_session_state) >= _SESSION_MAX_SIZE:
            oldest_key = next(iter(_session_state))
            del _session_state[oldest_key]
        _session_state[conversation_id] = {
            "topics": [],
            "turn": 0,
        }
    return _session_state[conversation_id]


# ── Pydantic models ──────────────────────────────────────────────────────────

class AnalyzeRequest(BaseModel):
    prompt: str = Field(..., min_length=5, max_length=3000,
                        description="Natural language clinical case description")
    page_context: str = Field(default="case-review",
                              description="Current UI page the user is on")


class ClinicalFinding(BaseModel):
    text: str
    domain: str        # condition | drug | procedure | measurement | observation
    role: str          # primary | secondary | comorbidity | contraindication
    negated: bool = False


class PatientDemographics(BaseModel):
    sex: list[str] = []          # ['Female'] | ['Male'] | []
    age_min: int | None = None
    age_max: int | None = None
    race: list[str] = []
    ethnicity: list[str] = []


class TemporalContext(BaseModel):
    onset_days: int | None = None       # days since onset
    duration_days: int | None = None    # duration of condition
    followup_days: int | None = None    # recommended follow-up


class AnalyzeResponse(BaseModel):
    case_title: str
    case_summary: str
    demographics: PatientDemographics
    findings: list[ClinicalFinding]
    temporal: TemporalContext
    case_type: str             # tumor_board | mdr | consultation | follow_up
    urgency: str               # emergent | urgent | routine | elective
    confidence: float          # 0-1, LLM self-assessment of analysis quality
    recommended_specialties: list[str] = []
    warnings: list[str] = []
    raw_llm_output: str = ""   # for debug / transparency


class ChatMessage(BaseModel):
    role: str   # 'user' | 'assistant'
    content: str


class ResearchProfile(BaseModel):
    """Learned research profile for personalization."""
    research_interests: list[str] | None = []
    expertise_domains: dict[str, float] | None = {}
    interaction_preferences: dict | None = {}
    frequently_used: dict | None = {}
    interaction_count: int | None = 0

    model_config = {"populate_by_name": True}

    @model_validator(mode="before")
    @classmethod
    def coerce_nulls(cls, data: Any) -> Any:
        """Coerce None/empty-list to correct empty defaults.

        PHP serialises empty arrays as [] regardless of whether the column
        is a list or a JSON object, so dict fields may arrive as [].
        """
        if isinstance(data, dict):
            dict_fields = {"expertise_domains", "interaction_preferences", "frequently_used"}
            result: dict[str, object] = {}
            for k, v in data.items():
                if v is None:
                    result[k] = [] if k == "research_interests" else ({} if k in dict_fields else 0)
                elif k in dict_fields and isinstance(v, list):
                    result[k] = {}  # [] -> {}
                else:
                    result[k] = v
            return result
        return data


class UserProfile(BaseModel):
    name: str = ""
    roles: list[str] = []
    research_profile: ResearchProfile = ResearchProfile()


class ChatRequest(BaseModel):
    message: str = Field(..., min_length=1, max_length=4000)
    page_context: str = Field(
        default="general",
        description="UI page context for Abby to tailor responses"
    )
    page_data: dict[str, Any] = Field(
        default_factory=dict,
        description="Relevant page entity data (case name, current filters, etc.)"
    )
    history: list[ChatMessage] = Field(
        default_factory=list,
        description="Prior conversation turns (last 10 recommended)"
    )
    user_profile: UserProfile | None = Field(
        default=None,
        description="Current user info for personalized responses"
    )
    user_id: int | None = Field(
        default=None,
        description="Current user ID for personalized conversation memory"
    )
    conversation_id: int | None = Field(
        default=None,
        description="Conversation ID for session memory tracking"
    )


class ChatResponse(BaseModel):
    reply: str
    suggestions: list[str] = []   # quick-action prompts the UI can surface as chips
    routing: dict = {}
    confidence: str = ""
    sources: list[dict] = []


# ── Ollama helpers ───────────────────────────────────────────────────────────

SYSTEM_PROMPT_CASE_ANALYZER = """\
You are Abby, a clinical intelligence assistant for the Aurora clinical case management platform.

Your task is to parse a clinician's natural-language case description into a structured JSON object
suitable for tumor board review, multidisciplinary discussion, and clinical decision support.

RULES:
1. Output ONLY valid JSON — no markdown fences, no prose before or after.
2. Use the exact schema below.
3. For "findings", classify each clinical entity:
   - domain: condition | drug | procedure | measurement | observation
   - role: primary (main diagnosis) | secondary (related condition) | comorbidity | contraindication
4. For demographics: extract sex, age range, race, ethnicity.
5. For case_type: tumor_board | mdr (multidisciplinary review) | consultation | follow_up
6. For urgency: emergent | urgent | routine | elective
7. Set confidence between 0.0 (very uncertain) and 1.0 (clear, complete description).
8. Add warnings for ambiguous terms or missing critical information.
9. Suggest recommended_specialties for multidisciplinary review.

OUTPUT SCHEMA:
{
  "case_title": "Short descriptive title for the case",
  "case_summary": "One-paragraph clinical summary",
  "demographics": {
    "sex": [],
    "age_min": null,
    "age_max": null,
    "race": [],
    "ethnicity": []
  },
  "findings": [
    {"text": "breast cancer", "domain": "condition", "role": "primary", "negated": false}
  ],
  "temporal": {
    "onset_days": null,
    "duration_days": null,
    "followup_days": null
  },
  "case_type": "tumor_board",
  "urgency": "routine",
  "confidence": 0.92,
  "recommended_specialties": ["oncology", "radiology"],
  "warnings": []
}
"""

PAGE_SYSTEM_PROMPTS: dict[str, str] = {
    "case_review": (
        "You are Abby, a clinical intelligence assistant for the Aurora platform. "
        "The user is reviewing a clinical case for tumor board or multidisciplinary discussion. "
        "Help them analyze findings, suggest differential diagnoses, recommend imaging or labs, "
        "and prepare case presentations. Be concise and evidence-based."
    ),
    "case_list": (
        "You are Abby. The user is viewing the list of clinical cases. "
        "Help them prioritize cases, understand urgency levels, "
        "and identify cases requiring immediate multidisciplinary review."
    ),
    "patient_profile": (
        "You are Abby. The user is viewing an individual patient profile and clinical timeline. "
        "Help them interpret the clinical events, identify care gaps, understand medication "
        "interactions, and prepare for clinical discussions. Highlight any concerning trends."
    ),
    "tumor_board": (
        "You are Abby. The user is preparing for or conducting a tumor board session. "
        "Help them organize case presentations, review staging criteria, suggest treatment "
        "options based on guidelines (NCCN, ASCO), and document recommendations."
    ),
    "imaging": (
        "You are Abby. The user is reviewing medical imaging studies. "
        "Help with study interpretation context, modality selection guidance (CT, MRI, X-ray, US), "
        "report findings extraction, and correlating imaging with clinical history."
    ),
    "lab_results": (
        "You are Abby. The user is reviewing laboratory results. "
        "Help them interpret lab values in clinical context, identify critical values, "
        "explain trends, and suggest follow-up testing."
    ),
    "medications": (
        "You are Abby. The user is reviewing medication lists or prescribing. "
        "Help with drug interactions, dosing guidance, formulary alternatives, "
        "contraindication checks, and medication reconciliation."
    ),
    "clinical_notes": (
        "You are Abby. The user is working with clinical documentation. "
        "Help them extract key information from notes, summarize histories, "
        "identify relevant findings, and structure clinical narratives."
    ),
    "multidisciplinary_review": (
        "You are Abby. The user is in a multidisciplinary review session. "
        "Help coordinate input from multiple specialties, track action items, "
        "document consensus decisions, and prepare follow-up plans."
    ),
    "quality_metrics": (
        "You are Abby. The user is reviewing clinical quality metrics and outcomes. "
        "Help them interpret performance indicators, identify improvement opportunities, "
        "benchmark against standards, and design quality improvement initiatives."
    ),
    "administration": (
        "You are Abby. The user is in the Administration panel. "
        "Help them configure authentication providers, manage user roles and permissions, "
        "set up AI providers, check system health, and manage clinical workflows."
    ),
    "dashboard": (
        "You are Abby, a clinical intelligence assistant for the Aurora clinical case "
        "management platform. The user is on the main dashboard. Help them navigate "
        "to the right module for their task, understand platform metrics, "
        "and get started with their clinical workflow."
    ),
    "general": (
        "You are Abby, a clinical intelligence assistant for the Aurora clinical case "
        "management platform. Help the user with any question about clinical cases, "
        "tumor boards, multidisciplinary review, patient management, or the Aurora application."
    ),
}


# ── Help content knowledge base ──────────────────────────────────────────────

# Map page context -> help JSON keys to inject as knowledge
CONTEXT_HELP_KEYS: dict[str, list[str]] = {
    "case_review": ["case-review", "case-review.findings", "case-review.recommendations"],
    "case_list": ["case-list"],
    "patient_profile": ["patient-profile", "patient-timeline"],
    "tumor_board": ["tumor-board", "tumor-board.staging", "tumor-board.guidelines"],
    "imaging": ["imaging", "imaging.modalities"],
    "lab_results": ["lab-results", "lab-results.critical-values"],
    "medications": ["medications", "medications.interactions"],
    "clinical_notes": ["clinical-notes"],
    "multidisciplinary_review": ["mdr", "mdr.action-items"],
    "quality_metrics": ["quality-metrics"],
    "administration": ["admin", "admin.users", "admin.roles"],
    "dashboard": ["dashboard"],
}

HELP_CONTENT: dict[str, dict[str, Any]] = {}


def _load_help_files() -> None:
    """Load help JSON files from the backend resources directory."""
    help_dir = Path(os.environ.get("HELP_DIR", "/var/www/html/resources/help"))
    if not help_dir.exists():
        # Try relative path for local development
        alt_dir = Path(__file__).parent.parent.parent.parent / "backend" / "resources" / "help"
        if alt_dir.exists():
            help_dir = alt_dir
        else:
            logger.warning("Help directory not found: %s", help_dir)
            return

    for f in help_dir.glob("*.json"):
        try:
            data = json.loads(f.read_text())
            key = data.get("key", f.stem)
            HELP_CONTENT[key] = data
        except (json.JSONDecodeError, OSError) as e:
            logger.warning("Failed to load help file %s: %s", f, e)

    logger.info("Loaded %d help files for Abby", len(HELP_CONTENT))


# Load at module import time
_load_help_files()


def _get_help_context(page_context: str) -> str:
    """Build a help knowledge section for the given page context."""
    keys = CONTEXT_HELP_KEYS.get(page_context, [])
    if not keys:
        return ""

    sections = []
    for key in keys:
        data = HELP_CONTENT.get(key)
        if not data:
            continue
        title = data.get("title", key)
        desc = data.get("description", "")
        tips = data.get("tips", [])
        tip_text = "\n".join(f"  - {t}" for t in tips[:5]) if tips else ""
        section = f"### {title}\n{desc}"
        if tip_text:
            section += f"\nKey tips:\n{tip_text}"
        sections.append(section)

    if not sections:
        return ""

    return "\n\nFEATURE DOCUMENTATION:\n" + "\n\n".join(sections)


async def call_ollama(system_prompt: str, user_message: str,
                      history: list[ChatMessage] | None = None,
                      temperature: float = 0.1) -> str:
    """Call Ollama with the configured MedGemma model."""
    messages = [{"role": "system", "content": system_prompt}]

    if history:
        for msg in history[-10:]:  # cap at last 10 turns
            messages.append({"role": msg.role, "content": msg.content})

    messages.append({"role": "user", "content": user_message})

    # First attempt uses a longer timeout to accommodate cold model loads or
    # model swapping (e.g. evicting a large model takes >90s).
    # Subsequent retries use a shorter timeout since the model should be warm.
    max_retries = 2

    for attempt in range(max_retries + 1):
        attempt_timeout = 180 if attempt == 0 else 60
        try:
            async with httpx.AsyncClient(timeout=attempt_timeout) as client:
                resp = await client.post(
                    f"{settings.ollama_base_url}/api/chat",
                    json={
                        "model": settings.ollama_model,
                        "messages": messages,
                        "stream": False,
                        "keep_alive": 3600,  # keep warm for 1 hour
                        "options": {"temperature": temperature},
                    },
                )
                resp.raise_for_status()
                data = resp.json()
                return data["message"]["content"]  # type: ignore[no-any-return]
        except httpx.TimeoutException:
            if attempt < max_retries:
                logger.warning("Ollama attempt %d/%d timed out, retrying...", attempt + 1, max_retries + 1)
                continue
            raise HTTPException(status_code=504, detail="LLM service timed out after retries.")
        except httpx.HTTPStatusError as e:
            if e.response.status_code == 500 and attempt < max_retries:
                logger.warning("Ollama returned 500 on attempt %d, retrying...", attempt + 1)
                continue
            raise HTTPException(status_code=503, detail=f"LLM service error: {e}")
        except Exception as e:
            logger.error("Ollama call failed: %s", e)
            raise HTTPException(status_code=503, detail=f"LLM service unavailable: {e}")

    raise HTTPException(status_code=503, detail="LLM service unavailable: all retries exhausted")


# ── Endpoints ────────────────────────────────────────────────────────────────

@router.post("/analyze", response_model=AnalyzeResponse)
async def analyze_case(request: AnalyzeRequest) -> AnalyzeResponse:
    """
    Parse a natural-language clinical case description into a structured analysis.
    The Laravel backend uses this to resolve concepts, prepare tumor board
    presentations, and assemble multidisciplinary review documents.
    """
    raw = await call_ollama(
        system_prompt=SYSTEM_PROMPT_CASE_ANALYZER,
        user_message=request.prompt,
        temperature=0.05,   # near-deterministic for structured output
    )

    # Strip any accidental markdown fences
    clean = raw.strip()
    if clean.startswith("```"):
        clean = clean.split("```")[1]
        if clean.startswith("json"):
            clean = clean[4:]
        clean = clean.strip()

    try:
        parsed = json.loads(clean)
    except json.JSONDecodeError as e:
        logger.warning("LLM returned non-JSON output: %s\n%s", e, raw)
        # Return a minimal fallback
        return AnalyzeResponse(
            case_title="Unstructured Case",
            case_summary=request.prompt[:200],
            demographics=PatientDemographics(),
            findings=[],
            temporal=TemporalContext(),
            case_type="consultation",
            urgency="routine",
            confidence=0.0,
            recommended_specialties=[],
            warnings=["LLM could not parse the description into structured JSON. Falling back to manual review."],
            raw_llm_output=raw,
        )

    # Map parsed dict -> response model (with defaults for any missing keys)
    demo_raw = parsed.get("demographics", {})
    temporal_raw = parsed.get("temporal", {})

    return AnalyzeResponse(
        case_title=parsed.get("case_title", "Untitled Case"),
        case_summary=parsed.get("case_summary", ""),
        demographics=PatientDemographics(
            sex=demo_raw.get("sex", []),
            age_min=demo_raw.get("age_min"),
            age_max=demo_raw.get("age_max"),
            race=demo_raw.get("race", []),
            ethnicity=demo_raw.get("ethnicity", []),
        ),
        findings=[
            ClinicalFinding(
                text=t.get("text", ""),
                domain=t.get("domain", "condition"),
                role=t.get("role", "primary"),
                negated=t.get("negated", False),
            )
            for t in parsed.get("findings", [])
        ],
        temporal=TemporalContext(
            onset_days=temporal_raw.get("onset_days"),
            duration_days=temporal_raw.get("duration_days"),
            followup_days=temporal_raw.get("followup_days"),
        ),
        case_type=parsed.get("case_type", "consultation"),
        urgency=parsed.get("urgency", "routine"),
        confidence=float(parsed.get("confidence", 0.5)),
        recommended_specialties=parsed.get("recommended_specialties", []),
        warnings=parsed.get("warnings", []),
        raw_llm_output=raw,
    )


def _build_chat_system_prompt(request: ChatRequest) -> str:
    """Build the system prompt for a chat request.

    Context enrichment steps (each only injected when relevant):
      1. Help knowledge — static help docs for the current page context
      2. Page data — entity-specific data passed from the frontend
      3. User profile — personalization based on user roles and expertise
    """
    system_prompt = PAGE_SYSTEM_PROMPTS.get(
        request.page_context, PAGE_SYSTEM_PROMPTS["general"]
    )

    # -- Step 1: Help knowledge (static, page-specific) ────────────────────
    help_context = _get_help_context(request.page_context)
    if help_context:
        system_prompt += help_context

    # -- Step 2: User profile context ──────────────────────────────────────
    if request.user_profile and request.user_profile.name:
        role_str = ", ".join(request.user_profile.roles) if request.user_profile.roles else "clinician"
        system_prompt += (
            f"\n\nYou are assisting {request.user_profile.name}, "
            f"who has roles: {role_str}."
        )

    # User research/expertise profile context
    if request.user_profile and request.user_profile.research_profile:
        rp = request.user_profile.research_profile
        profile_parts = []
        if rp.research_interests:
            profile_parts.append(f"Research interests: {', '.join(rp.research_interests)}")
        if rp.expertise_domains:
            top_domains = sorted(rp.expertise_domains.items(), key=lambda x: x[1], reverse=True)[:5]
            profile_parts.append(f"Expertise: {', '.join(d for d, _ in top_domains)}")
        if profile_parts:
            system_prompt += f"\n\nUSER PROFILE: {'; '.join(profile_parts)}"

    # -- Step 3: Page data (entity-specific frontend context) ──────────────
    if request.page_data:
        context_lines = []
        for key, val in request.page_data.items():
            if isinstance(val, (str, int, float, bool)):
                context_lines.append(f"  {key}: {val}")
            elif isinstance(val, list) and len(val) <= 5:
                context_lines.append(f"  {key}: {', '.join(str(v) for v in val)}")
        if context_lines:
            system_prompt += "\n\nCURRENT PAGE CONTEXT:\n" + "\n".join(context_lines)

    # -- Grounding rules ──────────────────────────────────────────────────
    system_prompt += (
        "\n\nGROUNDING RULES:"
        "\n- Base your answer on established clinical evidence and guidelines."
        "\n- When citing specific patient data, use ONLY the data from the CURRENT PAGE CONTEXT provided above."
        "\n- When citing studies, guidelines, or clinical evidence, be specific and accurate. Do NOT fabricate paper titles, author names, or study details."
        "\n- If the provided context does not contain enough information, say so explicitly."
        "\n- You MAY use your general medical training knowledge for explanations, definitions, and context — but NEVER fabricate specific clinical claims."
    )

    system_prompt += (
        "\n\nRESPONSE FORMAT:"
        "\n- Keep replies concise (under 300 words)."
        "\n- Use markdown formatting for headers, lists, and code blocks."
        "\n- End your reply with 1-3 next-step action prompts the user could send you"
        " to make progress toward their goal within Aurora."
        " These are things the USER would TYPE TO YOU — short imperative commands or"
        " specific questions directed at you, NOT questions you are asking the user."
        " Good examples: \"Summarize this case for tumor board\","
        " \"Check for drug interactions with current medications\","
        " \"Suggest additional workup for this presentation\"."
        " Bad examples: \"Would you like to explore treatment options?\","
        " \"Are you interested in specific lab results?\" (those are you asking the user)."
        '\n- Format as a JSON array on the last line: SUGGESTIONS: ["...", "...", "..."]'
    )

    return system_prompt


def _strip_thinking_tokens(text: str) -> str:
    """Strip MedGemma's internal thinking/reasoning tokens from output.

    MedGemma uses <unused94>thought...content<unused95> for chain-of-thought.
    These tokens should never reach the user.
    """
    # Remove <unused94>thought....<unused95> blocks (thinking tokens)
    text = re.sub(r"<unused94>.*?<unused95>", "", text, flags=re.DOTALL)
    # Remove orphaned thinking markers
    text = re.sub(r"<unused\d+>", "", text)
    return text.strip()


def _extract_suggestions(raw: str) -> tuple[str, list[str]]:
    """Extract suggestion chips from the LLM reply and clean output.

    Handles two formats:
      1. JSON array (instructed format):
            SUGGESTIONS: ["What next?", "How to fix?"]
      2. Singular plain-text lines (what MedGemma actually produces):
            Suggestion: Would you like to explore treatment options?
            Suggestion: Are you interested in specific medications?
    """
    suggestions: list[str] = []
    reply = _strip_thinking_tokens(raw.strip())

    # -- Format 1: SUGGESTIONS: ["...", "..."] ────────────────────────────
    if "SUGGESTIONS:" in reply:
        parts = reply.rsplit("SUGGESTIONS:", 1)
        reply = parts[0].strip()
        try:
            suggestions = json.loads(parts[1].strip())
            if not isinstance(suggestions, list):
                suggestions = []
        except (json.JSONDecodeError, IndexError):
            suggestions = []
        return reply, suggestions[:3]

    # -- Format 2: Suggestion: text  (MedGemma's actual output) ───────────
    suggestion_pattern = re.compile(r"Suggestion:\s*(.+?)(?=Suggestion:|$)", re.IGNORECASE | re.DOTALL)
    matches = suggestion_pattern.findall(reply)
    if matches:
        suggestions = [m.strip().rstrip("?. ") + "?" if not m.strip().endswith("?") else m.strip()
                       for m in matches]
        # Strip all Suggestion: lines from the reply body
        reply = re.sub(r"\s*Suggestion:\s*.+?(?=Suggestion:|$)", "", reply,
                       flags=re.IGNORECASE | re.DOTALL).strip()

    return reply, suggestions[:3]


@router.post("/chat", response_model=ChatResponse)
async def chat(request: ChatRequest) -> ChatResponse:
    """
    Page-aware conversational endpoint. Abby adapts her persona and focus
    based on the current UI page and any entity data passed from the frontend.

    Routes to MedGemma (local) by default. Cloud routing (Claude) can be
    added in a future phase for complex queries.
    """
    system_prompt = _build_chat_system_prompt(request)

    # Working memory: track topic and update turn counter
    session = _get_session(request.conversation_id)
    session["turn"] += 1
    turn = session["turn"]

    # Track topic from the message
    msg_lower = request.message.lower()
    topic = request.message[:80]

    # Simple domain keyword detection for topic tracking
    domain_keywords = {
        "oncology": ["cancer", "tumor", "neoplasm", "metastasis", "staging", "chemo"],
        "cardiology": ["heart", "cardiac", "ecg", "arrhythmia", "hypertension", "mi"],
        "pulmonology": ["lung", "respiratory", "copd", "asthma", "pneumonia", "ventilat"],
        "nephrology": ["kidney", "renal", "dialysis", "creatinine", "gfr", "proteinuria"],
        "neurology": ["brain", "neuro", "stroke", "seizure", "neuropathy", "dementia"],
        "endocrinology": ["diabetes", "thyroid", "insulin", "hba1c", "adrenal", "pituitary"],
        "gastroenterology": ["liver", "gi", "gastro", "hepat", "pancrea", "bowel"],
        "hematology": ["blood", "anemia", "coagulation", "platelet", "leukemia", "lymphoma"],
        "infectious_disease": ["infection", "sepsis", "antibiotic", "viral", "bacterial", "fungal"],
    }

    detected_topics = [domain for domain, keywords in domain_keywords.items()
                       if any(kw in msg_lower for kw in keywords)]
    if detected_topics:
        topic = detected_topics[0]

    # Keep last 10 topics in session
    session["topics"].append(topic)
    if len(session["topics"]) > 10:
        session["topics"] = session["topics"][-10:]

    # Local path: MedGemma via Ollama
    raw = await call_ollama(
        system_prompt=system_prompt,
        user_message=request.message,
        history=request.history,
        temperature=0.15,
    )
    reply, suggestions = _extract_suggestions(raw)

    confidence = "medium"

    return ChatResponse(
        reply=reply,
        suggestions=suggestions,
        routing={
            "model": "local",
            "reason": "default",
            "stage": 0,
        },
        confidence=confidence,
        sources=[],
    )


async def _stream_ollama(system_prompt: str, user_message: str,
                         history: list[ChatMessage] | None = None,
                         temperature: float = 0.3) -> AsyncGenerator[str, None]:
    """Stream tokens from Ollama as SSE events."""
    messages = [{"role": "system", "content": system_prompt}]
    if history:
        for msg in history[-10:]:
            messages.append({"role": msg.role, "content": msg.content})
    messages.append({"role": "user", "content": user_message})

    try:
        async with httpx.AsyncClient(timeout=settings.ollama_timeout) as client:
            async with client.stream(
                "POST",
                f"{settings.ollama_base_url}/api/chat",
                json={
                    "model": settings.ollama_model,
                    "messages": messages,
                    "stream": True,
                    "options": {"temperature": temperature},
                },
            ) as resp:
                resp.raise_for_status()
                full_content = ""
                async for line in resp.aiter_lines():
                    if not line.strip():
                        continue
                    try:
                        data = json.loads(line)
                        if data.get("done"):
                            break
                        token = data.get("message", {}).get("content", "")
                        if token:
                            full_content += token
                            yield f"data: {json.dumps({'token': token})}\n\n"
                    except json.JSONDecodeError:
                        continue

                # Extract suggestions from complete response
                _, suggestions = _extract_suggestions(full_content)
                if suggestions:
                    yield f"data: {json.dumps({'suggestions': suggestions})}\n\n"
                yield "data: [DONE]\n\n"
    except httpx.TimeoutException:
        yield f"data: {json.dumps({'error': 'LLM service timed out.'})}\n\n"
        yield "data: [DONE]\n\n"
    except Exception as e:
        logger.error("Ollama streaming failed: %s", e)
        yield f"data: {json.dumps({'error': f'LLM service unavailable: {e}'})}\n\n"
        yield "data: [DONE]\n\n"


@router.post("/chat/stream")
async def chat_stream(request: ChatRequest) -> StreamingResponse:
    """
    SSE streaming version of the chat endpoint. Returns token-by-token
    responses as Server-Sent Events for real-time display in the UI.
    """
    system_prompt = _build_chat_system_prompt(request)

    return StreamingResponse(
        _stream_ollama(
            system_prompt=system_prompt,
            user_message=request.message,
            history=request.history,
            temperature=0.3,
        ),
        media_type="text/event-stream",
        headers={
            "Cache-Control": "no-cache",
            "Connection": "keep-alive",
            "X-Accel-Buffering": "no",
        },
    )
