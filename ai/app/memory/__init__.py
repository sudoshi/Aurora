"""
Abby 2.0 Memory Module — Aurora AI

Components:
- intent_stack: Working memory intent tracking across conversation turns
- scratch_pad: Session-scoped intermediate artifact storage
- profile_learner: Extracts user research profile from conversation patterns
- context_assembler: Ranked, budget-aware context assembly for LLM prompts
- conversation_store: PostgreSQL-backed conversation storage with vector search
- summarizer: Conversation compression for context window management
"""
