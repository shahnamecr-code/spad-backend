import React, { useEffect, useState } from "react";
import { Badge, Button, Card, CardBody, Input, Row, Table } from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { adminPathApi } from "../../../constants/defaultValues";
import { NotificationManager } from "../../../components/common/react-notifications";

const RaffleTickets = ({ match }) => {
  const id = match.params.id;
  const [raffle, setRaffle] = useState(null);
  const [tickets, setTickets] = useState([]);
  const [q, setQ] = useState("");
  const [announcement, setAnnouncement] = useState("");

  const load = () => {
    axios.get(`${adminPathApi}/raffles/${id}`).then((res) => setRaffle(res.data.data));
    axios.get(`${adminPathApi}/raffles/${id}/tickets`, {params: {q}}).then((res) => setTickets(res.data.data || []));
  };
  useEffect(load, [id]);

  const pickWinner = (ticket) => {
    if (!window.confirm(`بلیط ${ticket.code} به عنوان برنده نهایی ثبت شود؟ با این کار وضعیت سایر بلیط‌ها «برنده نشده» خواهد شد.`)) return;
    axios.post(`${adminPathApi}/raffles/${id}/tickets/${ticket.id}/winner`, {announcement}).then((res) => {
      NotificationManager.success(res.data.message, null, 2200, null, null, "filled"); load();
    }).catch((e) => NotificationManager.error(e.response?.data?.message || "ثبت برنده انجام نشد", null, 2200, null, null, "filled"));
  };

  const reset = () => {
    if (!window.confirm("نتیجه فعلی قرعه‌کشی پاک و بلیط‌ها دوباره فعال شوند؟")) return;
    axios.post(`${adminPathApi}/raffles/${id}/winner/reset`).then((res) => { NotificationManager.success(res.data.message, null, 1600, null, null, "filled"); load(); });
  };

  return (
    <>
      <Row><Colxx xxs="12"><Breadcrumb heading="مدیریت بلیط‌ها و اعلام برنده" match={match}/><Separator className="mb-4"/></Colxx></Row>
      {raffle && <Row className="mb-4">
        <Colxx xxs="12"><Card><CardBody>
          <div className="d-flex justify-content-between flex-wrap align-items-center">
            <div><h4 className="mb-1">{raffle.title}</h4><span className="text-muted">فروش: {raffle.sold_tickets} از {raffle.capacity} بلیط</span></div>
            <div>{raffle.winner_ticket_id ? <><Badge color="success" className="p-2 ml-2">برنده ثبت شده</Badge><Button size="sm" color="danger" outline onClick={reset}>بازنشانی نتیجه</Button></> : <Badge color="warning" className="p-2">انتخاب برنده فقط به صورت دستی توسط اپراتور</Badge>}</div>
          </div>
        </CardBody></Card></Colxx>
      </Row>}
      <Row className="mb-3"><Colxx md="7"><Input placeholder="جستجو با کد بلیط، شماره، نام یا موبایل" value={q} onChange={(e) => setQ(e.target.value)} onKeyDown={(e) => e.key === "Enter" && load()} /></Colxx><Colxx md="2"><Button color="primary" onClick={load}>جستجو</Button></Colxx></Row>
      <Row className="mb-4"><Colxx xxs="12"><Card><CardBody><label>متن اعلام نتیجه (اختیاری)</label><Input type="textarea" rows="2" value={announcement} onChange={(e) => setAnnouncement(e.target.value)} placeholder="مثلاً: ضمن تبریک، جهت هماهنگی تحویل با برنده تماس گرفته خواهد شد." /></CardBody></Card></Colxx></Row>
      <Row><Colxx xxs="12"><Card><CardBody className="table-responsive">
        <Table hover responsive>
          <thead><tr><th>#</th><th>کد بلیط</th><th>کاربر</th><th>موبایل</th><th>کد رهگیری پرداخت</th><th>مبلغ</th><th>تاریخ خرید</th><th>وضعیت</th><th></th></tr></thead>
          <tbody>{tickets.map((t) => <tr key={t.id}>
            <td>{t.sequence_no}</td><td dir="ltr">{t.code}</td><td>{t.user.name || "-"}</td><td>{t.user.mobile || "-"}</td><td dir="ltr">{t.order.tracking_code}</td><td>{Number(t.order.amount || 0).toLocaleString("fa-IR")}</td><td>{t.purchased_at}</td>
            <td><Badge color={t.status === "winner" ? "success" : t.status === "not_winner" ? "danger" : "primary"}>{t.status === "winner" ? "برنده" : t.status === "not_winner" ? "برنده نشده" : "فعال"}</Badge></td>
            <td>{t.status !== "winner" && <Button size="sm" color="success" outline onClick={() => pickWinner(t)}>ثبت برنده</Button>}</td>
          </tr>)}</tbody>
        </Table>
        {!tickets.length && <div className="text-center text-muted py-4">بلیطی ثبت نشده است.</div>}
      </CardBody></Card></Colxx></Row>
    </>
  );
};
export default RaffleTickets;
