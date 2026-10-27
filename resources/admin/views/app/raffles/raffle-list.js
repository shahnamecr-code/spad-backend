import React, { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { Badge, Button, Card, CardBody, Progress, Row } from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { adminPathApi, adminRoot } from "../../../constants/defaultValues";
import { NotificationManager } from "../../../components/common/react-notifications";

const statusText = {
  draft: "پیش‌نویس",
  upcoming: "در انتظار شروع",
  active: "فعال",
  sold_out: "تکمیل ظرفیت",
  ended: "مهلت تمام شده",
  completed: "قرعه‌کشی انجام شده",
  cancelled: "لغو شده",
};
const statusColor = {
  draft: "secondary", upcoming: "info", active: "success", sold_out: "warning",
  ended: "dark", completed: "primary", cancelled: "danger"
};

const RaffleList = ({ match }) => {
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);

  const load = () => {
    setLoading(true);
    axios.get(`${adminPathApi}/raffles`).then((res) => {
      setItems(res.data.data || []);
      setLoading(false);
    }).catch(() => {
      setLoading(false);
      NotificationManager.error("خطا در دریافت قرعه‌کشی‌ها", null, 1800, null, null, "filled");
    });
  };

  useEffect(load, []);

  const remove = (id) => {
    if (!window.confirm("قرعه‌کشی حذف شود؟")) return;
    axios.delete(`${adminPathApi}/raffles/${id}`).then((res) => {
      NotificationManager.success(res.data.message, null, 1500, null, null, "filled");
      load();
    }).catch((error) => {
      NotificationManager.error(error.response?.data?.message || "حذف انجام نشد", null, 1800, null, null, "filled");
    });
  };

  return (
    <>
      <Row>
        <Colxx xxs="12">
          <div className="d-flex justify-content-between align-items-center flex-wrap">
            <Breadcrumb heading="menu.raffles" match={match} />
            <div className="mb-3">
              <Link className="btn btn-outline-primary ml-2" to={`${adminRoot}/raffles/settings`}>تنظیمات</Link>
              <Link className="btn btn-primary" to={`${adminRoot}/raffles/create`}>افزودن قرعه‌کشی</Link>
            </div>
          </div>
          <Separator className="mb-4" />
        </Colxx>
      </Row>

      {loading ? <div className="loading" /> : (
        <Row>
          {items.length === 0 && <Colxx xxs="12"><Card><CardBody className="text-center py-5">هنوز قرعه‌کشی ایجاد نشده است.</CardBody></Card></Colxx>}
          {items.map((item) => {
            const percent = item.capacity ? Math.min(100, Math.round((item.sold_tickets / item.capacity) * 100)) : 0;
            return (
              <Colxx xxs="12" lg="6" className="mb-4" key={item.id}>
                <Card className="h-100 overflow-hidden">
                  <div className="d-flex flex-column flex-md-row">
                    <div style={{width: 180, minHeight: 160, background: "#eef5fb"}} className="d-none d-md-block">
                      {item.image && <img src={item.image} alt="" style={{width: "100%", height: "100%", objectFit: "cover"}} />}
                    </div>
                    <CardBody className="flex-grow-1">
                      <div className="d-flex justify-content-between align-items-start mb-2">
                        <div>
                          <h5 className="mb-1">{item.title}</h5>
                          <small className="text-muted">{item.property_title}</small>
                        </div>
                        <Badge color={statusColor[item.computed_status] || "secondary"} pill>{statusText[item.computed_status] || item.computed_status}</Badge>
                      </div>
                      <div className="d-flex justify-content-between mt-3 mb-1 text-small">
                        <span>فروش بلیط: {item.sold_tickets} / {item.capacity}</span>
                        <span>{percent}%</span>
                      </div>
                      <Progress value={percent} className="mb-3" />
                      <div className="text-muted text-small mb-3">
                        <div>شروع: {item.start_at}</div>
                        <div>پایان: {item.end_at}</div>
                        <div>قیمت بلیط: {Number(item.ticket_price || 0).toLocaleString("fa-IR")} تومان</div>
                      </div>
                      {item.winner && <div className="alert alert-success py-2">برنده: {item.winner.name || "-"} / {item.winner.code}</div>}
                      <div className="d-flex flex-wrap">
                        <Link className="btn btn-sm btn-primary ml-2 mb-2" to={`${adminRoot}/raffles/edit/${item.id}`}>ویرایش</Link>
                        <Link className="btn btn-sm btn-outline-primary ml-2 mb-2" to={`${adminRoot}/raffles/tickets/${item.id}`}>بلیط‌ها و قرعه‌کشی</Link>
                        <Button size="sm" color="danger" outline className="mb-2" onClick={() => remove(item.id)}>حذف</Button>
                      </div>
                    </CardBody>
                  </div>
                </Card>
              </Colxx>
            );
          })}
        </Row>
      )}
    </>
  );
};

export default RaffleList;
