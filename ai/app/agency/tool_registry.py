"""Tool Registry — central catalogue of all agency tools with risk metadata.

Tools are registered with a RiskLevel that controls whether execution requires
explicit user confirmation before the Plan-Confirm-Execute engine proceeds.

Aurora tools are oriented around clinical case intelligence: case management,
patient search, team collaboration, decision proposals, and clinical analysis.
"""
from __future__ import annotations

from dataclasses import dataclass, field
from enum import Enum
from typing import Any


class RiskLevel(str, Enum):
    """Risk classification for agency tools.

    LOW
        Read-only or copy operations that are easy to reverse.
    MEDIUM
        Write operations that create new resources (reversible via delete).
    HIGH
        Destructive or irreversible mutations.
    """

    LOW = "low"
    MEDIUM = "medium"
    HIGH = "high"


@dataclass
class ToolDefinition:
    """Metadata for a single callable agency tool.

    Parameters
    ----------
    name:
        Unique snake_case identifier, e.g. ``"case_lookup"``.
    description:
        Human-readable explanation shown to users and the LLM.
    risk_level:
        Risk classification controlling confirmation requirements.
    requires_confirmation:
        If ``True`` (default), the plan engine will pause and ask the user to
        approve before executing this tool.
    rollback_capable:
        If ``True`` (default), the action logger records a checkpoint that can
        be used to undo the operation.
    parameters_schema:
        JSON-Schema dict describing accepted parameters (optional; used for
        prompt construction and validation).
    """

    name: str
    description: str
    risk_level: RiskLevel
    requires_confirmation: bool = True
    rollback_capable: bool = True
    parameters_schema: dict[str, Any] = field(default_factory=dict)


class ToolRegistry:
    """Central registry mapping tool names to :class:`ToolDefinition` objects.

    Usage::

        registry = ToolRegistry.default()
        tool = registry.get("case_lookup")
    """

    def __init__(self) -> None:
        self._tools: dict[str, ToolDefinition] = {}

    # ------------------------------------------------------------------
    # Mutation
    # ------------------------------------------------------------------

    def register(self, tool: ToolDefinition) -> None:
        """Add *tool* to the registry.

        If a tool with the same name already exists it will be overwritten.
        """
        self._tools[tool.name] = tool

    # ------------------------------------------------------------------
    # Query
    # ------------------------------------------------------------------

    def get(self, name: str) -> ToolDefinition | None:
        """Return the :class:`ToolDefinition` for *name*, or ``None``."""
        return self._tools.get(name)

    def list_tools(self) -> list[ToolDefinition]:
        """Return all registered tools in insertion order."""
        return list(self._tools.values())

    def list_by_risk(self, risk_level: RiskLevel) -> list[ToolDefinition]:
        """Return only tools whose ``risk_level`` matches *risk_level*."""
        return [t for t in self._tools.values() if t.risk_level == risk_level]

    def format_for_prompt(self) -> str:
        """Render a human-readable summary of all tools for LLM prompts.

        Each tool appears on its own line with its name, risk level, and
        short description.
        """
        lines: list[str] = ["Available agency tools:"]
        for tool in self._tools.values():
            lines.append(
                f"  - {tool.name} [{tool.risk_level.value}]: {tool.description}"
            )
        return "\n".join(lines)

    # ------------------------------------------------------------------
    # Factory
    # ------------------------------------------------------------------

    @classmethod
    def default(cls) -> "ToolRegistry":
        """Return a registry pre-loaded with all Aurora clinical case intelligence tools."""
        registry = cls()

        # ------------------------------------------------------------------
        # Read-only tools (LOW risk)
        # ------------------------------------------------------------------

        registry.register(ToolDefinition(
            name="case_lookup",
            description=(
                "Look up a clinical case by ID, returning case details, "
                "assigned team, and current status."
            ),
            risk_level=RiskLevel.LOW,
            requires_confirmation=False,
            rollback_capable=False,
            parameters_schema={
                "type": "object",
                "properties": {
                    "case_id": {"type": "integer"},
                },
                "required": ["case_id"],
            },
        ))

        registry.register(ToolDefinition(
            name="patient_search",
            description=(
                "Search for patients by name, MRN, date of birth, or other "
                "demographic criteria. Returns matching patient records."
            ),
            risk_level=RiskLevel.LOW,
            requires_confirmation=False,
            rollback_capable=False,
            parameters_schema={
                "type": "object",
                "properties": {
                    "query": {"type": "string"},
                    "filters": {"type": "object"},
                    "limit": {"type": "integer", "default": 20},
                },
                "required": ["query"],
            },
        ))

        registry.register(ToolDefinition(
            name="compare_cases",
            description=(
                "Retrieve and compare two clinical cases side-by-side, "
                "highlighting differences in diagnoses, medications, and outcomes."
            ),
            risk_level=RiskLevel.LOW,
            requires_confirmation=False,
            rollback_capable=False,
            parameters_schema={
                "type": "object",
                "properties": {
                    "case_a_id": {"type": "integer"},
                    "case_b_id": {"type": "integer"},
                },
                "required": ["case_a_id", "case_b_id"],
            },
        ))

        registry.register(ToolDefinition(
            name="export_results",
            description=(
                "Export case or analysis results to a downloadable format."
            ),
            risk_level=RiskLevel.LOW,
            requires_confirmation=False,
            rollback_capable=False,
            parameters_schema={
                "type": "object",
                "properties": {
                    "entity_type": {"type": "string"},
                    "entity_id": {"type": "integer"},
                    "format": {"type": "string", "enum": ["csv", "json", "pdf"]},
                },
                "required": ["entity_type", "entity_id"],
            },
        ))

        # ------------------------------------------------------------------
        # Write tools (MEDIUM risk) — create new resources
        # ------------------------------------------------------------------

        registry.register(ToolDefinition(
            name="session_create",
            description=(
                "Create a new clinical review session for a case, bringing "
                "together team members for collaborative decision-making."
            ),
            risk_level=RiskLevel.MEDIUM,
            requires_confirmation=True,
            rollback_capable=True,
            parameters_schema={
                "type": "object",
                "properties": {
                    "case_id": {"type": "integer"},
                    "title": {"type": "string"},
                    "scheduled_at": {"type": "string", "format": "date-time"},
                    "participants": {
                        "type": "array",
                        "items": {"type": "integer"},
                    },
                },
                "required": ["case_id", "title"],
            },
        ))

        registry.register(ToolDefinition(
            name="decision_propose",
            description=(
                "Propose a clinical decision for a case. The decision enters "
                "a pending state and requires team approval before being finalized."
            ),
            risk_level=RiskLevel.MEDIUM,
            requires_confirmation=True,
            rollback_capable=True,
            parameters_schema={
                "type": "object",
                "properties": {
                    "case_id": {"type": "integer"},
                    "decision_type": {"type": "string"},
                    "summary": {"type": "string"},
                    "rationale": {"type": "string"},
                    "evidence": {"type": "array", "items": {"type": "object"}},
                },
                "required": ["case_id", "decision_type", "summary"],
            },
        ))

        registry.register(ToolDefinition(
            name="team_add_member",
            description=(
                "Add a team member to a clinical case. The member receives "
                "access to case data and can participate in review sessions."
            ),
            risk_level=RiskLevel.MEDIUM,
            requires_confirmation=True,
            rollback_capable=True,
            parameters_schema={
                "type": "object",
                "properties": {
                    "team_id": {"type": "integer"},
                    "user_id": {"type": "integer"},
                    "role": {"type": "string"},
                },
                "required": ["team_id", "user_id"],
            },
        ))

        registry.register(ToolDefinition(
            name="note_create",
            description=(
                "Create a clinical note attached to a case or patient record."
            ),
            risk_level=RiskLevel.MEDIUM,
            requires_confirmation=True,
            rollback_capable=True,
            parameters_schema={
                "type": "object",
                "properties": {
                    "case_id": {"type": "integer"},
                    "patient_id": {"type": "integer"},
                    "note_type": {"type": "string"},
                    "content": {"type": "string"},
                },
                "required": ["content"],
            },
        ))

        # ------------------------------------------------------------------
        # Analysis tools (MEDIUM risk)
        # ------------------------------------------------------------------

        registry.register(ToolDefinition(
            name="run_patient_analysis",
            description=(
                "Run a comprehensive patient analysis including condition "
                "timeline, medication interactions, and risk factors. "
                "Runs asynchronously; returns an analysis ID."
            ),
            risk_level=RiskLevel.MEDIUM,
            requires_confirmation=True,
            rollback_capable=True,
            parameters_schema={
                "type": "object",
                "properties": {
                    "patient_id": {"type": "integer"},
                    "analysis_type": {"type": "string"},
                    "include_sections": {
                        "type": "array",
                        "items": {"type": "string"},
                    },
                    "name": {"type": "string"},
                },
                "required": ["patient_id"],
            },
        ))

        registry.register(ToolDefinition(
            name="run_risk_assessment",
            description=(
                "Run a clinical risk assessment for a patient, evaluating "
                "comorbidities, medication risks, and predictive indicators."
            ),
            risk_level=RiskLevel.MEDIUM,
            requires_confirmation=True,
            rollback_capable=True,
            parameters_schema={
                "type": "object",
                "properties": {
                    "patient_id": {"type": "integer"},
                    "risk_model": {"type": "string"},
                    "name": {"type": "string"},
                },
                "required": ["patient_id"],
            },
        ))

        # ------------------------------------------------------------------
        # High-risk tools
        # ------------------------------------------------------------------

        registry.register(ToolDefinition(
            name="execute_sql",
            description=(
                "Execute a validated read-only SQL query against the clinical "
                "database. Only SELECT statements are permitted; DML/DDL is blocked."
            ),
            risk_level=RiskLevel.HIGH,
            requires_confirmation=True,
            rollback_capable=False,
            parameters_schema={
                "type": "object",
                "properties": {
                    "query": {"type": "string"},
                    "schema": {"type": "string"},
                },
                "required": ["query"],
            },
        ))

        return registry
