import { MapContainer, TileLayer, Marker, useMapEvents } from "react-leaflet";
import "leaflet/dist/leaflet.css";
import "leaflet-defaulticon-compatibility/dist/leaflet-defaulticon-compatibility.css";
import "leaflet-defaulticon-compatibility";
import { useMemo, useRef, useState } from "react";
import { Marker as TMarker } from "leaflet";

interface IProps {
  lng?: number;
  lat?: number;
  onChange: (value: { lat: number; lng: number }) => void;
  className?: string;
}

const Map = ({ lat = 38.0792, lng = 46.2887, onChange, className }: IProps) => {
  const [latlng, setlatlng] = useState({ lat: lat, lng: lng });

  function MyComponent() {
    const map = useMapEvents({
      click: (data) => {
        setlatlng(data.latlng);
        onChange(data.latlng);
        // map.locate();
      },
    });
    return null;
  }

  const markerRef = useRef<TMarker<any>>(null);
  const eventHandlers = useMemo(
    () => ({
      dragend() {
        const marker = markerRef.current;
        if (marker != null) {
          setlatlng(marker.getLatLng());
        }
      },
    }),
    []
  );

  return (
    <MapContainer
      center={latlng}
      zoom={13}
      scrollWheelZoom={false}
      style={{ height: 250, width: "100%", borderRadius: 10 }}
      className={className}
    >
      <MyComponent />
      <TileLayer
        attribution='&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
      />
      <Marker
        draggable
        eventHandlers={eventHandlers}
        position={latlng}
        ref={markerRef}
      />
    </MapContainer>
  );
};

export default Map;
