"""Smoke tests for Aurora AI service."""


def test_basic_assertion():
    """Trivial sanity check to verify pytest runs."""
    assert 1 + 1 == 2


def test_health_with_fixture(client):
    """Health endpoint works via the shared client fixture."""
    response = client.get("/api/ai/health")
    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "ok"
