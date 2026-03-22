"""Workflow Templates — pre-built step sequences for common clinical case intelligence patterns.

Each template method returns a list of step dicts compatible with
:meth:`~app.agency.plan_engine.PlanEngine.create_plan`.  Templates capture
institutional knowledge about the correct order of operations for standard
clinical case workflows so users and the LLM do not need to build plans from
scratch.
"""
from __future__ import annotations

from typing import Any


class WorkflowTemplates:
    """Static factory methods for common Aurora clinical workflow step sequences.

    All methods return a list of step dicts.  Each dict has at minimum:

    * ``tool_name`` (str) — a registered tool name.
    * ``parameters`` (dict) — parameters for that tool.
    * ``step_id`` (str) — a short human-readable identifier.
    * ``depends_on`` (list[str]) — IDs of prerequisite steps.
    """

    # ------------------------------------------------------------------
    # Templates
    # ------------------------------------------------------------------

    @staticmethod
    def case_review(
        case_id: int,
        session_title: str,
        team_member_ids: list[int] | None = None,
        decision_type: str = "treatment_plan",
    ) -> list[dict[str, Any]]:
        """Generate steps for a full clinical case review workflow.

        Workflow:
        1. Look up the case to verify it exists and load details.
        2. Create a review session for the case.
        3. Add team members to the session (if provided).
        4. Run a patient analysis for the case's patient.
        5. Propose a clinical decision based on the analysis.

        Parameters
        ----------
        case_id:
            ID of the clinical case to review.
        session_title:
            Title for the review session.
        team_member_ids:
            Optional list of user IDs to add to the case team.
        decision_type:
            Type of decision to propose (default ``"treatment_plan"``).

        Returns
        -------
        list[dict[str, Any]]
            Ordered list of step dicts (>= 3 steps).
        """
        steps: list[dict[str, Any]] = []

        # Step 1 — case lookup
        steps.append({
            "step_id": "case_lookup",
            "tool_name": "case_lookup",
            "parameters": {"case_id": case_id},
            "depends_on": [],
        })

        # Step 2 — create review session
        session_params: dict[str, Any] = {
            "case_id": case_id,
            "title": session_title,
        }
        steps.append({
            "step_id": "session_create",
            "tool_name": "session_create",
            "parameters": session_params,
            "depends_on": ["case_lookup"],
        })

        # Step 3 — add team members (if provided)
        if team_member_ids:
            for i, user_id in enumerate(team_member_ids):
                steps.append({
                    "step_id": f"team_add_member_{i}",
                    "tool_name": "team_add_member",
                    "parameters": {
                        "team_id": None,  # resolved at runtime from case
                        "user_id": user_id,
                    },
                    "depends_on": ["session_create"],
                })

        # Step 4 — run patient analysis
        steps.append({
            "step_id": "run_patient_analysis",
            "tool_name": "run_patient_analysis",
            "parameters": {
                "patient_id": None,  # resolved at runtime from case
                "analysis_type": "comprehensive",
                "name": f"Analysis for case {case_id}",
            },
            "depends_on": ["case_lookup"],
        })

        # Step 5 — propose decision
        steps.append({
            "step_id": "decision_propose",
            "tool_name": "decision_propose",
            "parameters": {
                "case_id": case_id,
                "decision_type": decision_type,
                "summary": None,  # resolved at runtime from analysis
                "rationale": None,  # resolved at runtime from analysis
            },
            "depends_on": ["run_patient_analysis"],
        })

        return steps

    @staticmethod
    def patient_risk_assessment(
        patient_id: int,
        risk_model: str = "comprehensive",
        include_case_notes: bool = True,
    ) -> list[dict[str, Any]]:
        """Generate steps for a patient risk assessment workflow.

        Workflow:
        1. Search for the patient to verify they exist.
        2. Run a comprehensive patient analysis.
        3. Run a risk assessment using the specified model.
        4. Export the results.

        Parameters
        ----------
        patient_id:
            ID of the patient to assess.
        risk_model:
            Risk model to use (default ``"comprehensive"``).
        include_case_notes:
            Whether to create a clinical note summarizing findings.

        Returns
        -------
        list[dict[str, Any]]
            Ordered list of step dicts (>= 3 steps).
        """
        steps: list[dict[str, Any]] = []

        # Step 1 — patient search / verification
        steps.append({
            "step_id": "patient_search",
            "tool_name": "patient_search",
            "parameters": {
                "query": str(patient_id),
                "filters": {"patient_id": patient_id},
            },
            "depends_on": [],
        })

        # Step 2 — patient analysis
        steps.append({
            "step_id": "run_patient_analysis",
            "tool_name": "run_patient_analysis",
            "parameters": {
                "patient_id": patient_id,
                "analysis_type": "comprehensive",
                "include_sections": [
                    "conditions", "medications", "procedures",
                    "lab_results", "vital_signs",
                ],
                "name": f"Full analysis for patient {patient_id}",
            },
            "depends_on": ["patient_search"],
        })

        # Step 3 — risk assessment
        steps.append({
            "step_id": "run_risk_assessment",
            "tool_name": "run_risk_assessment",
            "parameters": {
                "patient_id": patient_id,
                "risk_model": risk_model,
                "name": f"Risk assessment for patient {patient_id}",
            },
            "depends_on": ["run_patient_analysis"],
        })

        # Step 4 — export results
        steps.append({
            "step_id": "export_results",
            "tool_name": "export_results",
            "parameters": {
                "entity_type": "analyses",
                "entity_id": None,  # resolved at runtime
                "format": "json",
            },
            "depends_on": ["run_risk_assessment"],
        })

        # Step 5 — optional clinical note
        if include_case_notes:
            steps.append({
                "step_id": "note_create",
                "tool_name": "note_create",
                "parameters": {
                    "patient_id": patient_id,
                    "note_type": "risk_assessment_summary",
                    "content": None,  # resolved at runtime from analysis
                },
                "depends_on": ["run_risk_assessment"],
            })

        return steps

    @staticmethod
    def case_comparison(
        case_a_id: int,
        case_b_id: int,
        export_format: str = "json",
    ) -> list[dict[str, Any]]:
        """Generate steps for comparing two clinical cases side-by-side.

        Workflow:
        1. Look up case A.
        2. Look up case B.
        3. Compare the two cases.
        4. Export the comparison results.

        Parameters
        ----------
        case_a_id:
            ID of the first case.
        case_b_id:
            ID of the second case.
        export_format:
            Export format for the comparison results (default ``"json"``).

        Returns
        -------
        list[dict[str, Any]]
            Ordered list of step dicts.
        """
        steps: list[dict[str, Any]] = []

        # Step 1 — look up case A
        steps.append({
            "step_id": "case_lookup_a",
            "tool_name": "case_lookup",
            "parameters": {"case_id": case_a_id},
            "depends_on": [],
        })

        # Step 2 — look up case B
        steps.append({
            "step_id": "case_lookup_b",
            "tool_name": "case_lookup",
            "parameters": {"case_id": case_b_id},
            "depends_on": [],
        })

        # Step 3 — compare cases
        steps.append({
            "step_id": "compare_cases",
            "tool_name": "compare_cases",
            "parameters": {
                "case_a_id": case_a_id,
                "case_b_id": case_b_id,
            },
            "depends_on": ["case_lookup_a", "case_lookup_b"],
        })

        # Step 4 — export comparison
        steps.append({
            "step_id": "export_results",
            "tool_name": "export_results",
            "parameters": {
                "entity_type": "case-comparisons",
                "entity_id": None,  # resolved at runtime
                "format": export_format,
            },
            "depends_on": ["compare_cases"],
        })

        return steps

    # ------------------------------------------------------------------
    # Discovery helpers
    # ------------------------------------------------------------------

    @staticmethod
    def list_templates() -> list[dict[str, str]]:
        """Return metadata for all available workflow templates.

        Returns
        -------
        list[dict[str, str]]
            Each entry has ``name`` and ``description`` keys.
        """
        return [
            {
                "name": "case_review",
                "description": (
                    "Full clinical case review: case lookup -> create session "
                    "-> add team -> patient analysis -> propose decision."
                ),
            },
            {
                "name": "patient_risk_assessment",
                "description": (
                    "Patient risk assessment: verify patient -> comprehensive "
                    "analysis -> risk model -> export results -> clinical note."
                ),
            },
            {
                "name": "case_comparison",
                "description": (
                    "Compare two clinical cases: look up both cases -> "
                    "side-by-side comparison -> export results."
                ),
            },
        ]

    @staticmethod
    def format_for_prompt() -> str:
        """Render a human-readable summary of available templates for LLM prompts.

        Returns
        -------
        str
            Multi-line string listing each template name and description.
        """
        templates = WorkflowTemplates.list_templates()
        lines: list[str] = ["Available workflow templates:"]
        for tmpl in templates:
            lines.append(f"  - {tmpl['name']}: {tmpl['description']}")
        return "\n".join(lines)
