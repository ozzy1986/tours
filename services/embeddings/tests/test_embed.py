from __future__ import annotations


def test_embed_happy(client) -> None:
    r = client.post("/embed", json={"texts": ["Привет, мир", "Hello, world"]})
    assert r.status_code == 200
    body = r.json()
    assert body["dim"] == 384
    assert len(body["vectors"]) == 2
    assert all(len(v) == 384 for v in body["vectors"])

    # All vectors normalized
    for v in body["vectors"]:
        assert abs(sum(x * x for x in v) - 1.0) < 1e-6


def test_embed_empty_array_rejected(client) -> None:
    r = client.post("/embed", json={"texts": []})
    assert r.status_code in (400, 422)


def test_embed_empty_string_rejected(client) -> None:
    r = client.post("/embed", json={"texts": ["   "]})
    assert r.status_code == 400


def test_embed_prefix_changes_output(client) -> None:
    r1 = client.post("/embed", json={"texts": ["test"]}, params={"prefix": "passage"})
    r2 = client.post("/embed", json={"texts": ["test"]}, params={"prefix": "query"})
    assert r1.json()["vectors"][0] != r2.json()["vectors"][0]


def test_embed_invalid_prefix_rejected(client) -> None:
    r = client.post("/embed", json={"texts": ["test"]}, params={"prefix": "INVALID"})
    assert r.status_code == 422
