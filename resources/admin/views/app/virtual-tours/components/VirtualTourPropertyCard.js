import React, { useEffect, useState } from "react";
import { Button, Card, CardBody, Badge } from "reactstrap";
import { useHistory } from "react-router-dom";
import axios from "axios";
import { adminPathApi, adminRoot } from "../../../../constants/defaultValues";
import "../../../../assets/css/sass/virtual-tours.scss";

const VirtualTourPropertyCard = ({ advertisementId }) => {
  const history = useHistory();
  const [loading, setLoading] = useState(false);
  const [tour, setTour] = useState(null);
  const load = async () => {
    if (!advertisementId) return;
    setLoading(true);
    try { const { data } = await axios.get(`${adminPathApi}/virtual-tours/property/${advertisementId}`); setTour(data.data || null); } finally { setLoading(false); }
  };
  const openStudio = async () => {
    setLoading(true);
    try {
      if (tour) return history.push(`${adminRoot}/virtual-tours/${tour.id}/edit`);
      const { data } = await axios.post(`${adminPathApi}/virtual-tours/property/${advertisementId}`);
      setTour(data.data);
      history.push(`${adminRoot}/virtual-tours/${data.data.id}/edit`);
    } finally { setLoading(false); }
  };
  useEffect(() => { load(); }, [advertisementId]);
  return <Card className="vt-property-card mt-4"><CardBody>
    <div className="vt-property-card__icon">360°</div>
    <div className="vt-property-card__content">
      <div className="d-flex align-items-center flex-wrap mb-1"><h3 className="mb-0 ml-2">تور مجازی 360°</h3>{tour && <Badge color={tour.status === "published" ? "success" : "secondary"}>{tour.status === "published" ? "منتشر شده" : "پیش‌نویس"}</Badge>}</div>
      <p>تصاویر پانورامای 360 را آپلود کنید، Scene بسازید، Hotspotها را روی تصویر قرار دهید و قبل از انتشار Preview بگیرید.</p>
      {tour && <small>{tour.scenes?.length || 0} فضا • {tour.scenes?.reduce((s,x)=>s+(x.hotspots?.length||0),0) || 0} Hotspot</small>}
    </div>
    <Button color="primary" disabled={loading} onClick={openStudio}>{loading ? "در حال آماده‌سازی..." : tour ? "باز کردن استودیو" : "ساخت تور جدید"}</Button>
  </CardBody></Card>;
};
export default VirtualTourPropertyCard;
