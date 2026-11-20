import React, { useEffect, useRef, useState } from "react";
import { actionError } from "./api";

const groups = {
  posts: { label: "مطالب", statuses: ["pending", "published", "rejected", "hidden", "deleted"] },
  comments: { label: "دیدگاه‌ها", statuses: ["pending", "published", "rejected", "hidden", "deleted"] },
  reports: { label: "گزارش‌ها", statuses: ["open", "resolved", "dismissed"] },
  appeals: { label: "اعتراض‌ها", statuses: ["open", "accepted", "rejected"] },
  residencies: { label: "تأیید سکونت", statuses: ["pending", "verified", "denied", "revoked"] },
  audit: { label: "سابقه بررسی", statuses: [] },
};

const statuses = {
  pending: "در انتظار بررسی", published: "منتشرشده", rejected: "ردشده", hidden: "پنهان",
  deleted: "حذف‌شده", open: "باز", resolved: "رسیدگی‌شده", dismissed: "مختومه",
  accepted: "پذیرفته", verified: "تأییدشده", denied: "ردشده", revoked: "لغوشده",
};

const needsNote = ["verified", "rejected", "hidden", "denied", "revoked"];
const stateLabel = (value) => statuses[value] || value;
const faNumber = (value) => Number(value).toLocaleString("fa-IR");

function MediaPreview({ media, request }) {
  const [url, setUrl] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const active = useRef(false);

  useEffect(() => {
    active.current = true;
    return () => { active.current = false; };
  }, []);
  useEffect(() => () => { if (url) URL.revokeObjectURL(url); }, [url]);

  async function open() {
    setLoading(true);
    setError("");
    try {
      const blob = await request("get", `/neighbors/media/${media.id}`, undefined, { responseType: "blob" });
      if (!(blob instanceof Blob) || blob.type !== "image/jpeg") throw new Error("unexpected-media");
      if (active.current) setUrl(URL.createObjectURL(blob));
    } catch (exception) {
      if (active.current) setError(actionError(exception, "عکس در دسترس نیست."));
    } finally {
      if (active.current) setLoading(false);
    }
  }

  return (
    <div className="mb-2">
      <button className="btn btn-sm btn-outline-primary" type="button" disabled={loading} onClick={url ? () => setUrl("") : open}>
        {loading ? "در حال دریافت عکس…" : url ? "بستن عکس" : "مشاهده عکس"}
      </button>
      {error && <p role="alert" className="text-danger mt-1">{error}</p>}
      {url && <img src={url} className="d-block img-fluid mt-2 rounded" alt="عکس ضمیمه مطلب" style={{ maxHeight: 240 }} />}
    </div>
  );
}

function NeighborRow({ group, row, request, onUpdated }) {
  const [next, setNext] = useState(row.status || "");
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const options = groups[group].statuses.filter((status) => status !== "deleted");

  useEffect(() => {
    setNext(row.status || "");
    setNote("");
  }, [row.status]);

  async function submit(event) {
    event.preventDefault();
    if (busy || next === row.status) return;
    setBusy(true);
    setError("");
    try {
      await request("patch", `/neighbors/${group}/${row.id}`, { status: next, note: note.trim() || null });
      onUpdated();
    } catch (exception) {
      setError(actionError(exception, "تغییر وضعیت ذخیره نشد."));
    } finally {
      setBusy(false);
    }
  }

  if (group === "audit") {
    return (
      <li className="list-group-item">
        <strong>{groups[row.resource]?.label || row.resource} · {faNumber(row.record_id)}</strong>
        <p className="mb-1">{stateLabel(row.from_status)} ← {stateLabel(row.to_status)}</p>
        {row.note && <p className="mb-1">{row.note}</p>}
        <small dir="ltr">{row.created_at}</small>
      </li>
    );
  }

  const headline = row.title || row.region || `رکورد ${faNumber(row.id)}`;
  const related = row.target_id || row.post_id;
  const description = row.body || row.reason;
  return (
    <li className="list-group-item">
      <div className="d-flex flex-wrap justify-content-between mb-2">
        <strong>{headline}</strong>
        <span className="badge badge-light">{stateLabel(row.status)}</span>
      </div>
      <div className="small text-muted mb-2">
        شناسه {faNumber(row.id)}
        {row.region && row.region !== headline ? ` · محله ${row.region}` : ""}
        {related ? ` · شناسه محتوا ${faNumber(related)}` : ""}
        {group === "residencies" && row.user_id ? ` · شناسه حساب ${faNumber(row.user_id)}` : ""}
        {row.created_at ? <span dir="ltr"> · {row.created_at}</span> : null}
      </div>
      {description && <p style={{ whiteSpace: "pre-wrap", overflowWrap: "anywhere" }}>{description}</p>}
      {(Array.isArray(row.media) ? row.media : []).map((media) => <MediaPreview key={media.id} media={media} request={request} />)}
      {row.status !== "deleted" && options.length > 0 && (
        <form onSubmit={submit}>
          {error && <p role="alert" className="alert alert-danger">{error}</p>}
          <fieldset disabled={busy}>
            <div className="form-row">
              <label className="form-group col-12 col-md-4">
                وضعیت جدید
                <select className="form-control mt-1" value={next} onChange={(event) => setNext(event.target.value)}>
                  {options.map((status) => <option key={status} value={status}>{stateLabel(status)}</option>)}
                </select>
              </label>
              <label className="form-group col-12 col-md-8">
                یادداشت بررسی{needsNote.includes(next) && next !== row.status ? " (الزامی)" : ""}
                <input className="form-control mt-1" value={note} maxLength={500} required={needsNote.includes(next) && next !== row.status} onChange={(event) => setNote(event.target.value)} />
              </label>
            </div>
            <button className="btn btn-primary btn-sm" type="submit" disabled={next === row.status || (needsNote.includes(next) && !note.trim())}>ثبت تصمیم</button>
          </fieldset>
        </form>
      )}
    </li>
  );
}

export default function NeighborsPanel({ request }) {
  const [group, setGroup] = useState("posts");
  const [status, setStatus] = useState("pending");
  const [page, setPage] = useState(1);
  const [revision, setRevision] = useState(0);
  const [records, setRecords] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [saved, setSaved] = useState(false);

  useEffect(() => {
    let live = true;
    setLoading(true);
    setError("");
    const params = { page, ...(status ? { status } : {}) };
    request("get", `/neighbors/${group}`, undefined, { params })
      .then((response) => { if (live) setRecords(response); })
      .catch((exception) => { if (live) setError(actionError(exception, "فهرست بررسی دریافت نشد.")); })
      .finally(() => { if (live) setLoading(false); });
    return () => { live = false; };
  }, [request, group, status, page, revision]);

  function selectGroup(value) {
    setRecords(null);
    setSaved(false);
    setGroup(value);
    setStatus(groups[value].statuses[0] || "");
    setPage(1);
  }

  return (
    <section className="card mb-3">
      <div className="card-body">
        <h2 className="h5">مدیریت محتوای همسایه</h2>
        <div className="form-row">
          <label className="form-group col-12 col-md-4">بخش
            <select className="form-control mt-1" value={group} onChange={(event) => selectGroup(event.target.value)}>
              {Object.entries(groups).map(([key, option]) => <option key={key} value={key}>{option.label}</option>)}
            </select>
          </label>
          {groups[group].statuses.length > 0 && (
            <label className="form-group col-12 col-md-4">وضعیت
              <select className="form-control mt-1" value={status} onChange={(event) => {
                setRecords(null);
                setSaved(false);
                setStatus(event.target.value);
                setPage(1);
              }}>
                <option value="">همه وضعیت‌ها</option>
                {groups[group].statuses.map((value) => <option key={value} value={value}>{stateLabel(value)}</option>)}
              </select>
            </label>
          )}
        </div>
        {saved && <p role="status" className="alert alert-success">تصمیم ثبت شد.</p>}
        {loading && <p role="status">در حال دریافت موارد…</p>}
        {error && <p role="alert" className="alert alert-danger">{error} <button type="button" className="btn btn-sm btn-outline-primary" onClick={() => setRevision((value) => value + 1)}>تلاش دوباره</button></p>}
        {!loading && !error && records && (
          <>
            {records.data?.length ? (
              <ul className="list-group">
                {records.data.map((row) => <NeighborRow key={`${group}-${row.id}`} group={group} row={row} request={request} onUpdated={() => {
                  setSaved(true);
                  setRevision((value) => value + 1);
                }} />)}
              </ul>
            ) : <p>موردی در این صفحه پیدا نشد.</p>}
            <nav className="d-flex align-items-center mt-3" aria-label="صفحه‌های فهرست بررسی">
              <button className="btn btn-outline-primary btn-sm" type="button" disabled={page <= 1} onClick={() => setPage(page - 1)}>صفحه قبل</button>
              <span className="mx-2">{faNumber(page)} از {faNumber(records.last_page || 1)}</span>
              <button className="btn btn-outline-primary btn-sm" type="button" disabled={page >= records.last_page} onClick={() => setPage(page + 1)}>صفحه بعد</button>
            </nav>
          </>
        )}
      </div>
    </section>
  );
}
