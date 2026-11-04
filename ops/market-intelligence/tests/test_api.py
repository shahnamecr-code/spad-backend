import os
os.environ["DATABASE_URL"] = "sqlite:///./market-ci.db"
os.environ["INGEST_API_KEY"] = "ci-test-ingest-secret-32-characters"
os.environ["ALLOWED_SOURCES"] = "spad_authorized"

from fastapi.testclient import TestClient
from app.main import app
from app.db import Base, engine, Session, Listing, History
from app.worker import run_once
from sqlalchemy import select, func

def test_integration_requires_auth_and_allowed_source():
    sample = {"source":"spad_authorized","external_id":"test-1","city":"شیراز",
              "neighborhood":"قصردشت","property_type":"apartment",
              "area_m2":100,"price":8_000_000_000,"currency":"TOMAN"}
    with TestClient(app) as client:
        assert client.get("/health/ready").status_code == 200
        assert client.post("/v1/observations", json={"items":[sample]}).status_code == 403
        h={"X-API-Key":os.environ["INGEST_API_KEY"]}
        assert client.post("/v1/observations", headers=h, json={"items":[dict(sample, source="divar_licensed")]}).status_code == 403
        payload = [dict(sample, external_id="test-"+str(n)) for n in (1,2,3)]
        assert client.post("/v1/observations", headers=h, json={"items":payload}).json()["accepted"] == 3
        assert client.post("/v1/observations", headers=h, json={"items":[sample]}).status_code == 200
        with Session() as db:
            assert db.scalar(select(func.count()).select_from(Listing)) == 3
            assert db.scalar(select(func.count()).select_from(History)) == 3
        run_once()
        market = client.get("/v1/market", params={"city":"شیراز"}).json()
        assert market["status"] == "ok"
        assert market["neighborhoods"][0]["median_price_per_m2_toman"] == 80_000_000
        assert "spad_market_snapshot_age_seconds" in client.get("/metrics").text
