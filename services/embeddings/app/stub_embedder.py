from __future__ import annotations

import hashlib
import math
import re


class StubHashEmbedder:
    """
    Deterministic 384-dim vectors without HuggingFace (offline / restricted networks).
    Not true semantics, but enables pgvector pipeline and basic similarity for shared terms.
    """

    def __init__(self, dimension: int = 384) -> None:
        self.dimension = dimension

    @property
    def is_loaded(self) -> bool:
        return True

    def embed(self, texts: list[str], prefix: str = "passage") -> list[list[float]]:
        return [self._vector(f"{prefix}: {t}") for t in texts]

    def _vector(self, text: str) -> list[float]:
        tokens = re.findall(r"[\w\u0400-\u04ff]+", text.lower())
        vec = [0.0] * self.dimension

        for token in tokens:
            digest = hashlib.sha256(token.encode("utf-8")).digest()
            for i in range(0, len(digest), 4):
                idx = int.from_bytes(digest[i : i + 4], "little") % self.dimension
                vec[idx] += 1.0

        norm = math.sqrt(sum(x * x for x in vec)) or 1.0
        return [x / norm for x in vec]
