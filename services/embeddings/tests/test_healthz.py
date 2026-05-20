from __future__ import annotations


def test_healthz(client) -> None:
    r = client.get("/healthz")
    assert r.status_code == 200
    body = r.json()
    assert body["status"] == "ok"
    assert body["dim"] == 384
    assert body["model_loaded"] is True


def test_root_redirects(client) -> None:
    r = client.get("/", follow_redirects=False)
    assert r.status_code in (302, 307)
    assert r.headers["location"].endswith("/healthz")
