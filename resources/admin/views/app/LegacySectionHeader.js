import { adminRoot } from '../../constants/defaultValues';

const legacyPages = new Set([
  'settings', 'customers', 'blogs', 'advertisements', 'media_management', 'crm', 'operators', 'raffles', 'wallets', 'inquiries', 'virtual-tours', 'contacts', 'employ-requests', 'expert-requests', 'location', 'license', 'transactions', 'land-types', 'partner-panels', 'investor-listings', 'media-management'
]);

export function isLegacyAdminRoute(pathname) {
  const root = adminRoot + '/';
  if (!pathname?.startsWith(root)) return false;
  const [section, page] = pathname.slice(root.length).split('/');
  return legacyPages.has(section) && !(section === 'settings' && page === 'user-statistics');
}
