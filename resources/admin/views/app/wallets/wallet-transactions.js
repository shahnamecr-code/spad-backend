import React, { useEffect, useState } from "react";
import axios from "axios";
import { Badge, Button, Card, CardBody, Input, Row, Table } from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { adminPathApi } from "../../../constants/defaultValues";

const money = (v) => new Intl.NumberFormat("fa-IR").format(Number(v || 0));
const date = (v) => v ? new Date(v).toLocaleString("fa-IR") : "—";

const WalletTransactions = ({ match }) => {
  const [result, setResult] = useState({ data: [] });
  const [search, setSearch] = useState("");
  const [direction, setDirection] = useState("");

  const load = (page = 1) => axios.get(`${adminPathApi}/wallets/transactions/all`, { params: { page, per_page: 30, search, direction } }).then((r) => setResult(r.data));
  useEffect(() => { load(1); }, []);

  return <>
    <Row><Colxx xxs="12"><Breadcrumb heading="تراکنش‌های کیف پول" match={match}/><Separator className="mb-4"/></Colxx></Row>
    <Card><CardBody>
      <div className="d-flex flex-wrap mb-4" style={{gap: 8}}><Input style={{maxWidth: 320}} placeholder="نام، موبایل یا کد مرجع" value={search} onChange={(e) => setSearch(e.target.value)} /><Input type="select" style={{maxWidth: 180}} value={direction} onChange={(e) => setDirection(e.target.value)}><option value="">همه تراکنش‌ها</option><option value="credit">واریز</option><option value="debit">برداشت</option></Input><Button color="primary" onClick={() => load(1)}>اعمال فیلتر</Button></div>
      <Table responsive hover><thead><tr><th>کاربر</th><th>نوع</th><th>مبلغ</th><th>موجودی بعد</th><th>شرح</th><th>اپراتور</th><th>تاریخ</th></tr></thead><tbody>{(result.data || []).map((t) => <tr key={t.id}><td><strong>{t.user?.full_name}</strong><div className="small text-muted" dir="ltr">{t.user?.mobile}</div></td><td><Badge color={t.direction === "credit" ? "success" : "danger"}>{t.direction === "credit" ? "واریز" : "برداشت"}</Badge><div className="small">{t.type}</div></td><td>{t.direction === "credit" ? "+" : "−"}{money(t.amount)}</td><td>{money(t.balance_after)}</td><td>{t.description || t.title || "—"}</td><td>{t.admin ? `${t.admin.first_name || ""} ${t.admin.last_name || ""}` : "سیستم"}</td><td>{date(t.created_at)}</td></tr>)}</tbody></Table>
      {result.last_page > 1 && <div className="text-center mt-3"><Button size="sm" outline disabled={result.current_page <= 1} onClick={() => load(result.current_page - 1)}>قبلی</Button><span className="mx-3">{result.current_page} / {result.last_page}</span><Button size="sm" outline disabled={result.current_page >= result.last_page} onClick={() => load(result.current_page + 1)}>بعدی</Button></div>}
    </CardBody></Card>
  </>;
};
export default WalletTransactions;
