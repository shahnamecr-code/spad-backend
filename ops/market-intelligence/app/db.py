import os
from datetime import datetime, timezone
from sqlalchemy import create_engine, Column, Integer, String, DateTime, Float, BigInteger, ForeignKey, UniqueConstraint, Index, Text
from sqlalchemy.orm import declarative_base, sessionmaker

DATABASE_URL = os.environ.get("DATABASE_URL", "sqlite:///./market-test.db")
engine = create_engine(DATABASE_URL, pool_pre_ping=True)
Session = sessionmaker(bind=engine, expire_on_commit=False)
Base = declarative_base()

def now():
    return datetime.now(timezone.utc)

class Listing(Base):
    __tablename__ = "market_listings"
    id = Column(Integer, primary_key=True)
    source = Column(String(80), nullable=False)
    external_id = Column(String(180), nullable=False)
    city = Column(String(100), nullable=False)
    neighborhood = Column(String(160), nullable=False)
    property_type = Column(String(40), nullable=False)
    area_m2 = Column(Float, nullable=False)
    price_toman = Column(BigInteger, nullable=False)
    rooms = Column(Integer)
    year_built = Column(Integer)
    first_seen = Column(DateTime(timezone=True), default=now, nullable=False)
    last_seen = Column(DateTime(timezone=True), default=now, nullable=False)
    __table_args__ = (UniqueConstraint("source", "external_id"), Index("idx_market_city_area", "city", "neighborhood", "property_type", "last_seen"))

class History(Base):
    __tablename__ = "market_price_history"
    id = Column(Integer, primary_key=True)
    listing_id = Column(Integer, ForeignKey("market_listings.id", ondelete="CASCADE"), nullable=False)
    price_toman = Column(BigInteger, nullable=False)
    observed_at = Column(DateTime(timezone=True), default=now, nullable=False)

class Snapshot(Base):
    __tablename__ = "market_snapshots"
    id = Column(Integer, primary_key=True)
    measured_at = Column(DateTime(timezone=True), default=now, nullable=False)
    payload = Column(Text, nullable=False)

class IngestRun(Base):
    __tablename__ = "market_ingest_runs"
    id = Column(Integer, primary_key=True)
    source = Column(String(80), nullable=False)
    received = Column(Integer, nullable=False)
    accepted = Column(Integer, nullable=False)
    recorded_at = Column(DateTime(timezone=True), default=now, nullable=False)
