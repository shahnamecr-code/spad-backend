import React, { useEffect, useState } from 'react';
import { DropdownItem, DropdownMenu, DropdownToggle, UncontrolledDropdown } from 'reactstrap';
import { Link } from 'react-router-dom';
import { useIntl } from 'react-intl';
import { connect } from 'react-redux';
import axios from 'axios';
import { logoutUser } from '../../redux/actions';
import { adminRoot } from '../../constants/defaultValues';
import { adminLabel } from './adminNavigation';

const TopNav = ({ history, logoutUserAction, user, active, showNavigation, menuOpen, onMenu, onSearch }) => {
  const intl = useIntl();
  const [panelLogo, setPanelLogo] = useState('');
  const [fullscreen, setFullscreen] = useState(false);

  useEffect(() => {
    let activeRequest = true;
    axios.get('/api/global-settings').then(({ data }) => {
      if (activeRequest) setPanelLogo(data?.panel_logo || '');
    }).catch(() => {});
    const updateFullscreen = () => setFullscreen(Boolean(document.fullscreenElement || document.webkitFullscreenElement));
    document.addEventListener('fullscreenchange', updateFullscreen);
    document.addEventListener('webkitfullscreenchange', updateFullscreen);
    return () => {
      activeRequest = false;
      document.removeEventListener('fullscreenchange', updateFullscreen);
      document.removeEventListener('webkitfullscreenchange', updateFullscreen);
    };
  }, []);

  const toggleFullscreen = async () => {
    try {
      if (document.fullscreenElement || document.webkitFullscreenElement) {
        await (document.exitFullscreen || document.webkitExitFullscreen)?.call(document);
      } else {
        const element = document.documentElement;
        await (element.requestFullscreen || element.webkitRequestFullScreen)?.call(element);
      }
    } catch (_) {
      // The browser may refuse fullscreen while another window is active.
    }
  };
  const displayName = user?.title || user?.name || 'حساب مدیریت';
  const superAdmin = (user?.roles || []).includes('Super Admin');
  const breadcrumb = active && adminLabel(intl, active.item);

  return (
    <header className="spad-console-topbar">
      <div className="spad-console-topbar-start">
        {showNavigation && (
          <button type="button" className="spad-console-icon-button spad-console-menu-trigger" onClick={onMenu}
            aria-label="باز کردن منوی مدیریت" aria-expanded={menuOpen} aria-controls="spad-console-navigation">
            <i className="simple-icon-menu" aria-hidden="true" />
          </button>
        )}
        <Link to={adminRoot} className="spad-console-brand">
          <span className="spad-console-brand-symbol">{panelLogo
            ? <img src={panelLogo} alt="" onError={() => setPanelLogo('')} />
            : <span aria-hidden="true">S</span>}</span>
          <span>مدیریت اسپاد<small dir="ltr">SPAD</small></span>
        </Link>
        {breadcrumb && <span className="spad-console-location" aria-label={`بخش جاری: ${breadcrumb}`}>{breadcrumb}</span>}
      </div>
      <div className="spad-console-topbar-actions">
        {showNavigation && (
          <button type="button" className="spad-console-search-trigger" onClick={onSearch}
            aria-label="جست‌وجوی منوی مدیریت" aria-keyshortcuts="Control+k Meta+k">
            <i className="simple-icon-magnifier" aria-hidden="true" />
            <span>جست‌وجوی منو</span><kbd dir="ltr">Ctrl K</kbd>
          </button>
        )}
        <div className="spad-console-topbar-shortcuts">
          <Link to={`${adminRoot}/advertisements/add`} className="spad-console-primary">
            <i className="simple-icon-plus" aria-hidden="true" /> ثبت آگهی
          </Link>
          <Link to={`${adminRoot}/crm/add-customer`} className="spad-console-icon-button" title="افزودن درخواست" aria-label="افزودن درخواست">
            <i className="simple-icon-user-follow" aria-hidden="true" />
          </Link>
          {superAdmin && <Link to={`${adminRoot}/settings/main`} className="spad-console-icon-button" title="تنظیمات" aria-label="تنظیمات">
            <i className="simple-icon-settings" aria-hidden="true" />
          </Link>}
          <button type="button" className="spad-console-icon-button" onClick={toggleFullscreen} title="تمام‌صفحه"
            aria-label={fullscreen ? 'خروج از تمام‌صفحه' : 'تمام‌صفحه'}>
            <i className={fullscreen ? 'simple-icon-size-actual' : 'simple-icon-size-fullscreen'} aria-hidden="true" />
          </button>
        </div>
        <UncontrolledDropdown className="spad-console-account">
          <DropdownToggle color="empty" className="spad-console-account-trigger" aria-label="منوی حساب مدیریت">
            <span className="spad-console-avatar"><i className="simple-icon-user" aria-hidden="true" /></span>
            <span className="spad-console-user-name">{displayName}</span>
            <i className="simple-icon-arrow-down" aria-hidden="true" />
          </DropdownToggle>
          <DropdownMenu right className="spad-console-account-menu">
            <DropdownItem header>{displayName}</DropdownItem>
            <DropdownItem className="spad-console-mobile-action" tag={Link} to={`${adminRoot}/advertisements/add`}>ثبت آگهی</DropdownItem>
            <DropdownItem className="spad-console-mobile-action" tag={Link} to={`${adminRoot}/crm/add-customer`}>افزودن درخواست</DropdownItem>
            <DropdownItem className="spad-console-mobile-action" onClick={toggleFullscreen}>{fullscreen ? 'خروج از تمام‌صفحه' : 'تمام‌صفحه'}</DropdownItem>
            <DropdownItem tag={Link} to={`${adminRoot}/settings/main`}>
              <i className="simple-icon-settings" aria-hidden="true" /> تنظیمات
            </DropdownItem>
            <DropdownItem divider />
            <DropdownItem onClick={() => logoutUserAction(history)}>
              <i className="simple-icon-logout" aria-hidden="true" /> خروج از حساب
            </DropdownItem>
          </DropdownMenu>
        </UncontrolledDropdown>
      </div>
    </header>
  );
};

export default connect(({ authUser }) => ({ user: authUser.currentUser }), { logoutUserAction: logoutUser })(TopNav);
