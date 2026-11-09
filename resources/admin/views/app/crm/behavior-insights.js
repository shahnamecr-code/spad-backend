import React, { useCallback, useEffect, useState } from 'react';
import axios from 'axios';
import { useSelector } from 'react-redux';
import { Link } from 'react-router-dom';
import { adminPathApi, adminRoot } from '../../../constants/defaultValues';
import '../../../assets/css/sass/spad-admin-crm-behavior.scss';

const API = adminPathApi + '/crm-pro/behavior';
const fmt = value => Number(value || 0).toLocaleString('fa-IR');
const date = value => value ? new Date(String(value).replace(' ', 'T')).toLocaleString('fa-IR') : '—';
const EVENT_NAMES = {
  page_view: 'ورود به صفحه', property_view: 'مشاهده ملک',
  tour_open: 'شروع تور مجازی', tour_scene: 'دیدن فضای تور',
  tour_duration: 'زمان حضور در تور', favorite: 'علاقه‌مندی به ملک'
};
const getError = error => error?.response?.data?.message || 'دریافت اطلاعات ممکن نشد.';

export default function BehaviorInsights() {
  const user = useSelector(state => state.authUser.currentUser);
  const isAdmin = Boolean(user && Array.isArray(user.roles) && user.roles.includes('Super Admin'));
  const [days, setDays] = useState(30);
  const [page, setPage] = useState(1);
  const [summary, setSummary] = useState(null);
  const [list, setList] = useState([]);
  const [selected, setSelected] = useState(() => Number(new URLSearchParams(window.location.search).get("user")) || null);
  const [detail, setDetail] = useState(null);
  const [loading, setLoading] = useState(false);
  const [detailLoading, setDetailLoading] = useState(false);
  const [error, setError] = useState('');

  const reload = useCallback(async () => {
    if (!isAdmin) return;
    setLoading(true);
    try {
      const { data } = await axios.get(API + '/overview', { params: { days, page, per_page: 15 } });
      setSummary(data); setList(data.users || []); setError('');
    } catch (e) { setError(getError(e)); }
    finally { setLoading(false); }
  }, [days, page, isAdmin]);

  useEffect(() => { reload(); }, [reload]);
  useEffect(() => {
    if (!isAdmin || !selected) { setDetail(null); return; }
    let cancelled = false;
    setDetailLoading(true);
    axios.get(API + '/users/' + selected, { params: { days } })
      .then(({ data }) => { if (!cancelled) { setDetail(data); setError(''); } })
      .catch(e => { if (!cancelled) { setDetail(null); setError(getError(e)); } })
      .finally(() => { if (!cancelled) setDetailLoading(false); });
    return () => { cancelled = true; };
  }, [days, selected, isAdmin]);

  if (!isAdmin) return <div className="spad-crm-behavior"><p className="notice">دسترسی به تحلیل رفتار مشتریان فقط برای مدیر اصلی اسپاد مجاز است.</p></div>;

  const stats = summary?.stats || {};
  return <div className="spad-crm-behavior" dir="rtl">
    <header className="hero">
      <div><small>تحلیل رفتار مشتریان اسپاد</small><h1>هوش رفتاری مشتریان</h1>
        <p>صفحات بازدیدشده، املاک بررسی‌شده و تورهای مجازی هر کاربرِ دارای رضایت را بررسی کنید؛ امتیازها نشانه احتمالی علاقه هستند، نه تصمیم قطعی مشتری.</p>
        <p className="privacy">نمایش فقط برای کاربران واردشده‌ای است که رضایت تحلیل رفتار داده‌اند. هیچ محتوای فرم، رمز، نشانی اینترنتی دارای پارامتر یا IP در این بخش ذخیره نمی‌شود. سوابق حداکثر ۹۰ روز نگهداری می‌شوند.</p>
      </div>
      <div className="actions"><Link to={adminRoot + '/crm/pro'}>بازگشت به پرونده‌های CRM</Link>
        <label>بازه بررسی <select value={days} onChange={e => { setDays(Number(e.target.value)); setPage(1); }}><option value={7}>۷ روز</option><option value={30}>۳۰ روز</option><option value={90}>۹۰ روز</option></select></label>
        <button type="button" disabled={loading} onClick={reload}>تازه‌سازی</button>
      </div>
    </header>
    {error && <div className="notice" role="alert">{error}</div>}
    <div className="metrics">
      <article><small>کاربران دارای رضایت فعال</small><strong>{fmt(stats.consented_users)}</strong></article>
      <article><small>کاربران فعال در بازه</small><strong>{fmt(stats.active_users)}</strong></article>
      <article><small>بازدید صفحات ملک</small><strong>{fmt(stats.property_views)}</strong></article>
      <article><small>شروع تور مجازی</small><strong>{fmt(stats.tour_opens)}</strong></article>
    </div>
    <div className="workspace">
      <section className="directory">
        <h2>کاربران و میزان تعامل</h2>
        <div className="heading"><span>کاربر</span><span>ملک‌ها</span><span>تور</span></div>
        {list.map(item => <button type="button" className={'person' + (selected === item.id ? ' selected' : '')} key={item.id} onClick={() => setSelected(item.id)}>
          <span><b>{[item.first_name, item.last_name].filter(Boolean).join(' ') || 'کاربر اسپاد'}</b><small>آخرین فعالیت: {date(item.last_seen)}{item.contact_id ? ' · دارای پرونده CRM' : ''}</small></span>
          <b>{fmt(item.properties)}</b><b>{fmt(item.tours)}</b>
        </button>)}
        {!list.length && <p className="empty">{loading ? 'در حال دریافت اطلاعات…' : 'در این بازه فعالیت دارای رضایت ثبت نشده است.'}</p>}
        <footer><span>{fmt(summary?.total)} کاربر</span><button type="button" disabled={page === 1 || loading} onClick={() => setPage(x => x - 1)}>قبلی</button>
          <span>{fmt(page)} / {fmt(summary?.last_page || 1)}</span>
          <button type="button" disabled={loading || page >= (summary?.last_page || 1)} onClick={() => setPage(x => x + 1)}>بعدی</button></footer>
      </section>
      <section className="details">
        {!selected ? <p className="empty">برای مشاهده علایق احتمالی و مسیر بازدید، یک کاربر را انتخاب کنید.</p> :
          detailLoading ? <p className="empty">در حال بارگذاری پرونده رفتاری…</p> : detail ? <>
            <div className="detail-head"><div><small>پروفایل رفتار</small><h2>{[detail.user.first_name, detail.user.last_name].filter(Boolean).join(' ') || 'کاربر اسپاد'}</h2></div>
              {detail.contact_id && <Link to={adminRoot + '/crm/pro?contact=' + detail.contact_id}>مشاهده CRM</Link>}
            </div>
            <h3>املاک مورد توجه بر اساس رفتار</h3>
            <p className="caption">امتیاز تقریبی: مشاهده ملک ۲، شروع تور ۵، مشاهده هر فضای تور ۱ و افزودن به علاقه‌مندی‌ها ۸ امتیاز.</p>
            <div className="property-list">
              {(detail.properties || []).map(item => <article key={item.property_id}>
                <div><b>ملک کد {fmt(item.property_id)}</b><small>آخرین بازدید: {date(item.last_seen)}</small></div>
                <div className="counts"><span>مشاهده: {fmt(item.property_views)}</span><span>تور: {fmt(item.tour_opens)}</span><span>فضاها: {fmt(item.scenes_viewed)}</span></div>
                <div className="score"><strong>{fmt(item.interest_score)}</strong><small>امتیاز</small></div>
                <a href={'https://spad.estate/property/' + Number(item.property_id)} target="_blank" rel="noopener noreferrer">مشاهده ملک</a>
              </article>)}
              {!detail.properties?.length && <p className="empty">هنوز مشاهده ملکی ثبت نشده است.</p>}
            </div>
            <h3>مسیر حرکت و رویدادها</h3>
            <div className="timeline">{(detail.timeline || []).map((event, index) => <div className="event" key={index}>
              <span className="event-icon">●</span><div><b>{EVENT_NAMES[event.event_type] || 'تعامل کاربر'}</b>
                <small>{event.property_id ? 'ملک ' + fmt(event.property_id) + ' · ' : ''}{event.page_path || ''}
                  {event.duration_seconds ? ' · ' + fmt(event.duration_seconds) + ' ثانیه' : ''}</small></div>
              <time>{date(event.created_at)}</time>
            </div>)}
              {!detail.timeline?.length && <p className="empty">رویدادی در این بازه وجود ندارد.</p>}
            </div>
          </> : <p className="empty">اطلاعات این کاربر در دسترس نیست یا رضایت خود را لغو کرده است.</p>}
      </section>
    </div>
  </div>;
}
