"""
Tests for the federation relay service.

Covers registry CRUD, crypto signing/verification, health endpoint,
and query routing logic with mock peers.
"""

import json
import sys
from pathlib import Path
from unittest.mock import AsyncMock, patch

import pytest
from httpx import ASGITransport, AsyncClient

# Ensure federation package is importable
sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from config import FederationSettings
from crypto import (
    generate_institution_keypair,
    hash_patient_id,
    sign_message,
    verify_signature,
)
from registry import FederationRegistry, PeerInstitution


# ── Registry tests ───────────────────────────────────────────────────────────


class TestPeerInstitution:
    def test_create_peer(self):
        peer = PeerInstitution(
            id="inst-001",
            name="Test Hospital",
            endpoint_url="https://aurora.testhospital.org",
            public_key="aabbccdd",
        )
        assert peer.id == "inst-001"
        assert peer.name == "Test Hospital"
        assert peer.status == "active"
        assert peer.registered_at != ""
        assert "similarity" in peer.capabilities

    def test_invalid_status_raises(self):
        with pytest.raises(ValueError, match="Invalid status"):
            PeerInstitution(
                id="inst-002",
                name="Bad Hospital",
                endpoint_url="https://bad.org",
                public_key="aabb",
                status="invalid",
            )

    def test_to_dict_roundtrip(self):
        peer = PeerInstitution(
            id="inst-003",
            name="Roundtrip Hospital",
            endpoint_url="https://roundtrip.org",
            public_key="eeff",
        )
        data = peer.to_dict()
        restored = PeerInstitution.from_dict(data)
        assert restored.id == peer.id
        assert restored.name == peer.name
        assert restored.endpoint_url == peer.endpoint_url


class TestFederationRegistry:
    def test_register_and_get_peer(self, tmp_path):
        reg = FederationRegistry(str(tmp_path / "test_registry.json"))
        peer = PeerInstitution(
            id="inst-100",
            name="Alpha Hospital",
            endpoint_url="https://alpha.org",
            public_key="1234",
        )
        reg.register_peer(peer)
        assert reg.get_peer("inst-100") is not None
        assert reg.get_peer("inst-100").name == "Alpha Hospital"

    def test_remove_peer(self, tmp_path):
        reg = FederationRegistry(str(tmp_path / "test_registry.json"))
        peer = PeerInstitution(
            id="inst-101",
            name="Beta Hospital",
            endpoint_url="https://beta.org",
            public_key="5678",
        )
        reg.register_peer(peer)
        assert reg.peer_count == 1
        reg.remove_peer("inst-101")
        assert reg.peer_count == 0
        assert reg.get_peer("inst-101") is None

    def test_remove_nonexistent_raises(self, tmp_path):
        reg = FederationRegistry(str(tmp_path / "test_registry.json"))
        with pytest.raises(KeyError, match="not found"):
            reg.remove_peer("nonexistent")

    def test_get_active_peers(self, tmp_path):
        reg = FederationRegistry(str(tmp_path / "test_registry.json"))
        active_peer = PeerInstitution(
            id="inst-200",
            name="Active Hospital",
            endpoint_url="https://active.org",
            public_key="aaaa",
        )
        suspended_peer = PeerInstitution(
            id="inst-201",
            name="Suspended Hospital",
            endpoint_url="https://suspended.org",
            public_key="bbbb",
            status="suspended",
        )
        reg.register_peer(active_peer)
        reg.register_peer(suspended_peer)
        active = reg.get_active_peers()
        assert len(active) == 1
        assert active[0].id == "inst-200"

    def test_update_status(self, tmp_path):
        reg = FederationRegistry(str(tmp_path / "test_registry.json"))
        peer = PeerInstitution(
            id="inst-300",
            name="Status Hospital",
            endpoint_url="https://status.org",
            public_key="cccc",
        )
        reg.register_peer(peer)
        reg.update_status("inst-300", "suspended")
        assert reg.get_peer("inst-300").status == "suspended"

    def test_persistence(self, tmp_path):
        path = str(tmp_path / "persist_registry.json")
        reg1 = FederationRegistry(path)
        peer = PeerInstitution(
            id="inst-400",
            name="Persist Hospital",
            endpoint_url="https://persist.org",
            public_key="dddd",
        )
        reg1.register_peer(peer)

        # Load in a new instance
        reg2 = FederationRegistry(path)
        assert reg2.peer_count == 1
        assert reg2.get_peer("inst-400").name == "Persist Hospital"


# ── Crypto tests ─────────────────────────────────────────────────────────────


class TestCrypto:
    def test_generate_keypair(self):
        private_key, public_key = generate_institution_keypair()
        assert len(private_key) == 32
        assert len(public_key) == 32

    def test_sign_and_verify(self):
        private_key, public_key = generate_institution_keypair()
        message = b"test federation message"
        signature = sign_message(message, private_key)
        assert len(signature) == 64
        assert verify_signature(message, signature, public_key) is True

    def test_verify_wrong_message(self):
        private_key, public_key = generate_institution_keypair()
        message = b"original message"
        signature = sign_message(message, private_key)
        assert verify_signature(b"tampered message", signature, public_key) is False

    def test_verify_wrong_key(self):
        private_key1, _ = generate_institution_keypair()
        _, public_key2 = generate_institution_keypair()
        message = b"test message"
        signature = sign_message(message, private_key1)
        assert verify_signature(message, signature, public_key2) is False

    def test_hash_patient_id_deterministic(self):
        h1 = hash_patient_id(42, "inst-001")
        h2 = hash_patient_id(42, "inst-001")
        assert h1 == h2
        assert len(h1) == 64  # SHA-256 hex

    def test_hash_patient_id_different_institutions(self):
        h1 = hash_patient_id(42, "inst-001")
        h2 = hash_patient_id(42, "inst-002")
        assert h1 != h2  # Different salt produces different hash

    def test_hash_patient_id_different_patients(self):
        h1 = hash_patient_id(1, "inst-001")
        h2 = hash_patient_id(2, "inst-001")
        assert h1 != h2


# ── Relay API tests ──────────────────────────────────────────────────────────


@pytest.fixture
def _seed_registry(tmp_path, monkeypatch):
    """Seed a registry with test peers and patch settings."""
    registry_path = str(tmp_path / "test_relay_registry.json")
    monkeypatch.setattr("config.settings.registry_file", registry_path)

    # Reset the global registry
    import relay as relay_mod
    relay_mod._registry = None

    reg = FederationRegistry(registry_path)
    reg.register_peer(
        PeerInstitution(
            id="source-inst",
            name="Source Hospital",
            endpoint_url="https://source.org",
            public_key="aaaa",
        )
    )
    reg.register_peer(
        PeerInstitution(
            id="peer-inst",
            name="Peer Hospital",
            endpoint_url="https://peer.org",
            public_key="bbbb",
        )
    )
    return reg


@pytest.mark.asyncio
async def test_health_endpoint():
    """Health endpoint should return healthy status."""
    from relay import app

    transport = ASGITransport(app=app)
    async with AsyncClient(transport=transport, base_url="http://test") as client:
        resp = await client.get("/federation/health")
        assert resp.status_code == 200
        data = resp.json()
        assert data["status"] == "healthy"
        assert data["service"] == "Aurora Federation Relay"


@pytest.mark.asyncio
async def test_register_peer_endpoint(tmp_path, monkeypatch):
    """Register a new peer via the API."""
    registry_path = str(tmp_path / "api_registry.json")
    monkeypatch.setattr("config.settings.registry_file", registry_path)

    import relay as relay_mod
    relay_mod._registry = None

    from relay import app

    transport = ASGITransport(app=app)
    async with AsyncClient(transport=transport, base_url="http://test") as client:
        resp = await client.post(
            "/federation/peers/register",
            json={
                "id": "new-inst",
                "name": "New Hospital",
                "endpoint_url": "https://new.org",
                "public_key": "eeff1122",
            },
        )
        assert resp.status_code == 200
        data = resp.json()
        assert data["id"] == "new-inst"
        assert data["status"] == "active"


@pytest.mark.asyncio
async def test_remove_peer_endpoint(tmp_path, monkeypatch):
    """Remove a peer via the API."""
    registry_path = str(tmp_path / "remove_registry.json")
    monkeypatch.setattr("config.settings.registry_file", registry_path)

    import relay as relay_mod
    relay_mod._registry = None

    from relay import app

    transport = ASGITransport(app=app)
    async with AsyncClient(transport=transport, base_url="http://test") as client:
        # Register first
        await client.post(
            "/federation/peers/register",
            json={
                "id": "to-remove",
                "name": "Remove Hospital",
                "endpoint_url": "https://remove.org",
                "public_key": "dead",
            },
        )
        # Remove
        resp = await client.delete("/federation/peers/to-remove")
        assert resp.status_code == 200
        assert resp.json()["status"] == "removed"


@pytest.mark.asyncio
async def test_remove_nonexistent_peer_returns_404(tmp_path, monkeypatch):
    """Removing a nonexistent peer returns 404."""
    registry_path = str(tmp_path / "remove404_registry.json")
    monkeypatch.setattr("config.settings.registry_file", registry_path)

    import relay as relay_mod
    relay_mod._registry = None

    from relay import app

    transport = ASGITransport(app=app)
    async with AsyncClient(transport=transport, base_url="http://test") as client:
        resp = await client.delete("/federation/peers/nonexistent")
        assert resp.status_code == 404


@pytest.mark.asyncio
async def test_query_unregistered_institution_returns_403(tmp_path, monkeypatch):
    """Queries from unregistered institutions are rejected."""
    registry_path = str(tmp_path / "unregistered_registry.json")
    monkeypatch.setattr("config.settings.registry_file", registry_path)

    import relay as relay_mod
    relay_mod._registry = None

    from relay import app

    transport = ASGITransport(app=app)
    async with AsyncClient(transport=transport, base_url="http://test") as client:
        resp = await client.post(
            "/federation/query",
            json={
                "query_type": "similarity",
                "payload": {"embedding": [0.1, 0.2]},
                "source_institution_id": "unknown-inst",
                "max_results": 10,
            },
        )
        assert resp.status_code == 403


@pytest.mark.asyncio
async def test_query_invalid_type_returns_400(tmp_path, monkeypatch):
    """Queries with unsupported types are rejected."""
    registry_path = str(tmp_path / "badtype_registry.json")
    monkeypatch.setattr("config.settings.registry_file", registry_path)

    import relay as relay_mod
    relay_mod._registry = None

    from relay import app

    transport = ASGITransport(app=app)
    async with AsyncClient(transport=transport, base_url="http://test") as client:
        # Register the source
        await client.post(
            "/federation/peers/register",
            json={
                "id": "src",
                "name": "Source",
                "endpoint_url": "https://src.org",
                "public_key": "aaaa",
            },
        )
        resp = await client.post(
            "/federation/query",
            json={
                "query_type": "dangerous_operation",
                "payload": {},
                "source_institution_id": "src",
                "max_results": 10,
            },
        )
        assert resp.status_code == 400


@pytest.mark.asyncio
async def test_federation_query_with_mock_peers(tmp_path, monkeypatch):
    """Test the full query fan-out flow with mocked peer responses."""
    registry_path = str(tmp_path / "fanout_registry.json")
    monkeypatch.setattr("config.settings.registry_file", registry_path)
    monkeypatch.setattr("config.settings.min_k_anonymity", 1)

    import relay as relay_mod
    relay_mod._registry = None

    from relay import app

    transport = ASGITransport(app=app)
    async with AsyncClient(transport=transport, base_url="http://test") as client:
        # Register source + peer
        await client.post(
            "/federation/peers/register",
            json={
                "id": "source",
                "name": "Source Hospital",
                "endpoint_url": "https://source.org",
                "public_key": "aaaa",
            },
        )
        await client.post(
            "/federation/peers/register",
            json={
                "id": "peer-a",
                "name": "Peer A Hospital",
                "endpoint_url": "https://peer-a.org",
                "public_key": "bbbb",
            },
        )

        # Mock the fan-out so it returns fake results
        mock_results = [
            (
                PeerInstitution(
                    id="peer-a",
                    name="Peer A Hospital",
                    endpoint_url="https://peer-a.org",
                    public_key="bbbb",
                ),
                {
                    "institution_id": "peer-a",
                    "results": [
                        {
                            "patient_id": 42,
                            "similarity_score": 0.92,
                            "domain_scores": {"diagnosis": 0.85},
                        }
                    ],
                    "patient_count": 1,
                },
            )
        ]

        with patch.object(relay_mod, "_fan_out_query", new_callable=AsyncMock) as mock_fan:
            mock_fan.return_value = mock_results
            resp = await client.post(
                "/federation/query",
                json={
                    "query_type": "similarity",
                    "payload": {"embedding": [0.1, 0.2, 0.3]},
                    "source_institution_id": "source",
                    "max_results": 10,
                },
            )

        assert resp.status_code == 200
        data = resp.json()
        assert data["peers_responded"] == 1
        assert data["total_results"] == 1
        # Patient ID should be hashed, not raw
        result = data["results"][0]
        assert "hashed_patient_id" in result
        assert result["hashed_patient_id"] != "42"
