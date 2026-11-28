import React, { useEffect, useState } from "react";
import axios from "axios";
import { Alert, Button, Card, CardBody, Table } from "reactstrap";
import { Link } from "react-router-dom";
import { adminPathApi, adminRoot } from "../../../constants/defaultValues";

const number = new Intl.NumberFormat("fa-IR");
const localTime = new Intl.DateTimeFormat("fa-IR", {
  timeZone: "Asia/Tehran", hour: "2-digit", minute: "2-digit",
});

export default function PublicSiteUserStats() {
  const [page, setPage] = useState(1);
  const [reload, setReload] = useState(0);
  const [report, setReport] = useState(null);
  const [error, setError] = useState("");

  useEffect(() => {
    let active = true;
    setReport(null);
    setError("");
    axios.get(`${adminPathApi}/site-usage/users`, { params: { page } })
      .then(({ data }) => { if (active) setReport(data.data); })
      .catch((failure) => {
        if (active) setError(failure?.response?.status === 403
          ? "این گزارش فقط برای مدیر اصلی اسپاد در دسترس است."
          : "دریافت آمار کاربران ممکن نشد.");
      });
    return () => { active = false; };
  }, [page, reload]);

  return (
    <section dir="rtl" aria-labelledby="spad-public-user-stats">
      <div className="d-flex flex-wrap align-items-center justify-content-between mb-3">
        <h2 id="spad-public-user-stats">آمار کاربران سایت اصلی</h2>
        <div className="d-flex flex-wrap">
          {report && <Button tag={Link} to={`${adminRoot}/settings/user-statistics`} color="primary" className="m-1">کنسول آمار و فعالیت کاربران</Button>}
          <Button color="outline-primary" className="m-1" onClick={() => setReload((attempt) => attempt + 1)}>به‌روزرسانی</Button>
        </div>
      </div>
      {error && <Alert color="danger" role="alert">{error}</Alert>}
      {!report && !error && <p role="status">در حال دریافت گزارش…</p>}
      {report && (
        <>
          <div className="d-flex flex-wrap mb-3">
            <Card className="flex-fill m-1">
              <CardBody>مرورگرهای یکتای امروز <strong className="d-block h4 mt-2">
                {report.visitors === null ? "هنوز فعال نشده" : number.format(report.visitors)}
              </strong></CardBody>
            </Card>
            <Card className="flex-fill m-1">
              <CardBody>حساب‌های جدید امروز <strong className="d-block h4 mt-2">{number.format(report.new_accounts)}</strong></CardBody>
            </Card>
          </div>
          <p className="text-muted">بازدید بر اساس مرورگرهای یکتا و ثبت‌نام بر اساس حساب کاربری محاسبه می‌شود. بازهٔ گزارش به وقت تهران است.</p>
          <h3>کاربران جدید و فعالیت‌های ثبت‌شدهٔ امروز</h3>
          {report.users.length ? (
            <Table responsive striped>
              <thead><tr><th scope="col">شناسه</th><th scope="col">نام</th><th scope="col">شماره تماس</th><th scope="col">زمان ثبت‌نام</th><th scope="col">عملیات</th></tr></thead>
              <tbody>
                {report.users.map((user) => (
                  <tr key={user.id}>
                    <td>{number.format(user.id)}</td>
                    <td>{user.name || "—"}</td>
                    <td><bdi>{user.mobile || "—"}</bdi></td>
                    <td>{localTime.format(new Date(user.registered_at))}</td>
                    <td>
                      {user.actions.length ? user.actions.map((action) => (
                        <div key={action.label}>
                          {action.label} · {number.format(action.count)} بار · {localTime.format(new Date(action.latest_at))}
                        </div>
                      )) : "عملیاتی در این گزارش ثبت نشده"}
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>
          ) : <p>حساب جدیدی در این صفحه وجود ندارد.</p>}
          {report.pages > 1 && (
            <div className="d-flex align-items-center justify-content-between">
              <Button color="outline-primary" disabled={page <= 1} onClick={() => setPage(page - 1)}>قبل</Button>
              <span>صفحهٔ {number.format(page)} از {number.format(report.pages)}</span>
              <Button color="outline-primary" disabled={page >= report.pages} onClick={() => setPage(page + 1)}>بعد</Button>
            </div>
          )}
        </>
      )}
    </section>
  );
}
