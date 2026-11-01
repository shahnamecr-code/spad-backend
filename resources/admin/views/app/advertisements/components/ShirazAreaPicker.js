import React from "react";
import { MapContainer, TileLayer, Circle, useMapEvents } from "react-leaflet";
import "leaflet/dist/leaflet.css";

const SHIRAZ = [29.5918, 52.5837];
const BOUNDS = [[29.3, 52.1], [30.0, 53.2]];
const valid = (lat, lng) => lat != null && lng != null
  && Number.isFinite(Number(lat)) && Number.isFinite(Number(lng))
  && Number(lat) >= 29.3 && Number(lat) <= 30
  && Number(lng) >= 52.1 && Number(lng) <= 53.2;

const ClickToChoose = ({ onChange }) => {
  useMapEvents({
    click(event) {
      const { lat, lng } = event.latlng;
      if (valid(lat, lng)) {
        onChange({ lat: Number(lat.toFixed(6)), lng: Number(lng.toFixed(6)) });
      }
    },
  });
  return null;
};

const ShirazAreaPicker = ({ latitude, longitude, onChange }) => {
  const selected = valid(latitude, longitude);
  const center = selected ? [Number(latitude), Number(longitude)] : SHIRAZ;

  return (
    <div className="spad-ad-document-panel mb-4" style={{ direction: "rtl" }}>
      <div className="spad-ad-finance-title">
        <span className="spad-ad-tool-icon spad-ad-tool-icon--blue"><i className="simple-icon-location-pin" /></span>
        <div>
          <h4>محدوده ملک در نقشه شیراز</h4>
          <p>روی محدوده تقریبی ملک کلیک کنید؛ موقعیت دقیق ساختمان عمومی نمی‌شود.</p>
        </div>
      </div>
      <div style={{ border: "1px solid #d7eafa", borderRadius: 15, overflow: "hidden", height: 275 }}>
        <MapContainer center={center} zoom={selected ? 13 : 11} minZoom={10} maxZoom={17}
          maxBounds={BOUNDS} maxBoundsViscosity={1} scrollWheelZoom={false}
          style={{ height: "100%", width: "100%" }}>
          <TileLayer attribution='&copy; OpenStreetMap contributors'
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
          <ClickToChoose onChange={onChange} />
          {selected && <Circle center={center} radius={580}
            pathOptions={{ color: "#0984e3", fillColor: "#36b4ff", fillOpacity: 0.26, weight: 3 }} />}
        </MapContainer>
      </div>
      <div className="d-flex justify-content-between align-items-center mt-2">
        <small style={{ color: "#507b9b" }}>{selected ? "محدوده تقریبی انتخاب شد" : "برای تعیین محدوده روی نقشه شیراز بزنید"}</small>
        {selected && <button type="button" className="btn btn-outline-primary btn-sm"
          onClick={() => onChange(null)}>حذف انتخاب</button>}
      </div>
    </div>
  );
};

export default ShirazAreaPicker;
