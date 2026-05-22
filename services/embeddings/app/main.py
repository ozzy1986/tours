from __future__ import annotations

import logging
from contextlib import asynccontextmanager

from fastapi import Depends, FastAPI, HTTPException, Query
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import RedirectResponse

from .auth import require_api_key
from .config import settings
from .model import Embedder, SentenceTransformerEmbedder, get_embedder
from .schemas import EmbedRequest, EmbedResponse, HealthResponse

logging.basicConfig(level=logging.INFO)
log = logging.getLogger("embeddings")


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Eagerly load the model so /healthz reflects the real state and the
    first user request does not pay the ~5s load cost.
    """
    embedder = get_embedder()
    if isinstance(embedder, SentenceTransformerEmbedder):
        ok = embedder.preload()
        if ok:
            log.info("Embeddings model is ready: %s", settings.resolved_model_path())
        else:
            log.error(
                "Embeddings model failed to load (%s). Service stays up; "
                "/healthz will report model_loaded=false so callers fall back to keyword search.",
                embedder.load_error,
            )
    yield


def create_app() -> FastAPI:
    app = FastAPI(
        title="Tours embeddings service",
        version="0.1.0",
        description="multilingual-e5-small embeddings for Russian/English text",
        lifespan=lifespan,
    )

    app.add_middleware(
        CORSMiddleware,
        allow_origins=settings.cors_origins,
        allow_methods=["GET", "POST", "OPTIONS"],
        allow_headers=["*"],
        allow_credentials=False,
    )

    @app.get("/", include_in_schema=False)
    def root() -> RedirectResponse:
        return RedirectResponse(url="/healthz")

    @app.get("/healthz", response_model=HealthResponse)
    def healthz(embedder: Embedder = Depends(get_embedder)) -> HealthResponse:
        return HealthResponse(
            status="ok",
            model=settings.resolved_model_path(),
            dim=settings.embedding_dim,
            model_loaded=embedder.is_loaded,
            use_stub=settings.use_stub,
        )

    @app.post("/embed", response_model=EmbedResponse, dependencies=[Depends(require_api_key)])
    def embed(
        payload: EmbedRequest,
        prefix: str = Query("passage", pattern="^(passage|query)$"),
        embedder: Embedder = Depends(get_embedder),
    ) -> EmbedResponse:
        texts = [t.strip() for t in payload.texts]
        if any(not t for t in texts):
            raise HTTPException(status_code=400, detail="Empty text in input")

        try:
            vectors = embedder.embed(texts, prefix=prefix)
        except Exception as e:  # noqa: BLE001
            log.exception("Embedding failed")
            raise HTTPException(status_code=500, detail=f"Embedding failed: {e}") from e

        return EmbedResponse(model=settings.model_id, dim=settings.embedding_dim, vectors=vectors)

    return app


app = create_app()
