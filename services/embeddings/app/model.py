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
    """Eager-loading wrapper around sentence-transformers.

    The model is loaded once via ``preload()`` (called from FastAPI startup) so
    that the first request never blocks on a multi-second download / disk read
    and ``/healthz`` accurately reports the ready state.
    """

    def __init__(self, model_id: str, dimension: int) -> None:
        self.model_id = model_id
        self.dimension = dimension
        self._model = None
        self._lock = threading.Lock()
        self._load_error: Exception | None = None

    @property
    def is_loaded(self) -> bool:
        return self._model is not None

    @property
    def load_error(self) -> Exception | None:
        return self._load_error

    def preload(self) -> bool:
        """Load the model now. Returns True on success, False otherwise.

        Failures are recorded in ``load_error`` and surfaced through
        ``/healthz`` so callers (Laravel) can transparently degrade to keyword
        search rather than show a runtime error to the user.
        """
        try:
            self._load()
            return True
        except Exception as exc:  # noqa: BLE001
            self._load_error = exc
            logger.exception("Embedding model preload failed: %s", exc)
            return False

    def _load(self) -> None:
        if self._model is not None:
            return
        with self._lock:
            if self._model is not None:
                return

            model_path = settings.resolved_model_path()
            local_only = Path(model_path).is_dir()

            # Force offline mode BEFORE importing/using transformers so it
            # never attempts a network roundtrip when a local model is present.
            if local_only:
                os.environ["HF_HUB_OFFLINE"] = "1"
                os.environ["TRANSFORMERS_OFFLINE"] = "1"

            logger.info(
                "Loading SentenceTransformer model from %s (local_files_only=%s)",
                model_path,
                local_only,
            )

            # Imported here to keep CLI startup fast and let tests stub before import
            from sentence_transformers import SentenceTransformer

            model = SentenceTransformer(
                model_path,
                local_files_only=local_only,
            )
            model.max_seq_length = 512
            self._model = model
            self._load_error = None
            logger.info("Model loaded successfully from %s", model_path)

    def embed(self, texts: list[str], prefix: str = "passage") -> list[list[float]]:
        if self._model is None:
            # preload() should have been called; fall back to a lazy load just
            # in case (tests, direct .embed() use).
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
            logger.warning(
                "Using StubHashEmbedder (set USE_STUB=false + provide local model for real semantics)"
            )
            _embedder = StubHashEmbedder(settings.embedding_dim)
        else:
            _embedder = SentenceTransformerEmbedder(settings.model_id, settings.embedding_dim)
    return _embedder


def set_embedder(embedder: Embedder) -> None:
    """Used by tests to inject a stub."""

    global _embedder
    _embedder = embedder
