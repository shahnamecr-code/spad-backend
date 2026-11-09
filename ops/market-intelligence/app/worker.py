import json
import logging
import os
import time
from datetime import timedelta
from sqlalchemy import select, delete
from .core import summarize
from .db import Base, engine, Session, Listing, Snapshot, now

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
INTERVAL = max(60, int(os.getenv("ANALYSIS_INTERVAL_SECONDS", "300")))
MIN_SAMPLES = max(3, int(os.getenv("MIN_SAMPLES", "10")))
MAX_AGE = max(1, int(os.getenv("DATA_MAX_AGE_DAYS", "14")))

def run_once():
    Base.metadata.create_all(bind=engine)
    cutoff = now() - timedelta(days=MAX_AGE)
    with Session.begin() as db:
        listings = db.execute(select(Listing).where(Listing.last_seen >= cutoff)).scalars().all()
        rows = [{"city": r.city, "neighborhood": r.neighborhood, "property_type": r.property_type,
                 "price_toman": r.price_toman, "area_m2": r.area_m2} for r in listings]
        summaries = summarize(rows, MIN_SAMPLES)
        db.add(Snapshot(payload=json.dumps(summaries, ensure_ascii=False)))
        db.execute(delete(Snapshot).where(Snapshot.measured_at < now() - timedelta(days=30)))
    logging.info("analysis complete: listings=%s groups=%s reliable=%s", len(rows), len(summaries),
                 sum(r["status"] == "sufficient" for r in summaries))

if __name__ == "__main__":
    while True:
        try:
            run_once()
        except Exception:
            logging.exception("market analysis failed; will retry")
        time.sleep(INTERVAL)
