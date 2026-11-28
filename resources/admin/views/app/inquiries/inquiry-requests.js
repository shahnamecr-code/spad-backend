import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import axios from 'axios';
import { adminPathApi } from '../../../constants/defaultValues';

const number = new Intl.NumberFormat('fa-IR');
const initialFilters = { search: '', status: '', payment_status: '', execution_type: '' };
const execution = [['manual', 'اپراتوری'], ['api', 'API آنلاین']];
const payments = {
  paid: ['پرداخت‌شده', 'success'], pending: ['در انتظار', 'pending'],
  failed: ['ناموفق', 'danger'], refunded: ['برگشت وجه', 'neutral'],
};
const statuses = {
  waiting_operator: ['صف اپراتور', 'pending'], processing: ['در حال پردازش', 'active'],
  needs_correction: ['منتظر اصلاح اطلاعات', 'pending'], completed: ['تکمیل‌شده', 'success'],
  failed: ['ناموفق', 'danger'], pending_payment: ['در انتظار پرداخت', 'pending'],
  rejected: ['ردشده', 'danger'], cancelled: ['لغوشده', 'neutral'],
};

const options = (items) => Object.entries(items).map(([value, [title]]) => [value, title]);

function FilterSelect({ title, all, items, value, onChange }) {
  return <label>{title}
    <select value={value} onChange={(event) => onChange(event.target.value)}>
      <option value="">{all}</option>
      {items.map(([code, text]) => <option key={code} value={code}>{text}</option>)}
    </select>
  </label>;
}

function Status({ entry, fallback }) {
  const [title, tone] = entry || ['', 'neutral'];
  return <span className={`spad-order-status is-${tone}`}>{title || fallback || '—'}</span>;
}

function orderStatus(item) {
  if (item.status === 'waiting_operator' && item.service?.partner_handler_type) return ['صف همکار', 'pending'];
  if (item.status === 'processing' && item.assigned_partner) return ['در حال انجام توسط همکار', 'active'];
  return statuses[item.status];
}

function assignee(item) {
  if (item.assigned_partner) return item.assigned_partner.organization_name || item.assigned_partner.title || 'همکار تخصصی';
  if (item.operator) return [item.operator.first_name, item.operator.last_name].filter(Boolean).join(' ') || 'اپراتور';
  return item.service?.partner_handler_type ? 'در انتظار تخصیص همکار' : '—';
}

export default function InquiryRequests({ match }) {
  const [draft, setDraft] = useState(initialFilters);
  const [query, setQuery] = useState({ filters: initialFilters, page: 1 });
  const [result, setResult] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);
  const change = (field) => (value) => setDraft((filters) => ({ ...filters, [field]: value }));
  const goToPage = (page) => setQuery((current) => ({ ...current, page }));

  useEffect(() => {
    let active = true;
    setLoading(true);
    setError(false);
    axios.get(`${adminPathApi}/inquiries/requests`, { params: { ...query.filters, page: query.page }, timeout: 15000 })
      .then(({ data }) => { if (active) setResult(data); })
      .catch(() => { if (active) setError(true); })
      .finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, [query]);

  const hasResults = !loading && !error && result?.data?.length;
  const page = result?.current_page || 1;
  const last = result?.last_page || 1;

  return (
    <section className="spad-orders" aria-labelledby="spad-orders-title" dir="rtl">
      <header className="spad-orders__head">
        <div><h1 id="spad-orders-title">سفارش‌های استعلام</h1><p>پیگیری سفارش، پرداخت و وضعیت پردازش استعلام‌های ملکی</p></div>
        {result?.total != null && !loading && !error && <span>{number.format(result.total)} سفارش</span>}
      </header>

      <form className="spad-orders__filters" onSubmit={(event) => {
        event.preventDefault();
        setQuery({ filters: { ...draft, search: draft.search.trim() }, page: 1 });
      }}>
        <label className="spad-orders__search">جست‌وجوی سفارش
          <input type="search" name="search" value={draft.search} placeholder="کد پیگیری، کاربر یا سرویس" onChange={(event) => change('search')(event.target.value)} />
        </label>
        <FilterSelect title="وضعیت سفارش" all="همهٔ وضعیت‌ها" items={options(statuses)} value={draft.status} onChange={change('status')} />
        <FilterSelect title="روش اجرا" all="همهٔ روش‌ها" items={execution} value={draft.execution_type} onChange={change('execution_type')} />
        <FilterSelect title="وضعیت پرداخت" all="همهٔ پرداخت‌ها" items={options(payments)} value={draft.payment_status} onChange={change('payment_status')} />
        <div className="spad-orders__actions">
          <button className="spad-orders__primary" type="submit" disabled={loading}>اعمال فیلتر</button>
          <button className="spad-orders__secondary" type="button" disabled={loading || Object.values(draft).every((value) => !value) && Object.values(query.filters).every((value) => !value)}
            onClick={() => { setDraft(initialFilters); setQuery({ filters: initialFilters, page: 1 }); }}>پاک‌کردن</button>
        </div>
      </form>

      <div className="spad-orders__results" aria-busy={loading}>
        {loading ? <p role="status">در حال دریافت سفارش‌ها…</p> : error ? (
          <div className="spad-orders__empty" role="alert"><p>دریافت سفارش‌ها ممکن نشد.</p>
            <button type="button" className="spad-orders__secondary" onClick={() => goToPage(query.page)}>تلاش دوباره</button></div>
        ) : hasResults ? (<>
          <p className="spad-orders__scroll-note">برای دیدن ستون‌های بیشتر، جدول را افقی پیمایش کنید.</p>
          <div className="table-responsive spad-orders__table" tabIndex={0} role="region" aria-label="جدول سفارش‌های استعلام">
            <table className="table">
              <thead><tr><th scope="col">کد پیگیری</th><th scope="col">سرویس و روش اجرا</th><th scope="col">کاربر</th><th scope="col">مبلغ</th><th scope="col">پرداخت</th><th scope="col">وضعیت</th><th scope="col">مسئول</th><th scope="col">جزئیات</th></tr></thead>
              <tbody>{result.data.map((item) => {
                const name = item.user?.full_name || [item.user?.first_name, item.user?.last_name].filter(Boolean).join(' ') || '—';
                return <tr key={item.id}>
                  <td><strong><Link to={`${match.url}/${item.id}`}><bdi>{item.reference}</bdi></Link></strong><small>شناسهٔ {number.format(item.id)}</small></td>
                  <td><strong>{item.service?.title || '—'}</strong><small>{item.service?.execution_type === 'api' ? 'API آنلاین' : 'اپراتوری'}</small></td>
                  <td>{name}{item.user?.mobile && <small><bdi>{item.user.mobile}</bdi></small>}</td>
                  <td>{Number(item.amount) ? `${number.format(item.amount)} تومان` : 'رایگان'}</td>
                  <td><Status entry={payments[item.payment_status]} fallback={item.payment_status} /></td>
                  <td><Status entry={orderStatus(item)} fallback={item.status} /></td>
                  <td>{assignee(item)}</td>
                  <td><Link className="spad-orders__detail" to={`${match.url}/${item.id}`} aria-label={`مشاهده سفارش ${item.reference}`}>مشاهده <i className="simple-icon-arrow-left" aria-hidden="true" /></Link></td>
                </tr>;
              })}</tbody>
            </table>
          </div>
        </>) : <p role="status">سفارشی با این فیلترها پیدا نشد.</p>}
        {!loading && !error && last > 1 && (
          <nav className="spad-orders__pagination" aria-label="صفحه‌بندی سفارش‌ها">
            <button type="button" className="spad-orders__secondary" disabled={page <= 1} onClick={() => goToPage(page - 1)}>قبلی</button>
            <span>صفحهٔ {number.format(page)} از {number.format(last)}</span>
            <button type="button" className="spad-orders__secondary" disabled={page >= last} onClick={() => goToPage(page + 1)}>بعدی</button>
          </nav>
        )}
      </div>
    </section>
  );
}
