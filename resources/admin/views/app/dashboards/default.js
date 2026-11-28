import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { useSelector } from 'react-redux';
import { Link } from 'react-router-dom';
import { useIntl } from 'react-intl';
import axios from 'axios';
import { adminPathApi, adminRoot } from '../../../constants/defaultValues';
import { adminDestinations, adminLabel, adminSections, normalizeAdminSearch } from '../../../containers/navs/adminNavigation';
import ServerHealthPanel from './ServerHealthPanel';
import UserActivityOverview from './UserActivityOverview';

const fullDate = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
  timeZone: 'Asia/Tehran', dateStyle: 'full',
});
const number = new Intl.NumberFormat('fa-IR');
const format = (value) => value == null ? '—' : number.format(value);

const workflow = [
  { route: '/inquiries/requests', icon: 'simple-icon-docs', caption: 'رهگیری، پرداخت و وضعیت پردازش' },
  { route: '/advertisements/unapproved', icon: 'simple-icon-clock', title: 'آگهی‌های در انتظار بررسی', read: (data) => data?.ads_by_state?.value?.[2] },
  { route: '/crm/all-requests', icon: 'simple-icon-people', title: 'درخواست‌های مشتریان', read: (data) => data?.crm_requests_count },
  { route: '/advertisements/adver-list', icon: 'simple-icon-list', title: 'فهرست آگهی‌ها', read: (data) => data?.all_ads_count },
];

function Directory({ sections }) {
  const intl = useIntl();
  const [query, setQuery] = useState('');
  const terms = normalizeAdminSearch(query).split(' ').filter(Boolean);
  const groups = sections.filter(({ id }) => id !== 'dashboards').map((section) => ({
    ...section,
    links: adminDestinations([section]).filter(({ item, parents }) => {
      const text = normalizeAdminSearch([
        section.title, adminLabel(intl, item), item.keywords, ...parents.map((parent) => adminLabel(intl, parent)),
      ].join(' '));
      return terms.every((term) => text.includes(term));
    }),
  })).filter(({ links }) => links.length);

  return (
    <section className="spad-work-directory" aria-labelledby="spad-work-directory-title">
      <header className="spad-work-directory__head">
        <div><h2 id="spad-work-directory-title">همهٔ بخش‌های پنل</h2><p>گزینه‌های منو، بر اساس حوزهٔ کاری و سطح دسترسی شما</p></div>
        <label className="spad-work-directory__search">
          <i className="simple-icon-magnifier" aria-hidden="true" />
          <input type="search" value={query} onChange={(event) => setQuery(event.target.value)} aria-label="جست‌وجو در همه بخش‌های پنل" placeholder="نام صفحه یا خدمت" />
        </label>
      </header>
      {groups.length === 0 && <p role="status">صفحه‌ای با این عبارت پیدا نشد.</p>}
      <div className="spad-work-directory__grid">
        {groups.map(({ id, title, links }) => (
          <details open={Boolean(terms.length) || undefined} key={id} className="spad-work-directory__group">
            <summary>
              <i className={links[0].parents[0]?.icon || links[0].item.icon || 'simple-icon-grid'} aria-hidden="true" />
              <span>{title}</span>
              <small>{format(links.length)} گزینه</small>
              <i className="simple-icon-arrow-down" aria-hidden="true" />
            </summary>
            <ul>
              {links.map(({ item, key }) => {
                const content = <><i className={item.icon} aria-hidden="true" /><span>{adminLabel(intl, item)}</span></>;
                return <li key={key}>{item.newWindow
                  ? <a href={item.to} target="_blank" rel="noopener noreferrer">{content}</a>
                  : <Link to={item.to}>{content}</Link>}</li>;
              })}
            </ul>
          </details>
        ))}
      </div>
    </section>
  );
}

function useDashboardData(superAdmin) {
  const [data, setData] = useState(null);
  const [health, setHealth] = useState(null);
  const [resources, setResources] = useState(null);
  const [period, setPeriod] = useState('24h');
  const [loading, setLoading] = useState(true);
  const [resourcesLoading, setResourcesLoading] = useState(superAdmin);
  const [healthError, setHealthError] = useState('');
  const [resourceError, setResourceError] = useState('');
  const loadHealth = useCallback(async () => {
    setLoading(true);
    try {
      const response = await axios.get(adminPathApi + '/dashboard/server-health', { params: { period }, timeout: 15000 });
      setHealth(response.data?.data || null);
      setHealthError('');
    } catch (_) { setHealthError('دریافت وضعیت سلامت سامانه ممکن نشد.'); }
    finally { setLoading(false); }
  }, [period]);
  const loadResources = useCallback(async () => {
    if (!superAdmin) return;
    setResourcesLoading(true);
    try {
      const response = await axios.get(adminPathApi + '/dashboard/server-resources', { timeout: 15000 });
      setResources(response.data?.data || null);
      setResourceError('');
    } catch (_) { setResourceError('دریافت اطلاعات منابع میزبان ممکن نشد.'); }
    finally { setResourcesLoading(false); }
  }, [superAdmin]);

  useEffect(() => {
    let active = true;
    axios.get(adminPathApi + '/dashboard', { timeout: 15000 }).then(({ data: payload }) => {
      if (active) setData(payload);
    }).catch(() => {});
    return () => { active = false; };
  }, []);
  useEffect(() => {
    loadHealth();
    const timer = window.setInterval(loadHealth, 300000);
    return () => window.clearInterval(timer);
  }, [loadHealth]);
  useEffect(() => {
    if (!superAdmin) return undefined;
    loadResources();
    const timer = window.setInterval(loadResources, 120000);
    return () => window.clearInterval(timer);
  }, [superAdmin, loadResources]);

  return { data, health, resources, period, setPeriod, loading, resourcesLoading, healthError, resourceError,
    refresh: () => { loadHealth(); loadResources(); } };
}

export default function DashboardDefault() {
  const intl = useIntl();
  const user = useSelector((state) => state.authUser.currentUser);
  const sections = useMemo(() => adminSections(user), [user]);
  const destinations = useMemo(() => adminDestinations(sections), [sections]);
  const superAdmin = Boolean(user?.roles?.includes('Super Admin'));
  const { data, health, resources, period, setPeriod, loading, resourcesLoading, healthError, resourceError, refresh } = useDashboardData(superAdmin);

  return (
    <div className="spad-work-dashboard" dir="rtl">
      <header className="spad-work-dashboard__head">
        <div><h1>داشبورد مدیریت اسپاد</h1><p>کارهای جاری، فعالیت کاربران و وضعیت سامانه</p></div>
        <div className="spad-work-dashboard__meta">
          <span><i className="simple-icon-calendar" aria-hidden="true" />{fullDate.format(new Date())}</span>
          <a href="#spad-work-directory-title">همهٔ بخش‌های پنل <i className="simple-icon-arrow-down" aria-hidden="true" /></a>
        </div>
      </header>

      <section className="spad-work-queue" aria-labelledby="spad-work-queue-title">
        <h2 id="spad-work-queue-title">دسترسی به کارهای جاری</h2>
        <div className="spad-work-queue__grid">
          {workflow.flatMap(({ route, icon, title, caption, read }) => {
            const dest = destinations.find(({ item }) => item.to === adminRoot + route);
            if (!dest) return [];
            return [<Link key={route} to={dest.item.to}>
              <i className={icon} aria-hidden="true" />
              <span><strong>{title || adminLabel(intl, dest.item)}</strong>{caption && <small>{caption}</small>}</span>
              {read ? <b>{format(read(data))}</b> : <i className="simple-icon-arrow-left" aria-hidden="true" />}
            </Link>];
          })}
        </div>
      </section>

      {superAdmin && <UserActivityOverview />}

      <div className="spad-work-server">
        <ServerHealthPanel info={health} resources={resources} resourceError={resourceError}
          loadingResources={resourcesLoading} loading={loading} error={healthError}
          onRefresh={refresh} period={period} onPeriod={setPeriod} />
      </div>
      <Directory sections={sections} />
    </div>
  );
}
