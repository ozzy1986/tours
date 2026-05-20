from __future__ import annotations

import hashlib

import pytest
from fastapi.testclient import TestClient

from app.config import settings
from app.main import create_app
from app.model import Embedder, set_embedder


class _StubEmbedder:
    """Deterministic stub: hash(text) -> float vector of dim = settings.embedding_dim."""

    is_loaded = True

    def embed(self, texts: list[str], prefix: str = "passage") -> list[list[float]]:
        dim = settings.embedding_dim
        out: list[list[float]] = []
        for t in texts:
            digest = hashlib.sha256(f"{prefix}:{t}".encode()).digest()
            # Repeat the 32-byte digest to fill `dim` floats in [0, 1)
            raw = (digest * ((dim // 32) + 1))[:dim]
            vec = [b / 255.0 for b in raw]
            # L2-normalize
            norm = sum(x * x for x in vec) ** 0.5 or 1.0
            out.append([x / norm for x in vec])
        return out


@pytest.fixture(autouse=True)
def stub_embedder():
    set_embedder(_StubEmbedder())
    yield
    set_embedder(None)  # type: ignore[arg-type]


@pytest.fixture()
def client() -> TestClient:
    return TestClient(create_app())
