import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { useSelector } from 'react-redux';
import { useHistory, useLocation } from 'react-router-dom';
import TopNav from '../containers/navs/Topnav';
import Sidebar from '../containers/navs/Sidebar';
import Footer from '../containers/navs/Footer';
import AdminMenuSearch from '../containers/navs/AdminMenuSearch';
import AdminPageContext from '../containers/navs/AdminPageContext';
import { adminRoot } from '../constants/defaultValues';
import { activeAdminDestination, adminDestinations, adminSections } from '../containers/navs/adminNavigation';
import '../assets/css/sass/spad-admin-console.scss';
import '../assets/css/sass/spad-admin-workspace.scss';

export default function AppLayout({ children }) {
  const { currentUser, step } = useSelector(({ authUser }) => authUser);
  const history = useHistory();
  const location = useLocation();
  const sections = useMemo(() => adminSections(currentUser), [currentUser]);
  const destinations = useMemo(() => adminDestinations(sections), [sections]);
  const active = activeAdminDestination(destinations, location);
  const showNavigation = step == null;
  const interior = showNavigation && location.pathname !== adminRoot && !location.pathname.startsWith(`${adminRoot}/dashboards`);
  const [mobileOpen, setMobileOpen] = useState(false);
  const [searchOpen, setSearchOpen] = useState(false);
  const closeMenu = useCallback(() => setMobileOpen(false), []);
  const closeSearch = useCallback(() => setSearchOpen(false), []);
  const openSearch = useCallback(() => {
    setMobileOpen(false);
    setSearchOpen(true);
  }, []);

  useEffect(() => {
    closeMenu();
    closeSearch();
    window.scrollTo(0, 0);
  }, [location.pathname, closeMenu, closeSearch]);

  useEffect(() => {
    const media = window.matchMedia('(min-width: 992px)');
    const resize = () => { if (media.matches) closeMenu(); };
    window.addEventListener('resize', resize);
    return () => window.removeEventListener('resize', resize);
  }, [closeMenu]);

  useEffect(() => {
    if (!showNavigation) return undefined;
    const press = (event) => {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        openSearch();
      }
    };
    window.addEventListener('keydown', press);
    return () => window.removeEventListener('keydown', press);
  }, [showNavigation, openSearch]);

  return (
    <div id="app-container" className={showNavigation ? 'spad-admin-shell spad-console' : 'spad-admin-shell spad-console spad-console--standalone'} dir="rtl">
      <a className="spad-console-skip" href="#spad-console-main">رفتن به محتوای اصلی</a>
      <TopNav {...{ history, active, showNavigation, menuOpen: mobileOpen }} onMenu={() => setMobileOpen((open) => !open)} onSearch={openSearch} />
      {showNavigation && <Sidebar sections={sections} destinations={destinations} active={active} open={mobileOpen} onClose={closeMenu} />}
      <main id="spad-console-main" className="spad-console-main" tabIndex={-1}>
        <div className={interior ? 'container-fluid spad-admin-content spad-work-pages' : 'container-fluid spad-admin-content'}>
          {interior && <AdminPageContext active={active} destinations={destinations} pathname={location.pathname} />}
          {children}
        </div>
      </main>
      <Footer />
      {showNavigation && <AdminMenuSearch destinations={destinations} open={searchOpen} onClose={closeSearch} />}
    </div>
  );
}
