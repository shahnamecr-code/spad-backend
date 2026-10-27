import React, { useEffect, useState } from "react";
import axios from "axios";
import { Badge, Button, Card, CardBody, FormGroup, Input, Label, Row, Table } from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { adminPathApi } from "../../../constants/defaultValues";
import { NotificationManager } from "../../../components/common/react-notifications";

const money = (value) => new Intl.NumberFormat("fa-IR").format(Number(value || 0));
const date = (value) => value ? new Date(value).toLocaleString("fa-IR") : "—";

const WalletUser = ({ match }) => {
  const id = match.params.id;
  const [data, setData] = useState(null);
  const [direction, setDirection] = useState("credit");
  const [amount, setAmount] = useState("");
  const [description, setDescription] = useState("");
  const [saving, setSaving] = useState(false);

  const load = () => axios.get(`${adminPathApi}/wallets/${id}`).then((res) => setData(res.data.data));
  useEffect(() => { load(); }, [id]);

  const adjust = () => {
    if (!amount || Number(amount) <= 0 || description.trim().length < 3) {
      return NotificationManager.warning("مبلغ و علت تغییر موجودی را کامل وارد کنید.", null, 1800, null, null, "filled");
    }
    setSaving(true);
    axios.post(`${adminPathApi}/wallets/${id}/adjust`, { direction, amount: Number(amount), description })
      .then((res) => { NotificationManager.success(res.data.message, null, 1600, null, null, "filled"); setAmount(""); setDescription(""); load(); })
      .catch((e) => NotificationManager.error(e.response?.data?.message || "عملیات انجام نشد", null, 2200, null, null, "filled"))
      .finally(() => setSaving(false));
  };

  const toggle = () => axios.post(`${adminPathApi}/wallets/${id}/status`, { status: data.wallet.status === "active" ? "blocked" : "active" }).then(() => load());

  if (!data) return <div className="loading" />;

  return <>
    <Row><Colxx xxs="12"><Breadcrumb heading="جزئیات کیف پول" match={match}/><Separator className="mb-4"/></Colxx></Row>
    <Row>
      <Colxx xxs="12" lg="5" className="mb-4">
        <Card className="mb-4"><CardBody>
          <div className="d-flex align-items-start justify-content-between">
            <div><small className="text-muted">موجودی فعلی</small><h1 className="mt-2 mb-1">{money(data.wallet.balance)} <small style={{fontSize: 14}}>تومان</small></h1><strong>{data.user.full_name}</strong><div className="text-muted mt-1" dir="ltr">{data.user.mobile}</div></div>
            <Badge color={data.wallet.status === "active" ? "success" : "danger"}>{data.wallet.status === "active" ? "فعال" : "مسدود"}</Badge>
          </div>
          <Button className="mt-4" color={data.wallet.status === "active" ? "outline-danger" : "outline-success"} onClick={toggle}>{data.wallet.status === "active" ? "مسدود کردن کیف پول" : "فعال کردن کیف پول"}</Button>
        </CardBody></Card>
        <Card><CardBody>
          <h3>اصلاح دستی موجودی</h3><p className="text-muted">هر تغییر با نام اپراتور و علت در دفتر تراکنش ثبت می‌شود.</p>
          <FormGroup><Label>نوع عملیات</Label><Input type="select" value={direction} onChange={(e) => setDirection(e.target.value)}><option value="credit">افزایش موجودی</option><option value="debit">کاهش موجودی</option></Input></FormGroup>
          <FormGroup><Label>مبلغ (تومان)</Label><Input type="number" min="1" value={amount} onChange={(e) => setAmount(e.target.value)} /></FormGroup>
          <FormGroup><Label>علت تغییر موجودی</Label><Input type="textarea" rows="4" value={description} onChange={(e) => setDescription(e.target.value)} placeholder="مثلاً اصلاح پرداخت، اعتبار هدیه، برگشت وجه..." /></FormGroup>
          <Button color={direction === "credit" ? "success" : "danger"} block disabled={saving} onClick={adjust}>{saving ? "در حال ثبت..." : direction === "credit" ? "افزایش موجودی" : "کاهش موجودی"}</Button>
        </CardBody></Card>
      </Colxx>
      <Colxx xxs="12" lg="7">
        <Card className="mb-4"><CardBody><h3>آخرین تراکنش‌ها</h3><Table responsive hover size="sm"><thead><tr><th>نوع</th><th>مبلغ</th><th>مانده</th><th>تاریخ</th></tr></thead><tbody>{data.transactions.map((t) => <tr key={t.id}><td><Badge color={t.direction === "credit" ? "success" : "danger"}>{t.direction === "credit" ? "واریز" : "برداشت"}</Badge><div className="small mt-1">{t.title || t.type}</div></td><td>{t.direction === "credit" ? "+" : "−"}{money(t.amount)}</td><td>{money(t.balance_after)}</td><td>{date(t.created_at)}</td></tr>)}</tbody></Table></CardBody></Card>
        <Card><CardBody><h3>آخرین شارژهای آنلاین</h3><Table responsive hover size="sm"><thead><tr><th>مبلغ</th><th>درگاه</th><th>وضعیت</th><th>رهگیری</th><th>تاریخ</th></tr></thead><tbody>{data.topups.map((t) => <tr key={t.id}><td>{money(t.amount)}</td><td>{t.gateway?.title || "—"}</td><td><Badge color={t.status === "paid" ? "success" : t.status === "failed" ? "danger" : "warning"}>{t.status === "paid" ? "موفق" : t.status === "failed" ? "ناموفق" : "در انتظار"}</Badge></td><td dir="ltr">{t.reference_id || t.tracking_code}</td><td>{date(t.created_at)}</td></tr>)}</tbody></Table></CardBody></Card>
      </Colxx>
    </Row>
  </>;
};

export default WalletUser;
