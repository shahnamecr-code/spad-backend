# SPAD Market Intelligence — isolated service (stage 1)

**Status:** Source-only deployment candidate. This code does **not** run on SPAD production unless independently installed. No Divar API or scraper is enabled, and no production values were measured.

## Architecture
- FastAPI (read and authenticated ingestion), PostgreSQL (deduplicated listings, price history), separate 5-minute analytics worker.
- Median-based neighborhood/property-type estimates with MAD outlier trimming. Prices are **seller asking prices**, never verified transactions.
- Prometheus and node-exporter for API/worker freshness, host disk/memory, ingestion lag. Grafana provisioned with a Prometheus datasource. Alertmanager rules are included but **alerts are NOT delivered** until a receiver is configured.
- All service ports bind to **127.0.0.1**; expose APIs only behind a TLS/auth reverse proxy with per-client controls, or via private network.
- Retains latest 30 days of snapshots and persistent listing price history. Production needs retention and deletion policy approval.

## Security and legal gate
1. Only ingest data from sources for which SPAD has documented commercial collection/processing rights.
2. Default allowlist is `spad_authorized`. The `divar_licensed` adapter is disabled unless explicitly added after obtaining permission; Excel importer only processes a manually provided export.
3. No crawler, internal Divar API, CAPTCHA bypass, IP rotation, or phone-number extraction.
4. Reject unknown fields, including contact details; store minimal price and location metadata.
5. Use unique strong secrets, restrict local ports, enforce firewall, take encrypted off-host PostgreSQL backups, rotate secrets, and configure Prometheus/Grafana user access before production.

## Local / dedicated server installation
```bash
cp .env.example .env
chmod 600 .env
# Replace all REPLACE_WITH_* and set allowed source names explicitly.
docker compose --env-file .env -f compose.yml config --quiet
docker compose --env-file .env -f compose.yml up -d --build
curl -fsS http://127.0.0.1:8732/health/ready
curl -fsS http://127.0.0.1:8732/v1/market?city=%D8%B4%DB%8C%D8%B1%D8%A7%D8%B2
```
Grafana available only via private SSH tunnel to localhost port 3003; Prometheus on 9098. Use `docker compose logs -f api worker` to troubleshoot.

## Data input
`POST /v1/observations` (X-API-Key), up to 500 records per request. Source must be in ALLOWED_SOURCES. Currency must be explicit TOMAN or IRR. Example:
```json
{"items":[{"source":"spad_authorized","external_id":"fixture-1","city":"شیراز","neighborhood":"قصردشت","property_type":"apartment","area_m2":100,"price":8000000000,"currency":"TOMAN"}]}
```
Do not send test fixtures to production, and never present demo prices as real prices.

Optional **manual authorized** Excel importer compatible with the Divar House Scraper's *English output columns*:
```bash
export INGEST_API_KEY='your-secret-from-env'
python -m scripts.import_divar_excel --file /private/approved-export.xlsx --city شیراز
```
Needs source permission and `ALLOWED_SOURCES=spad_authorized,divar_licensed` (only after license).

## Operational checklist before go-live
- Obtain clear data-use rights. Review third-party scraper licenses **before copying any source code**.
- Provision a dedicated Linux server with Docker/Compose, TLS gateway and private management VPN.
- Set real secrets, API allowlist, backup, disaster recovery, retention and restore drill.
- Wire Laravel/Next.js dashboard to `GET /v1/market` (not yet connected); return "insufficient_data" instead of fabricated prices.
- Connect Alertmanager receiver (mail/approved webhook) and **test delivery**.
- Test with authorized fixture inputs, verify outlier handling and compare estimates with actual completed deals where legitimately available.
- Add CI, threat model, exporter metrics for DB/backups/queue and load test.
- No 24/7 guarantee without actual host deployment and external monitoring.

## Tests
```bash
python -m pytest tests -q
```
