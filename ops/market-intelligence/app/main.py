import json
import os
import secrets
from datetime import datetime, timezone
from typing import Literal
from fastapi import FastAPI, Header, HTTPException, Response, status
from pydantic import BaseModel, ConfigDict, Field
from sqlalchemy import select, text, func
from prometheus_client import Gauge, CollectorRegistry, generate_latest, CONTENT_TYPE_LATEST
from .core import normalize_price_toman
from .db import Base, engine, Session, Listing, History, Snapshot, IngestRun, now

app = FastAPI(title="SPAD Market Intelligence", version="0.1.0")
MIN_SAMPLES = int(os.getenv("MIN_SAMPLES", "10"))
ALLOWED = {s.strip() for s in os.getenv("ALLOWED_SOURCES", "spad_authorized").split(",") if s.strip()}
registry = CollectorRegistry()
snapshot_age = Gauge("spad_market_snapshot_age_seconds", "Seconds since latest computed snapshot; -1 is missing", registry=registry)
ingest_age = Gauge("spad_market_ingest_age_seconds", "Seconds since successful authorized ingest; -1 is missing", registry=registry)
qualified = Gauge("spad_market_qualified_neighborhoods", "Neighborhoods with sufficient comparable listings", registry=registry)
listing_total = Gauge("spad_market_listing_total", "Unique authorized market listings", registry=registry)

class Observation(BaseModel):
    model_config = ConfigDict(extra="forbid")
    source: str = Field(min_length=2, max_length=80)
    external_id: str = Field(min_length=1, max_length=180)
    city: str = Field(min_length=1, max_length=100)
    neighborhood: str = Field(min_length=1, max_length=160)
    property_type: Literal["apartment", "house", "villa", "land"]
    area_m2: float = Field(gt=10, le=50000)
    price: float = Field(gt=0, le=1e15)
    currency: Literal["TOMAN", "IRR"]
    rooms: int | None = Field(default=None, ge=0, le=30)
    year_built: int | None = Field(default=None, ge=1250, le=1500)

class ObservationBatch(BaseModel):
    items: list[Observation] = Field(min_length=1, max_length=500)

def require_ingest_key(key: str | None):
    expected = os.getenv("INGEST_API_KEY", "")
    if len(expected) < 24 or not key or not secrets.compare_digest(key, expected):
        raise HTTPException(status_code=403, detail="ingestion disabled or unauthorized")

@app.on_event("startup")
def create_schema():
    Base.metadata.create_all(bind=engine)

@app.get("/health/live")
def live():
    return {"status": "alive"}

@app.get("/health/ready")
def ready():
    try:
        with engine.connect() as conn:
            conn.execute(text("SELECT 1"))
        return {"status": "ready", "database": True}
    except Exception:
        raise HTTPException(status_code=503, detail="database unavailable")

@app.post("/v1/observations")
def ingest(batch: ObservationBatch, x_api_key: str | None = Header(default=None)):
    require_ingest_key(x_api_key)
    forbidden = set(x.source for x in batch.items) - ALLOWED
    if forbidden:
        raise HTTPException(status_code=403, detail="source not allowlisted: " + ", ".join(sorted(forbidden)))
    accepted = 0
    with Session.begin() as db:
        for item in batch.items:
            normalized = normalize_price_toman(item.price, item.currency)
            prior = db.execute(select(Listing).where(Listing.source == item.source, Listing.external_id == item.external_id)).scalar_one_or_none()
            if prior is None:
                prior = Listing(source=item.source, external_id=item.external_id,
                    city=item.city.strip(), neighborhood=item.neighborhood.strip(),
                    property_type=item.property_type, area_m2=item.area_m2,
                    price_toman=normalized, rooms=item.rooms, year_built=item.year_built)
                db.add(prior)
                db.flush()
                db.add(History(listing_id=prior.id, price_toman=normalized))
            else:
                if prior.price_toman != normalized:
                    db.add(History(listing_id=prior.id, price_toman=normalized))
                prior.price_toman = normalized
                prior.area_m2 = item.area_m2
                prior.city = item.city.strip()
                prior.neighborhood = item.neighborhood.strip()
                prior.property_type = item.property_type
                prior.rooms = item.rooms
                prior.year_built = item.year_built
                prior.last_seen = now()
            accepted += 1
        db.add(IngestRun(source=",".join(sorted({i.source for i in batch.items})), received=len(batch.items), accepted=accepted))
    return {"accepted": accepted, "sources": sorted({i.source for i in batch.items})}

@app.get("/v1/market")
def market(city: str = "شیراز"):
    with Session() as db:
        recent = db.execute(select(Snapshot).order_by(Snapshot.measured_at.desc()).limit(1)).scalar_one_or_none()
        if recent is None:
            return {"status": "no_snapshot", "updated_at": None, "neighborhoods": []}
        all_rows = json.loads(recent.payload)
        rows = [x for x in all_rows if x["city"] == city]
        stamp = recent.measured_at
        return {"status": "ok" if any(x["status"] == "sufficient" for x in rows) else "insufficient_data",
                "price_type": "asking_prices_not_transactions", "city": city,
                "updated_at": stamp.isoformat(), "neighborhoods": rows}

@app.get("/metrics", include_in_schema=False)
def metrics():
    with Session() as db:
        snap = db.execute(select(Snapshot).order_by(Snapshot.measured_at.desc()).limit(1)).scalar_one_or_none()
        ing = db.execute(select(IngestRun).order_by(IngestRun.recorded_at.desc()).limit(1)).scalar_one_or_none()
        listing_total.set(db.scalar(select(func.count()).select_from(Listing)) or 0)
        if snap:
            stamp = snap.measured_at.replace(tzinfo=timezone.utc) if snap.measured_at.tzinfo is None else snap.measured_at
            snapshot_age.set(max(0, (now() - stamp).total_seconds()))
            qualified.set(sum(1 for r in json.loads(snap.payload) if r["status"] == "sufficient"))
        else:
            snapshot_age.set(-1)
            qualified.set(0)
        if ing:
            stamp = ing.recorded_at.replace(tzinfo=timezone.utc) if ing.recorded_at.tzinfo is None else ing.recorded_at
            ingest_age.set(max(0, (now() - stamp).total_seconds()))
        else:
            ingest_age.set(-1)
    return Response(content=generate_latest(registry), media_type=CONTENT_TYPE_LATEST)
