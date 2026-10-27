import React, { useEffect, useState } from "react";
import axios from "axios";
import { Badge, Button, Card, CardBody, Input, Row, Table } from "reactstrap";
import { Link } from "react-router-dom";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { adminPathApi } from "../../../constants/defaultValues";

const money = (value) => new Intl.NumberFormat("fa-IR").format(Number(value || 0));

const WalletList = ({ match }) => {
  const [stats, setStats] = useState({});
  const [result, setResult] = useState({ data: [] });
  const [search, setSearch] = useState("");
  const [loading, setLoading] = useState(false);

  const load = (page = 1) => {
    setLoading(true);
    Promise.all([
      axios.get(`${adminPathApi}/wallets/stats`),
      axios.get(`${adminPathApi}/wallets`, { params: { page, per_page: 25, search } }),
    ]).then(([s, r]) => {
      setStats(s.data.data || {});
      setResult(r.data || { data: [] });
    }).finally(() => setLoading(false));
  };

  useEffect(() => { load(1); }, []);

  return <>
    <Row><Colxx xxs="12"><Breadcrumb heading="کیف پول کاربران" match={match}/><Separator className="mb-4"/></Colxx></Row>

    <Row className="mb-4">
      {[
        ["مجموع موجودی کاربران", money(stats.total_balance) + " تومان", "primary"],
        ["مجموع شارژ موفق", money(stats.paid_topups) + " تومان", "success"],
        ["کیف پول فعال", money(stats.active_wallets), "info"],
        ["تعداد تراکنش‌ها", money(stats.transactions_count), "warning"],
      ].map(([title, value, color]) => <Colxx xxs="12" sm="6" lg="3" className="mb-3" key={title}>
        <Card className="h-100"><CardBody><div className="d-flex align-items-center justify-content-between"><div><small className="text-muted">{title}</small><h3 className="mt-2 mb-0">{value}</h3></div><span className={`badge badge-${color} p-3`}><i className="iconsminds-wallet" /></span></div></CardBody></Card>
      </Colxx>)}
    </Row>

    <Card><CardBody>
      <div className="d-flex flex-wrap align-items-center justify-content-between mb-4" style={{gap: 12}}>
        <div><h3 className="mb-1">مدیریت کیف پول کاربران</h3><div className="text-muted">مشاهده موجودی، افزایش/کاهش دستی و سابقه تراکنش هر کاربر</div></div>
        <div className="d-flex" style={{gap: 8, minWidth: 320}}>
          <Input value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === "Enter" && load(1)} placeholder="نام، موبایل یا ایمیل..." />
          <Button color="primary" onClick={() => load(1)}>جستجو</Button>
        </div>
      </div>
      <Table responsive hover>
        <thead><tr><th>کاربر</th><th>موبایل</th><th>موجودی</th><th>وضعیت</th><th>مدیریت</th></tr></thead>
        <tbody>
          {loading ? <tr><td colSpan="5" className="text-center py-5">در حال دریافت...</td></tr> : (result.data || []).map((u) => <tr key={u.id}>
            <td><strong>{u.full_name || `${u.first_name || ""} ${u.last_name || ""}`}</strong><div className="small text-muted">#{u.id} {u.email ? `• ${u.email}` : ""}</div></td>
            <td dir="ltr">{u.mobile || "—"}</td>
            <td><strong>{money(u.wallet_balance)} تومان</strong></td>
            <td><Badge color={u.wallet_status === "blocked" ? "danger" : "success"}>{u.wallet_status === "blocked" ? "مسدود" : "فعال"}</Badge></td>
            <td><Link className="btn btn-sm btn-primary" to={`${match.url.replace(/\/list$/, "")}/user/${u.id}`}>جزئیات و مدیریت</Link></td>
          </tr>)}
          {!loading && !(result.data || []).length && <tr><td colSpan="5" className="text-center py-5 text-muted">کاربری پیدا نشد.</td></tr>}
        </tbody>
      </Table>
      {result.last_page > 1 && <div className="d-flex justify-content-center mt-3" style={{gap: 6}}>
        <Button size="sm" outline disabled={result.current_page <= 1} onClick={() => load(result.current_page - 1)}>قبلی</Button>
        <span className="px-3 py-2">صفحه {result.current_page} از {result.last_page}</span>
        <Button size="sm" outline disabled={result.current_page >= result.last_page} onClick={() => load(result.current_page + 1)}>بعدی</Button>
      </div>}
    </CardBody></Card>
  </>;
};

export default WalletList;
