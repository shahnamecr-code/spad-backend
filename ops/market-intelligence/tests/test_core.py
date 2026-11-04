from app.core import normalize_price_toman, price_per_meter, summarize

def test_currency_conversion():
    assert normalize_price_toman(1_000_000_000, "IRR") == 100_000_000
    assert normalize_price_toman(100_000_000, "TOMAN") == 100_000_000

def test_separation_of_neighborhoods_and_types():
    rows = [
        {"city": "شیراز", "neighborhood": "معالی آباد", "property_type": "apartment",
         "area_m2": 100, "price_toman": 10_000_000_000},
        {"city": "شیراز", "neighborhood": "معالی آباد", "property_type": "house",
         "area_m2": 100, "price_toman": 15_000_000_000},
    ]
    out = summarize(rows, min_samples=1)
    assert len(out) == 2 and all(item["status"] == "sufficient" for item in out)

def test_no_fake_prices_when_insufficient_data():
    out = summarize([{"city": "شیراز", "neighborhood": "قصردشت", "property_type": "apartment",
                      "area_m2": 100, "price_toman": 8_000_000_000}], min_samples=10)
    assert out[0]["median_price_per_m2_toman"] is None
    assert out[0]["status"] == "insufficient_data"

def test_price_per_meter():
    assert price_per_meter(10_000_000_000, 100) == 100_000_000
