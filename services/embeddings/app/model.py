from __future__ import annotations

import logging
import os
import threading
from pathlib import Path
from typing import Protocol

from .config import settings
from .stub_embedder import StubHashEmbedder

logger = logging.getLogger(__name__)


class Embedder(Protocol):
    """Minimal interface used by the API layer (so we can stub in tests)."""

    def embed(self, texts: list[str], prefix: str = "passage") -> list[list[float]]: ...

    @property
    def is_loaded(self) -> bool: ...


class SentenceTransformerEmbedder:
    """Lazy-loading wrapper around sentence-transformers."""

    def __init__(self, model_id: str, dimension: int) -> None:
        self.model_id = model_id
        self.dimension = dimension
        self._model = None
        self._lock = threading.Lock()

    @property
    def is_loaded(self) -> bool:
        return self._model is not None

    def _load(self) -> None:
        if self._model is not None:
            return
        with self._lock:
            if self._model is not None:
                return
            model_path = settings.resolved_model_path()
            local_only = Path(model_path).is_dir()
            logger.info(
                "Loading model %s (local_files_only=%s)",
                model_path,
                local_only,
            )
            # Imported here to keep CLI startup fast and let tests stub before import
            from sentence_transformers import SentenceTransformer

            if local_only:
                os.environ.setdefault("HF_HUB_OFFLINE", "1")

            self._model = SentenceTransformer(
                model_path,
                local_files_only=local_only,
            )
            self._model.max_seq_length = 512
            logger.info("Model %s loaded", model_path)

    def embed(self, texts: list[str], prefix: str = "passage") -> list[list[float]]:
        self._load()
        # E5 family expects "query: " / "passage: " prefix
        prepared = [f"{prefix}: {t}" for t in texts]
        vectors = self._model.encode(prepared, normalize_embeddings=True)  # type: ignore[union-attr]
        return [list(map(float, v)) for v in vectors]


_embedder: Embedder | None = None


def get_embedder() -> Embedder:
    """FastAPI dependency producing the singleton embedder."""

    global _embedder
    if _embedder is None:
        if settings.use_stub:
            logger.warning("Using StubHashEmbedder (set USE_STUB=false + HF access for real semantics)")
            _embedder = StubHashEmbedder(settings.embedding_dim)
        else:
            _embedder = SentenceTransformerEmbedder(settings.model_id, settings.embedding_dim)
    return _embedder


def set_embedder(embedder: Embedder) -> None:
    """Used by tests to inject a stub."""

    global _embedder
    _embedder = embedder
