import React, {useCallback, useEffect, useState} from 'react';
import {Link} from 'react-router-dom';
import {useSelector} from 'react-redux';
import axios from 'axios';
import {adminPathApi, adminRoot} from '../../../constants/defaultValues';

const number = (value) => Number(value || 0).toLocaleString('fa-IR');
const safeArray = (value) => Array.isArray(value) ? value : [];
const percentage = (value, total) => total > 0 ? Math.min(100, Math.max(0, Number(value || 0) * 100 / total)) : 0;

const DashboardStat = ({label, value, icon, to}) => (
  <Link className="spad-v7-stat" to={to}>
    <span className="spad-v7-stat__icon"><i className={icon}/></span>
    <span className="spad-v7-stat__copy"><small>{label}</small><strong>{number(value)}</strong></span>
    <i className="simple-icon-arrow-left spad-v7-stat__arrow"/>
  </Link>
);

const DashboardPanel = ({title, subtitle, action, children}) => (
  <section className="spad-v7-panel">
    <div className="spad-v7-panel__heading">
      <div><h2>{title}</h2>{subtitle && <p>{subtitle}</p>}</div>
      {action}
    </div>
    {children}
  </section>
);

const DashboardDefault = () => {
  const [dashboard, setDashboard] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);
  const user = useSelector((state) => state.authUser.currentUser);
  const isSuperAdmin = safeArray(user && user.roles).includes('Super Admin');

  const loadDashboard = useCallback(() => {
    setLoading(true);
    setError(false);
    return axios.get(`${adminPathApi}/dashboard`)
      .then(({data}) => setDashboard(data || {}))
      .catch(() => setError(true))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    let active = true;
    setLoading(true);
    axios.get(`${adminPathApi}/dashboard`)
      .then(({data}) => {if (active) setDashboard(data || {});})
      .catch(() => {if (active) setError(true);})
      .finally(() => {if (active) setLoading(false);});
    return () => {active = false;};
  }, []);

  const states = safeArray(dashboard && dashboard.ads_by_state && dashboard.ads_by_state.value);
  const waitingCount = states[2] || 0;
  const dates = safeArray(dashboard && dashboard.ads_by_time && dashboard.ads_by_time.label);
  const volumes = safeArray(dashboard && dashboard.ads_by_time && dashboard.ads_by_time.value);
  const chart = dates.map((label, index) => ({label, count: Number(volumes[index] || 0)}));
  const maxCount = Math.max(1, ...chart.map((item) => item.count));
  const categories = safeArray(dashboard && dashboard.ads_by_category && dashboard.ads_by_category.label)
    .map((label, index) => ({
      label,
      count: Number(safeArray(dashboard.ads_by_category.value)[index] || 0),
    })).sort((a, b) => b.count - a.count).slice(0, 5);
  const maxCategory = Math.max(1, ...categories.map((item) => item.count));
  const recent = safeArray(dashboard && dashboard.last_ads);
  const shortcuts = [
    ['آگهی‌های ثبت‌شده', 'simple-icon-list', `${adminRoot}/advertisements/adver-list`],
    ['درخواست‌های مشتریان', 'simple-icon-people', `${adminRoot}/crm/all-requests`],
    ['تورهای مجازی', 'simple-icon-picture', `${adminRoot}/virtual-tours/list`],
    ['استعلامات', 'simple-icon-magnifier', `${adminRoot}/inquiries/requests`],
    ...(isSuperAdmin ? [
      ['مدیریت ساختمان', 'iconsminds-building', `${adminRoot}/building-management`],
      ['سازندگان برتر', 'iconsminds-business-man', `${adminRoot}/top-builders`],
      ['مزایده املاک', 'iconsminds-gavel', `${adminRoot}/property-auctions`],
      ['وام مسکن', 'iconsminds-bank', `${adminRoot}/mortgage`],
      ['تنظیمات پنل', 'simple-icon-settings', `${adminRoot}/settings/main`],
    ] : []),
  ];

  return (
    <div className="spad-v7-dashboard">
      <header className="spad-v7-hero">
        <div className="spad-v7-hero__text">
          <span className="spad-v7-eyebrow">SPAD ESTATE / ADMIN CONSOLE</span>
          <h1>داشبورد مدیریت اسپاد</h1>
          <p>آمار، فعالیت‌های ملکی و درخواست‌های مجموعه در یک نگاه</p>
        </div>
        <div className="spad-v7-hero__actions">
          <Link to={`${adminRoot}/advertisements/add`}><i className="simple-icon-plus"/> ثبت ملک جدید</Link>
          <Link to={`${adminRoot}/crm/all-requests`}><i className="simple-icon-people"/> مدیریت درخواست‌ها</Link>
        </div>
      </header>

      {error && <div className="spad-v7-alert" role="alert">
        دریافت آمار داشبورد انجام نشد. اطلاعات قبلی، در صورت وجود، نمایش داده می‌شود.
        <button type="button" className="btn btn-link" onClick={loadDashboard}>تلاش مجدد</button>
      </div>}

      <div className="spad-v7-stats" aria-busy={loading}>
        <DashboardStat label="کل آگهی‌های ملکی" value={dashboard && dashboard.all_ads_count} icon="iconsminds-building" to={`${adminRoot}/advertisements/adver-list`}/>
        <DashboardStat label="درخواست‌های CRM" value={dashboard && dashboard.crm_requests_count} icon="simple-icon-people" to={`${adminRoot}/crm/all-requests`}/>
        <DashboardStat label="درخواست‌های کارشناسی" value={dashboard && dashboard.expert_requests_count} icon="simple-icon-user-following" to={`${adminRoot}/expert-requests/list`}/>
        <DashboardStat label="آگهی در انتظار تأیید" value={waitingCount} icon="simple-icon-clock" to={`${adminRoot}/advertisements/unapproved`}/>
      </div>

      <div className="spad-v7-grid">
        <DashboardPanel title="روند ثبت آگهی‌ها" subtitle="اطلاعات ثبت‌شده در بازه زمانی ارائه‌شده توسط سرور"
          action={<button type="button" onClick={loadDashboard} disabled={loading}><i className="simple-icon-refresh"/> {loading ? 'در حال دریافت' : 'به‌روزرسانی'}</button>}>
          {chart.length > 0 ? (
            <div className="spad-v7-bars" role="img" aria-label="نمودار تعداد آگهی‌های ثبت شده">
              {chart.map((item, index) => (
                <div className="spad-v7-bar-item" key={`${index}-${item.label}`} title={`${item.label}: ${number(item.count)} آگهی`}>
                  <span className="spad-v7-bar-count">{number(item.count)}</span>
                  <div className="spad-v7-bar-track"><span className="spad-v7-bar-fill" style={{height: `${Math.max(2, percentage(item.count, maxCount))}%`}}/></div>
                  <span className="spad-v7-bar-label">{item.label}</span>
                </div>
              ))}
            </div>
          ) : <div className={`spad-v7-empty ${loading ? 'spad-v7-skeleton' : ''}`}>{loading ? 'در حال دریافت نمودار…' : 'داده‌ای برای نمایش وجود ندارد'}</div>}
        </DashboardPanel>

        <DashboardPanel title="وضعیت انتشار آگهی‌ها" subtitle="توزیع آگهی‌ها بر اساس وضعیت بررسی">
          <div className="spad-v7-breakdown">
            {['تأیید شده', 'رد شده', 'در انتظار بررسی'].map((name, i) => (
              <div className="spad-v7-breakdown__item" key={name}>
                <div className="spad-v7-breakdown__row"><span>{name}</span><b>{number(states[i])}</b></div>
                <div className="spad-v7-progress" role="progressbar" aria-label={name} aria-valuenow={Number(states[i] || 0)} aria-valuemin={0} aria-valuemax={Number(dashboard && dashboard.all_ads_count || 0) || 1}>
                  <span style={{width: `${percentage(states[i], Number(dashboard && dashboard.all_ads_count || 0))}%`}}/>
                </div>
              </div>
            ))}
          </div>
        </DashboardPanel>
      </div>

      <div className="spad-v7-grid">
        <DashboardPanel title="تازه‌ترین املاک ثبت‌شده" subtitle="آخرین آگهی‌های موجود در سامانه"
          action={<Link to={`${adminRoot}/advertisements/adver-list`}>مشاهده همه</Link>}>
          {recent.length ? (
            <div className="spad-v7-recent">
              {recent.slice(0, 6).map((item, i) => (
                <Link to={`${adminRoot}/advertisements/edit/${item.id}`} key={item.id || i} className="spad-v7-recent__item">
                  {item.image ? <img src={item.image} alt="" className="spad-v7-recent__photo" loading="lazy"/> : <span className="spad-v7-recent__photo spad-v7-recent__placeholder"><i className="iconsminds-building"/></span>}
                  <span className="spad-v7-recent__content"><strong>{item.title || 'ملک بدون عنوان'}</strong><small>{item.date || '—'}</small></span>
                  <span className="spad-v7-recent__status">{item.status_text || 'ثبت‌شده'}</span>
                </Link>
              ))}
            </div>
          ) : <div className="spad-v7-empty">{loading ? 'در حال دریافت املاک…' : 'آگهی جدیدی وجود ندارد'}</div>}
        </DashboardPanel>
        <DashboardPanel title="دسته‌بندی املاک" subtitle="پراکندگی تعداد آگهی‌ها در دسته‌های اصلی">
          {categories.length ? (
            <div className="spad-v7-breakdown">
              {categories.map((item, i) => (
                <div className="spad-v7-breakdown__item" key={i}>
                  <div className="spad-v7-breakdown__row"><span>{item.label}</span><b>{number(item.count)}</b></div>
                  <div className="spad-v7-progress"><span style={{width: `${percentage(item.count, maxCategory)}%`}}/></div>
                </div>
              ))}
            </div>
          ) : <div className="spad-v7-empty">{loading ? 'در حال دریافت دسته‌بندی‌ها…' : 'اطلاعاتی برای نمایش وجود ندارد'}</div>}
        </DashboardPanel>
      </div>

      <DashboardPanel title="دسترسی سریع به بخش‌های مدیریت" subtitle="میانبرهای عملیاتی پرکاربرد">
        <div className="spad-v7-shortcuts">
          {shortcuts.map(([label, icon, to]) => (
            <Link to={to} className="spad-v7-shortcut" key={to}><i className={icon}/>{label}</Link>
          ))}
        </div>
      </DashboardPanel>
    </div>
  );
};
export default DashboardDefault;
