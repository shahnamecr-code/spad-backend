import React, { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { actionError } from "./api";

const statusLabels = {
  draft: "پیش‌نویس",
  queued: "در صف",
  analyzing: "در حال بررسی",
  completed: "بررسی شده",
  ready: "نتیجه آماده",
  referral: "ارجاع",
  unavailable: "نتیجه ناموجود",
};

function CategoryForm({ category, request, onSaved }) {
  const { enabled, label, description, guide } = category;
  const [draft, setDraft] = useState({ enabled, label, description, guide });
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [saved, setSaved] = useState(false);

  useEffect(() => setDraft({ enabled, label, description, guide }), [enabled, label, description, guide]);

  function field(name, value) {
    setDraft((previous) => ({ ...previous, [name]: value }));
    setSaved(false);
  }

  async function submit(event) {
    event.preventDefault();
    if (busy) return;
    setBusy(true);
    setError("");
    try {
      const { data } = await request("put", `/home-doctor/categories/${encodeURIComponent(category.code)}`, {
        enabled: Boolean(draft.enabled),
        label: draft.label?.trim() || null,
        description: draft.description?.trim() || null,
        guide: draft.guide?.trim() || null,
      });
      onSaved(data);
      setSaved(true);
    } catch (exception) {
      setError(actionError(exception, "دسته ذخیره نشد."));
    } finally {
      setBusy(false);
    }
  }

  return (
    <details className="border rounded p-3 mb-2">
      <summary>{category.label} · {category.enabled ? "فعال" : "غیرفعال"}</summary>
      <form className="mt-3" onSubmit={submit}>
        {error && <p className="alert alert-danger" role="alert">{error}</p>}
        {saved && <p className="alert alert-success" role="status">دسته ذخیره شد.</p>}
        <fieldset disabled={busy}>
          <label className="d-block mb-2">
            <input type="checkbox" checked={!!draft.enabled} onChange={(event) => field("enabled", event.target.checked)} />
            <span className="mx-2">این دسته در دسترس باشد</span>
          </label>
          <div className="form-row">
            <label className="form-group col-12 col-lg-4">نام دسته
              <input className="form-control mt-1" value={draft.label || ""} maxLength={100} onChange={(event) => field("label", event.target.value)} />
            </label>
            <label className="form-group col-12 col-lg-8">توضیح
              <input className="form-control mt-1" value={draft.description || ""} maxLength={300} onChange={(event) => field("description", event.target.value)} />
            </label>
          </div>
          <label className="form-group d-block">راهنمای اولیه
            <textarea className="form-control mt-1" rows={3} value={draft.guide || ""} maxLength={2400} onChange={(event) => field("guide", event.target.value)} />
          </label>
          <button className="btn btn-primary" type="submit">ذخیره این دسته</button>
        </fieldset>
      </form>
    </details>
  );
}

const number = (value) => value == null ? "—" : Number(value).toLocaleString("fa-IR");

export default function DoctorPanel({ request, assistantUrl }) {
  const [summary, setSummary] = useState(null);
  const [error, setError] = useState("");
  const [revision, setRevision] = useState(0);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let live = true;
    setLoading(true);
    setError("");
    request("get", "/home-doctor/summary")
      .then(({ data }) => { if (live) setSummary(data); })
      .catch((exception) => { if (live) setError(actionError(exception, "خلاصه دکتر خانه دریافت نشد.")); })
      .finally(() => { if (live) setLoading(false); });
    return () => { live = false; };
  }, [request, revision]);

  return (
    <section className="card mb-3">
      <div className="card-body">
        <div className="d-flex flex-wrap justify-content-between align-items-center mb-2">
          <h2 className="h5">آمار و دسته‌های دکتر خانه</h2>
          <button className="btn btn-outline-primary btn-sm" type="button" disabled={loading} onClick={() => setRevision((value) => value + 1)}>به‌روزرسانی</button>
        </div>
        {loading && <p role="status">در حال دریافت وضعیت…</p>}
        {error && <p role="alert" className="alert alert-danger">{error}</p>}
        {summary && (
          <>
            <div className="row mb-3">
              <div className="col-12 col-md-6">
                <ul className="list-unstyled mb-2">
                  <li>پرونده‌ها: {number(summary.case_count)}</li>
                  <li>بازخوردها: {number(summary.feedback_count)} · میانگین: {number(summary.average_feedback)}</li>
                  {Object.entries(summary.status_counts || {}).map(([status, count]) => (
                    <li key={status}>{statusLabels[status] || status}: {number(count)}</li>
                  ))}
                </ul>
              </div>
              <div className="col-12 col-md-6">
                <ul className="list-unstyled mb-2">
                  <li>اتصال تحلیل هوشمند: {summary.provider_configured ? "آماده" : "تنظیم نشده یا خاموش"}</li>
                  <li>پردازش عکس: {summary.photos_available ? "در دسترس" : "در دسترس نیست"}</li>
                  <li>صف پردازش: {summary.queue_connection || "نامشخص"}</li>
                </ul>
                <Link to={assistantUrl}>تنظیمات دستیار هوشمند</Link>
              </div>
            </div>
            {(summary.categories || []).map((category) => (
              <CategoryForm key={category.code} category={category} request={request} onSaved={setSummary} />
            ))}
          </>
        )}
      </div>
    </section>
  );
}
