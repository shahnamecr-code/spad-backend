from collections import defaultdict
from statistics import median

def normalize_price_toman(value: float, currency: str) -> int:
    """Never implicitly confuse rials and tomans."""
    if currency not in ("TOMAN", "IRR"):
        raise ValueError("currency must be TOMAN or IRR")
    if value <= 0:
        raise ValueError("price must be positive")
    return round(value / 10 if currency == "IRR" else value)

def price_per_meter(price_toman: int, area_m2: float) -> int:
    if area_m2 <= 0:
        raise ValueError("area must be positive")
    return round(price_toman / area_m2)

def summarize(rows: list[dict], min_samples: int = 10) -> list[dict]:
    """Use neighborhood medians; suppress outliers and sparse markets."""
    groups = defaultdict(list)
    for row in rows:
        if row.get("price_toman", 0) <= 0 or row.get("area_m2", 0) <= 0:
            continue
        key = (row["city"].strip(), row["neighborhood"].strip(), row["property_type"].strip())
        if all(key):
            groups[key].append(price_per_meter(row["price_toman"], row["area_m2"]))
    result = []
    for (city, neighborhood, kind), values in sorted(groups.items()):
        values.sort()
        mid = median(values)
        deviations = sorted(abs(x - mid) for x in values)
        mad = median(deviations)
        filtered = [x for x in values if abs(x - mid) <= 3 * 1.4826 * mad] if mad else [x for x in values if mid * 0.5 <= x <= mid * 2]
        # Require both a reasonable original sample and post-filter sample.
        sufficient = len(filtered) >= min_samples
        result.append({
            "city": city, "neighborhood": neighborhood, "property_type": kind,
            "raw_count": len(values), "sample_count": len(filtered),
            "median_price_per_m2_toman": int(median(filtered)) if sufficient else None,
            "low_price_per_m2_toman": filtered[max(0, int(len(filtered) * 0.10))] if sufficient else None,
            "high_price_per_m2_toman": filtered[min(len(filtered)-1, int(len(filtered) * 0.90))] if sufficient else None,
            "status": "sufficient" if sufficient else "insufficient_data",
            "quality_note": "asking_prices_only_not_closed_transactions",
        })
    return result
