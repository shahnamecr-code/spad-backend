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
  read: "مشاهده و دریافت اطلاعات",
  write: "ثبت یا تغییر اطلاعات",
  analysis: "درخواست تحلیل",
  vote: "ثبت رأی",
  report: "ثبت گزارش",
  comments: "ثبت دیدگاه",
  entries: "ارسال اثر",
  feedback: "ثبت بازخورد",
};

const scanMetrics = [
  ["projects", "پروژه‌های ذخیره‌شده"],
  ["rooms", "فضاهای ثبت‌شده"],
  ["photos", "عکس‌های ذخیره‌شده"],
  ["photo_bytes", "حجم ثبت‌شدهٔ عکس‌ها (بایت)"],
  ["linked_renovations", "نقشه‌های پیوندخورده به بازسازی"],
];

function ScanMetrics({ summary }) {
  return (
    <section className="card mb-3">
      <div className="card-body">
        <h2 className="h5">آمار پروژه‌های اسپاد اسکن</h2>
        {summary ? (
          <dl className="row mb-0">
            {scanMetrics.map(([name, title]) => (
              <div className="col-6 col-md-4 col-lg mb-2" key={name}>
                <div className="border rounded h-100 p-2">
                  <dt className="small">{title}</dt>
                  <dd className="mb-0 font-weight-bold">{number(summary[name])}</dd>
                </div>
              </div>
            ))}
          </dl>
        ) : <p className="mb-0">آمار پروژه‌های ذخیره‌شده در دسترس نیست.</p>}
      </div>
    </section>
  );
}

const householdMetrics = {
  locations: "محل‌های مصرف",
  accounts: "شناسه‌های قبض",
  bills: "دوره‌های قبض",
  homes: "خانه‌های ثبت‌شده",
  devices: "وسایل ثبت‌شده",
  documents: "مدارک خصوصی",
  groups: "گروه‌های خانه",
  tasks: "وظایف تعریف‌شده",
  members: "عضویت‌ها",
  projects: "پروژه‌های نور",
  plants: "گیاهان ثبت‌شده",
  photos: "عکس‌های خصوصی",
  care_logs: "فعالیت‌های نگهداری",
  connections: "اتصال‌های خانه هوشمند",
  actions: "رخدادهای ثبت‌شده",
};

function HouseholdInventory({ counts }) {
  const rows = Object.entries(counts);
  return (
    <section className="card mb-3">
      <div className="card-body">
        <h2 className="h5">آمار داده‌های ثبت‌شده</h2>
        {rows.length > 0 ? (
          <dl className="row mb-0">
            {rows.map(([key, count]) => (
              <div className="col-6 col-md-4 mb-2" key={key}>
                <div className="border rounded h-100 p-2">
                  <dt className="small">{householdMetrics[key]}</dt>
                  <dd className="mb-0">{number(count)}</dd>
                </div>
              </div>
            ))}
          </dl>
        ) : <p className="mb-0">آمار داده‌های سرویس در دسترس نیست.</p>}
      </div>
    </section>
  );
}

const providerTitles = {
  garden: "تشخیص گونهٔ گیاه",
  book: "خواندن برچسب دستگاه",
  energy: "ارائه‌دهندهٔ استعلام قبض",
  smart: "درگاه‌های خانهٔ هوشمند",
};
const providerStates = {
  not_configured: "پیکربندی نشده",
  configured_unchecked: "پیکربندی شده؛ نتیجهٔ درخواست ثبت نشده است",
  responding: "آخرین درخواست ثبت‌شده پاسخ گرفته است",
  quota_exhausted: "سهمیه در آخرین درخواست کافی نبوده است",
  rate_limited: "آخرین درخواست با محدودیت نرخ روبه‌رو شده است",
  auth_error: "احراز هویت ارائه‌دهنده در آخرین درخواست ناموفق بوده است",
  unavailable: "آخرین درخواست کامل نشده است",
  invalid_response: "پاسخ آخرین درخواست معتبر نبوده است",
  configuration_ready: "پیکربندی معتبر است؛ اتصال زنده بررسی نشده است",
  configuration_incomplete: "پیکربندی کامل یا معتبر نیست",
  unknown: "وضعیت پیکربندی در دسترس نیست",
};
const utilityNames = { water: "آب", electricity: "برق", gas: "گاز" };
const readiness = (value) => value === true ? "در دسترس" : value === false ? "در دسترس نیست" : "نامشخص";

function ProviderStatus({ service, details }) {
  const rows = [];
  let settingsLink = null;
  if (service === "garden" || service === "book") {
    rows.push(["آماده‌سازی امن عکس", readiness(details.image_preparation_ready)]);
    if (details.last_observed_at) {
      const observed = new Date(details.last_observed_at);
      if (Number.isFinite(observed.getTime())) {
        rows.push(["آخرین نتیجهٔ ثبت‌شده", observed.toLocaleString("fa-IR", { timeZone: "Asia/Tehran" })]);
      }
    }
    settingsLink = <Link to={endpoints.assistant}>تنظیمات دستیار هوشمند</Link>;
  } else if (service === "energy") {
    const enabled = (details.inquiry_services || []).map((id) => utilityNames[id]).filter(Boolean);
    rows.push(
      ["استعلام‌های مجاز در پیکربندی", details.state === "unknown" ? "نامشخص" : (enabled.join("، ") || "هیچ‌کدام")],
      ["پرداخت قبض", readiness(details.payment_enabled)],
      ["تشخیص متن قبض", readiness(details.ocr_enabled)],
      ["ذخیرهٔ عکس رسید", readiness(details.receipt_images_ready)],
    );
    settingsLink = <Link to={`${adminRoot}/web-services`}>تنظیمات اتصال سرویس‌ها</Link>;
  } else {
    rows.push(
      ["درگاه‌های مجاز در پیکربندی", number(details.gateway_count)],
      ["چراغ‌های مجاز برای مشاهده", number(details.light_count)],
      ["چراغ‌های دارای مجوز کنترل", number(details.control_allowed_count)],
    );
  }
  return (
    <section className="card mb-3">
      <div className="card-body">
        <h2 className="h5">{providerTitles[service]}</h2>
        <p role="status">{providerStates[details.state] || providerStates.unknown}</p>
        <dl className="row mb-0">
          {rows.map(([title, value]) => (
            <div className="col-12 col-md-6 col-lg-4 mb-2" key={title}>
              <div className="border rounded p-2 h-100">
                <dt className="small">{title}</dt><dd className="mb-0">{value}</dd>
              </div>
            </div>
          ))}
        </dl>
        {settingsLink}
      </div>
    </section>
  );
}

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
      {settings && service.id === "scan" && <ScanMetrics summary={settings.scan_summary} />}
      {settings?.household_inventory && <HouseholdInventory counts={settings.household_inventory} />}
      {settings?.provider_status && providerTitles[service.id] && <ProviderStatus service={service.id} details={settings.provider_status} />}
      {settings && <ServiceSettings key={service.id} settings={settings} onSaved={setRecords} />}
      {Panel && <Panel key={service.id} request={request} assistantUrl={endpoints.assistant} />}
    </div>
  );
}
