/* eslint-disable jsx-a11y/click-events-have-key-events */
/* eslint-disable jsx-a11y/no-static-element-interactions */
import React, {useEffect, useState} from 'react';
import {DropdownItem, DropdownMenu, DropdownToggle, UncontrolledDropdown} from 'reactstrap';
import {Link} from 'react-router-dom';
import {connect} from 'react-redux';
import axios from 'axios';

import {logoutUser} from '../../redux/actions';
import {adminRoot} from '../../constants/defaultValues';
import UserCan from '../../helpers/UserCan';

const TopNav = ({history, logoutUserAction, user}) => {
  const [globalSettings, setGlobalSettings] = useState({});
  const [isInFullScreen, setIsInFullScreen] = useState(false);

  useEffect(() => {
    axios
      .get('/api/global-settings')
      .then((res) => setGlobalSettings(res.data || {}))
      .catch(() => {});
  }, []);

  const toggleMenu = (e) => {
    e.preventDefault();
    window.dispatchEvent(new CustomEvent('spad-admin-menu-toggle'));
  };

  const toggleFullScreen = () => {
    const doc = document;
    const element = document.documentElement;
    const active = !!(
      doc.fullscreenElement ||
      doc.webkitFullscreenElement ||
      doc.mozFullScreenElement ||
      doc.msFullscreenElement
    );

    if (!active) {
      const request =
        element.requestFullscreen ||
        element.webkitRequestFullScreen ||
        element.mozRequestFullScreen ||
        element.msRequestFullscreen;
      if (request) request.call(element);
      setIsInFullScreen(true);
    } else {
      const exit =
        doc.exitFullscreen ||
        doc.webkitExitFullscreen ||
        doc.mozCancelFullScreen ||
        doc.msExitFullscreen;
      if (exit) exit.call(doc);
      setIsInFullScreen(false);
    }
  };

  const handleLogout = () => logoutUserAction(history);
  const displayName = user?.title || user?.name || 'مدیر سیستم';
  const panelLogo = globalSettings?.panel_logo || '';

  return (
    <nav className="spad-admin-topbar navbar fixed-top">
      <div className="spad-admin-topbar__start">
        <button
          type="button"
          className="spad-admin-menu-trigger"
          onClick={toggleMenu}
          aria-label="باز کردن منوی مدیریت"
        >
          <i className="simple-icon-menu"/>
        </button>

        <Link to={adminRoot} className="spad-admin-brand">
          {panelLogo ? (
            <img src={panelLogo} alt="SPAD"/>
          ) : (
            <span className="spad-admin-brand__mark">S</span>
          )}
          <span className="spad-admin-brand__copy">
            <strong>مدیریت SPAD</strong>
            <small>مرکز کنترل املاک</small>
          </span>
        </Link>
      </div>

      <div className="spad-admin-topbar__actions">
        <div className="spad-admin-quick-actions">
          <Link to={`${adminRoot}/advertisements/add`} className="spad-admin-quick-btn spad-admin-quick-btn--primary">
            <i className="simple-icon-plus"/>
            <span>ثبت آگهی</span>
          </Link>
          <Link to={`${adminRoot}/crm/add-customer`} className="spad-admin-quick-btn">
            <i className="simple-icon-user-follow"/>
            <span>افزودن درخواست</span>
          </Link>
        </div>

        <div className="spad-admin-icon-actions">
          <UserCan>
            <Link to={`${adminRoot}/settings/main`} className="spad-admin-icon-btn" title="تنظیمات">
              <i className="simple-icon-settings"/>
            </Link>
          </UserCan>
          <button type="button" className="spad-admin-icon-btn" onClick={toggleFullScreen} title="تمام صفحه">
            <i className={isInFullScreen ? 'simple-icon-size-actual' : 'simple-icon-size-fullscreen'}/>
          </button>
        </div>

        <UncontrolledDropdown className="spad-admin-user-menu">
          <DropdownToggle color="empty" className="spad-admin-user-toggle">
            <span className="spad-admin-user-avatar"><i className="iconsminds-user"/></span>
            <span className="spad-admin-user-copy">
              <strong>{displayName}</strong>
              <small>اپراتور پنل</small>
            </span>
            <i className="simple-icon-arrow-down spad-admin-user-arrow"/>
          </DropdownToggle>
          <DropdownMenu right className="spad-admin-user-dropdown">
            <DropdownItem header>حساب مدیریت</DropdownItem>
            <DropdownItem tag={Link} to={`${adminRoot}/settings/main`}>
              <i className="simple-icon-settings"/> تنظیمات
            </DropdownItem>
            <DropdownItem divider/>
            <DropdownItem onClick={handleLogout}>
              <i className="simple-icon-logout"/> خروج از حساب
            </DropdownItem>
          </DropdownMenu>
        </UncontrolledDropdown>
      </div>
    </nav>
  );
};

const mapStateToProps = ({authUser}) => ({user: authUser.currentUser});

export default connect(mapStateToProps, {logoutUserAction: logoutUser})(TopNav);
