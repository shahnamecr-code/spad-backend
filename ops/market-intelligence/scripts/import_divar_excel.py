"""Optional import of an authorized, manually supplied Divar House Scraper export.

No automated crawling, API access, CAPTCHA bypass or contact data collection.
Do not enable 'divar_licensed' in ALLOWED_SOURCES without permission.
"""
import argparse
import json
import os
import urllib.request
from openpyxl import load_workbook

DIGITS = str.maketrans("۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩", "01234567890123456789")

def number(value):
    raw = str(value or "").translate(DIGITS).replace(",", "").replace("٬", "").replace("،", "")
    return float("".join(c for c in raw if c.isdigit() or c == ".") or 0)

def parse_export(path, city):
    workbook = load_workbook(path, read_only=True, data_only=True)
    sheet = workbook.active
    rows = sheet.iter_rows(values_only=True)
    headers = [str(v).strip().casefold() if v is not None else "" for v in next(rows)]
    def field(record, *names):
        for name in names:
            if name.casefold() in record:
                return record[name.casefold()]
        return None
    items = []
    for values in rows:
        record = dict(zip(headers, values))
        area = number(field(record, "Area (m²)", "Area", "متراژ"))
        price = number(field(record, "Total price (Toman)", "Total price", "قیمت کل"))
        listing_id = field(record, "Token", "token", "شناسه آگهی")
        neighborhood = field(record, "Neighborhood", "محله")
        if not all([area > 10, price > 0, listing_id, neighborhood]):
            continue
        kind = str(field(record, "Property type", "نوع ملک") or "apartment").casefold()
        property_type = "house" if ("villa" in kind or "ویلایی" in kind or "house" in kind) else "apartment"
        items.append(dict(source="divar_licensed", external_id=str(listing_id), city=city,
                          neighborhood=str(neighborhood), property_type=property_type,
                          area_m2=area, price=price, currency="TOMAN"))
    return items

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--file", required=True)
    parser.add_argument("--city", default="شیراز")
    parser.add_argument("--endpoint", default="http://127.0.0.1:8732/v1/observations")
    args = parser.parse_args()
    secret = os.environ.get("INGEST_API_KEY", "")
    if len(secret) < 24:
        raise SystemExit("Set INGEST_API_KEY in your shell. Never pass via CLI history.")
    items = parse_export(args.file, args.city)
    for offset in range(0, len(items), 500):
        payload = json.dumps({"items": items[offset:offset+500]}).encode()
        req = urllib.request.Request(args.endpoint, data=payload,
            headers={"Content-Type": "application/json", "X-API-Key": secret}, method="POST")
        with urllib.request.urlopen(req, timeout=30) as res:
            print(res.status, res.read().decode())
    print("Imported authorized rows:", len(items))

if __name__ == "__main__":
    main()
