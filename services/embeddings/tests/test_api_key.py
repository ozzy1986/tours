from __future__ import annotations

import pytest
from fastapi.testclient import TestClient

from app.config import settings
from app.main import create_app


@pytest.fixture()
def keyed_client(monkeypatch: pytest.MonkeyPatch) -> TestClient:
    monkeypatch.setattr(settings, "embeddings_api_key", "test-secret")
    return TestClient(create_app())


def test_embed_requires_api_key_when_configured(keyed_client: TestClient) -> None:
    r = keyed_client.post("/embed", json={"texts": ["hello"]})
    assert r.status_code == 401

    r_ok = keyed_client.post(
        "/embed",
        json={"texts": ["hello"]},
        headers={"X-Api-Key": "test-secret"},
    )
    assert r_ok.status_code == 200


def test_healthz_does_not_require_api_key(keyed_client: TestClient) -> None:
    assert keyed_client.get("/healthz").status_code == 200
