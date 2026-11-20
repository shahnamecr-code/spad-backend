import React, { useCallback, useEffect, useState } from "react";
import { Link, Redirect } from "react-router-dom";
import { useSelector } from "react-redux";
import { adminRoot, UserRole } from "../../../constants/defaultValues";
import { homeServices } from "../../../constants/homeServices";
import { actionError, request } from "./api";
import DoctorPanel from "./DoctorPanel";
import NeighborsPanel from "./NeighborsPanel";
import LeaguePanel from "./LeaguePanel";

const endpoints = {
  controls: "/home-services",
  assistant: `${adminRoot}/ai-assistant`,
};

function number(value) {
  return value === null || value === undefined ? "—" : Number(value).toLocaleString("fa-IR");
}

const metrics = [
  ["requests", "درخواست‌ها در ۳۰ روز اخیر"],
  ["active_today", "کاربران فعال امروز"],
  ["active_last_30_days", "کاربران فعال در ۳۰ روز اخیر"],
  ["average_ms", "میانگین پاسخ (هزارم ثانیه)"],
  ["errors_last_30_days", "خطاهای سرور در ۳۰ روز اخیر"],
];

const actionNames = {
  GET: "خواندن", POST: "ثبت", PUT: "ویرایش کامل", PATCH: "ویرایش", DELETE: "حذف",
  index: "فهرست", store: "ثبت", update: "ویرایش", show: "مشاهده", destroy: "حذف",
};

function ServiceMetrics({ settings }) {
  const completed = Object.entries(settings.completed_actions || {});
  return (
    <section className="card mb-3">
      <div className="card-body">
        <h2 className="h5">آمار سرویس</h2>
        <div className="row">
          {metrics.map(([key, label]) => (
            <div className="col-6 col-md-4 col-lg mb-2" key={key}>
              <div className="border rounded h-100 p-2">
                <span className="d-block small">{label}</span>
                <strong>{number(settings[key])}</strong>
              </div>
            </div>
          ))}
        </div>
        {completed.length > 0 && (
          <details className="mt-2">
            <summary>درخواست‌های موفق به تفکیک عملیات</summary>
            <ul className="list-group mt-2">
              {completed.map(([name, value]) => (
                <li key={name} className="list-group-item d-flex justify-content-between">
                  <span>{actionNames[name] || name}</span><strong>{number(value)}</strong>
                </li>
              ))}
            </ul>
          </details>
        )}
      </div>
    </section>
  );
}

function ServiceSettings({ settings, onSaved }) {
  const { enabled, writes_enabled, writes_per_minute, guide } = settings;
  const [draft, setDraft] = useState({ enabled, writes_enabled, writes_per_minute, guide });
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [saved, setSaved] = useState(false);

  useEffect(() => setDraft({ enabled, writes_enabled, writes_per_minute, guide }), [enabled, writes_enabled, writes_per_minute, guide]);
  const field = (name, value) => {
    setDraft((previous) => ({ ...previous, [name]: value }));
    setSaved(false);
  };

  async function submit(event) {
    event.preventDefault();
    if (busy) return;
    setBusy(true);
    setError("");
    setSaved(false);
    try {
      const result = await request("put", `${endpoints.controls}/${settings.service}`, {
        enabled: Boolean(draft.enabled),
        writes_enabled: Boolean(draft.writes_enabled),
        writes_per_minute: Number(draft.writes_per_minute),
        guide: draft.guide?.trim() || null,
      });
      onSaved(result.data);
      setSaved(true);
    } catch (exception) {
      setError(actionError(exception, "تنظیمات سرویس ذخیره نشد."));
    } finally {
      setBusy(false);
    }
  }

  return (
    <section className="card mb-3">
      <div className="card-body">
        <h2 className="h5">تنظیمات سرویس</h2>
        {error && <p role="alert" className="alert alert-danger">{error}</p>}
        {saved && <p role="status" className="alert alert-success">تنظیمات ذخیره شد.</p>}
        <form onSubmit={submit}>
          <fieldset disabled={busy}>
            <label className="d-block mb-2">
              <input type="checkbox" checked={!!draft.enabled} onChange={(event) => field("enabled", event.target.checked)} />
              <span className="mx-2">سرویس فعال باشد</span>
            </label>
            <label className="d-block mb-3">
              <input type="checkbox" checked={!!draft.writes_enabled} onChange={(event) => field("writes_enabled", event.target.checked)} />
              <span className="mx-2">ثبت و تغییر اطلاعات کاربران مجاز باشد</span>
            </label>
            <div className="form-row">
              <label className="form-group col-12 col-md-4">
                سقف درخواست ثبت یا ویرایش در دقیقه
                <input className="form-control mt-1" type="number" min={1} max={120} required value={draft.writes_per_minute} onChange={(event) => field("writes_per_minute", event.target.value)} />
              </label>
              <label className="form-group col-12 col-md-8">
                راهنمای سرویس
                <textarea className="form-control mt-1" rows={3} maxLength={600} value={draft.guide || ""} onChange={(event) => field("guide", event.target.value)} />
              </label>
            </div>
            <button className="btn btn-primary" type="submit">{busy ? "در حال ذخیره…" : "ذخیره تنظیمات"}</button>
          </fieldset>
        </form>
      </div>
    </section>
  );
}

const panels = { doctor: DoctorPanel, neighbors: NeighborsPanel, league: LeaguePanel };

export default function HomeServicesAdmin({ match }) {
  const currentUser = useSelector((state) => state.authUser?.currentUser);
  const allowed = Array.isArray(currentUser?.roles) && currentUser.roles.includes(UserRole.Admin);
  const service = homeServices.find(({ id }) => id === match.params.service);
  const [records, setRecords] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const load = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const result = await request("get", endpoints.controls);
      setRecords(Array.isArray(result.data) ? result.data : []);
    } catch (exception) {
      setError(actionError(exception, "آمار و تنظیمات سرویس دریافت نشد."));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (allowed) void load();
  }, [allowed, load]);

  if (!allowed) {
    return <div className="alert alert-warning" dir="rtl">این صفحه ویژه مدیر اصلی اسپاد است.</div>;
  }
  if (!service) return <Redirect to={`${adminRoot}/home-services/scan`} />;

  const settings = records.find((record) => record.service === service.id);
  const Panel = panels[service.id];
  return (
    <div dir="rtl">
      <header className="card mb-3">
        <div className="card-body d-flex flex-wrap align-items-center justify-content-between">
          <h1 className="h4 mb-2">{service.title}</h1>
          <button type="button" className="btn btn-outline-primary mb-2" disabled={loading} onClick={load}>به‌روزرسانی آمار</button>
          <nav className="w-100" aria-label="مدیریت خدمات خانه">
            {homeServices.map(({ id, title }) => (
              <Link key={id} className={`btn btn-sm mb-1 ml-1 ${id === service.id ? "btn-primary" : "btn-outline-primary"}`} aria-current={id === service.id ? "page" : undefined} to={`${adminRoot}/home-services/${id}`}>
                {title}
              </Link>
            ))}
          </nav>
        </div>
      </header>
      {loading && <p role="status">در حال دریافت آمار و تنظیمات…</p>}
      {error && <p role="alert" className="alert alert-danger">{error}</p>}
      {!loading && !error && !settings && <p className="alert alert-warning">تنظیمات این سرویس در دسترس نیست.</p>}
      {settings && <ServiceMetrics settings={settings} />}
      {settings && <ServiceSettings key={service.id} settings={settings} onSaved={setRecords} />}
      {Panel && <Panel key={service.id} request={request} assistantUrl={endpoints.assistant} />}
    </div>
  );
}
