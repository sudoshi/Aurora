"""
Federation registry -- manages known peer Aurora instances.
Persisted to a JSON file, reloadable at runtime.
"""

import json
import logging
from dataclasses import dataclass, field, asdict
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

from config import settings

logger = logging.getLogger(__name__)


@dataclass
class PeerInstitution:
    """A registered peer Aurora instance in the federation."""

    id: str
    name: str
    endpoint_url: str
    public_key: str  # hex-encoded Ed25519 public key
    status: str = "active"  # active | suspended | revoked
    registered_at: str = ""
    last_seen_at: str = ""
    capabilities: list[str] = field(default_factory=lambda: ["similarity", "aggregate_stats"])

    def __post_init__(self) -> None:
        if not self.registered_at:
            self.registered_at = datetime.now(timezone.utc).isoformat()
        if self.status not in ("active", "suspended", "revoked"):
            raise ValueError(
                f"Invalid status '{self.status}'; "
                "must be one of: active, suspended, revoked"
            )

    def to_dict(self) -> dict[str, Any]:
        return asdict(self)

    @classmethod
    def from_dict(cls, data: dict[str, Any]) -> "PeerInstitution":
        return cls(**data)


class FederationRegistry:
    """In-memory registry of peer institutions, backed by a JSON file."""

    def __init__(self, registry_path: str | None = None) -> None:
        self._path = Path(registry_path or settings.registry_file)
        self._peers: dict[str, PeerInstitution] = {}
        self.load()

    def register_peer(self, institution: PeerInstitution) -> None:
        """Register a new peer institution or update an existing one."""
        if (
            len(self._peers) >= settings.max_peers
            and institution.id not in self._peers
        ):
            raise ValueError(
                f"Maximum peer limit ({settings.max_peers}) reached"
            )
        self._peers[institution.id] = institution
        self.save()
        logger.info("Registered peer: %s (%s)", institution.name, institution.id)

    def remove_peer(self, institution_id: str) -> None:
        """Remove a peer institution from the registry."""
        if institution_id not in self._peers:
            raise KeyError(f"Peer '{institution_id}' not found in registry")
        removed = self._peers.pop(institution_id)
        self.save()
        logger.info("Removed peer: %s (%s)", removed.name, institution_id)

    def get_active_peers(self) -> list[PeerInstitution]:
        """Return all peers with status 'active'."""
        return [p for p in self._peers.values() if p.status == "active"]

    def get_peer(self, institution_id: str) -> PeerInstitution | None:
        """Look up a peer by institution ID."""
        return self._peers.get(institution_id)

    def update_status(self, institution_id: str, status: str) -> None:
        """Update the status of a peer institution."""
        peer = self._peers.get(institution_id)
        if peer is None:
            raise KeyError(f"Peer '{institution_id}' not found in registry")
        if status not in ("active", "suspended", "revoked"):
            raise ValueError(
                f"Invalid status '{status}'; "
                "must be one of: active, suspended, revoked"
            )
        peer.status = status
        self.save()
        logger.info("Updated peer %s status to %s", institution_id, status)

    def update_last_seen(self, institution_id: str) -> None:
        """Update the last_seen_at timestamp for a peer."""
        peer = self._peers.get(institution_id)
        if peer is not None:
            peer.last_seen_at = datetime.now(timezone.utc).isoformat()
            self.save()

    def save(self) -> None:
        """Persist the registry to disk as JSON."""
        data = {pid: peer.to_dict() for pid, peer in self._peers.items()}
        try:
            self._path.write_text(
                json.dumps(data, indent=2, default=str), encoding="utf-8"
            )
        except OSError as exc:
            logger.error("Failed to save registry to %s: %s", self._path, exc)

    def load(self) -> None:
        """Load the registry from disk. Silently starts empty if file missing."""
        if not self._path.exists():
            logger.info("Registry file %s not found; starting with empty registry", self._path)
            self._peers = {}
            return

        try:
            raw = json.loads(self._path.read_text(encoding="utf-8"))
            self._peers = {
                pid: PeerInstitution.from_dict(pdata)
                for pid, pdata in raw.items()
            }
            logger.info("Loaded %d peers from %s", len(self._peers), self._path)
        except (json.JSONDecodeError, OSError, TypeError) as exc:
            logger.error("Failed to load registry from %s: %s", self._path, exc)
            self._peers = {}

    @property
    def peer_count(self) -> int:
        return len(self._peers)
