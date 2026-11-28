import menuItems from '../../constants/menu';

const sections = [
  { title: 'نمای کلی', ids: ['dashboards'] },
  { title: 'املاک و مشتریان', ids: ['advertisement', 'property-acceptance', 'crm', 'customers', 'customer-sms', 'partner-panels', 'forms'] },
  { title: 'خدمات و تراکنش‌ها', ids: ['inquiries', 'property-affairs', 'mortgage', 'building-management', 'property-auctions', 'wallets', 'online-renovation', 'top-builders', 'home-services', 'raffles', 'material-prices'] },
  { title: 'محتوا و ارتباطات', ids: ['blogs', 'media_management', 'whatsapp-market', 'ai-assistant'] },
  { title: 'مدیریت سامانه', ids: ['settings', 'operators', 'advertisement-settings', 'locations', 'web-services', 'license'] },
];

export function canSeeAdminItem(item, user) {
  if (!user) return false;
  const roles = user.roles || [];
  const permissions = user.permissions || [];
  return roles.includes('Super Admin')
    || ((!item.roles || item.roles.some((role) => roles.includes(role)))
      && (!item.permission || item.permission.some((permission) => permissions.includes(permission))));
}

export function adminSections(user) {
  const visibleItems = (items) => items.filter((item) => canSeeAdminItem(item, user)).flatMap((item) => {
    if (!item.subs?.length) return item.to ? [item] : [];
    const subs = visibleItems(item.subs);
    return subs.length ? [{ ...item, subs }] : [];
  });
  const items = visibleItems(menuItems);
  const categorized = new Set(sections.flatMap(({ ids }) => ids));
  const menu = sections.map(({ title, ids }) => ({
    title, id: ids[0],
    items: ids.map((id) => items.find((item) => item.id === id)).filter(Boolean),
  }));
  menu.push({ title: 'سایر بخش‌ها', id: 'other', items: items.filter((item) => !categorized.has(item.id)) });
  return menu.filter((section) => section.items.length);
}

export function adminLabel(intl, item) {
  return item.label.startsWith('menu.')
    ? intl.formatMessage({ id: item.label, defaultMessage: item.label })
    : item.label;
}

export function adminDestinations(sections) {
  const destinations = [];
  const visit = (items, parents = []) => items.forEach((item, index) => {
    const key = [...parents.map((parent) => parent.id || parent.to), item.id || item.to || index].join('_');
    if (item.subs?.length) visit(item.subs, [...parents, item]);
    else destinations.push({ item, parents, key });
  });
  sections.forEach(({ items }) => visit(items));
  return destinations;
}

export function adminMenuKey(item) {
  return item.id || item.to || item.label;
}

export function activeAdminDestination(destinations, location) {
  let active = null;
  let best = -1;
  destinations.forEach((destination) => {
    const { to, newWindow, matchPaths = [] } = destination.item;
    if (!to?.startsWith('/') || newWindow) return;
    const [path, query] = to.split('?');
    const exact = location.pathname === path;
    const naturalMatch = exact || location.pathname.startsWith(path + '/');
    const alias = matchPaths.find((prefix) => location.pathname === prefix || location.pathname.startsWith(prefix + '/'));
    if (!naturalMatch && !alias) return;
    if (query && (!exact || !Array.from(new URLSearchParams(query)).every(([key, value]) => (
      new URLSearchParams(location.search).get(key) === value
    )))) return;
    const score = (naturalMatch ? path.length : alias.length) * 4 + (exact ? 2 : 0) + (query ? 1 : 0);
    if (score > best) {
      best = score;
      active = destination;
    }
  });
  return active;
}

export function normalizeAdminSearch(value) {
  return value.toLocaleLowerCase('fa').replace(/ي|ى/g, 'ی').replace(/ك/g, 'ک')
    .replace(/[\u064B-\u065F]/g, '').replace(/[\s\u200c\u200f-]+/g, ' ').trim();
}
