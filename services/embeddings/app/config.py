from __future__ import annotations

from pathlib import Path

from pydantic_settings import BaseSettings, SettingsConfigDict

# services/embeddings/
SERVICE_ROOT = Path(__file__).resolve().parent.parent
DEFAULT_LOCAL_MODEL_DIR = SERVICE_ROOT / "models" / "intfloat-multilingual-e5-small"


class Settings(BaseSettings):
    """Service configuration loaded from environment / .env file."""

    model_config = SettingsConfigDict(
        env_file=".env",
        extra="ignore",
        protected_namespaces=("settings_",),
    )

    # HuggingFace repo id or path to a local model directory (recommended: bundled under models/).
    model_id: str = "models/intfloat-multilingual-e5-small"
    embedding_dim: int = 384
    host: str = "0.0.0.0"
    port: int = 8001
    cors_origins: list[str] = [
        "http://localhost:8000",
        "http://localhost:3000",
        "http://127.0.0.1:8000",
        "http://127.0.0.1:3000",
    ]
    # Real HuggingFace model by default. Set USE_STUB=true only when HF is unreachable.
    use_stub: bool = False
    # When set, POST /embed requires matching X-Api-Key header.
    embeddings_api_key: str | None = None

    def resolved_model_path(self) -> str:
        """Absolute path to a local model dir, or a HuggingFace model id for download."""
        raw = self.model_id.strip()
        candidate = Path(raw)
        if candidate.is_absolute():
            return str(candidate) if candidate.is_dir() else raw

        for base in (Path.cwd(), SERVICE_ROOT):
            local = (base / raw).resolve()
            if local.is_dir():
                return str(local)

        if DEFAULT_LOCAL_MODEL_DIR.is_dir():
            return str(DEFAULT_LOCAL_MODEL_DIR)

        return raw

    def model_is_local(self) -> bool:
        return Path(self.resolved_model_path()).is_dir()


settings = Settings()
