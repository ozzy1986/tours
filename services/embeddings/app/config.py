from __future__ import annotations

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    """Service configuration loaded from environment / .env file."""

    model_config = SettingsConfigDict(
        env_file=".env",
        extra="ignore",
        protected_namespaces=("settings_",),
    )

    model_id: str = "intfloat/multilingual-e5-small"
    embedding_dim: int = 384
    host: str = "0.0.0.0"
    port: int = 8001
    cors_origins: list[str] = [
        "http://localhost:8000",
        "http://localhost:3000",
        "http://127.0.0.1:8000",
        "http://127.0.0.1:3000",
    ]
    # Use hash embedder when HuggingFace is unreachable (set true for Laragon offline dev)
    use_stub: bool = True


settings = Settings()
