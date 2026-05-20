from __future__ import annotations

from pydantic import BaseModel, Field


class EmbedRequest(BaseModel):
    texts: list[str] = Field(min_length=1, max_length=256)


class EmbedResponse(BaseModel):
    model: str
    dim: int
    vectors: list[list[float]]


class HealthResponse(BaseModel):
    status: str
    model: str
    dim: int
    model_loaded: bool
