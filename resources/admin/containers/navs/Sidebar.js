/* eslint-disable react/no-array-index-key */
import React, {Component} from 'react';
import {connect} from 'react-redux';
import {NavLink, withRouter} from 'react-router-dom';
import PerfectScrollbar from 'react-perfect-scrollbar';
import classnames from 'classnames';
import {injectIntl} from 'react-intl';

import IntlMessages from '../../helpers/IntlMessages';
import menuItems from '../../constants/menu';

class Sidebar extends Component {
  constructor(props) {
    super(props);
    this.state = {
      openGroups: this.getInitialOpenGroups(props.location.pathname),
      mobileOpen: false,
      searchQuery: '',
    };
  }

  componentDidMount() {
    window.addEventListener('spad-admin-menu-toggle', this.toggleMobileMenu);
    window.addEventListener('resize', this.handleResize);
    this.handleResize();
  }

  componentDidUpdate(prevProps) {
    if (prevProps.location.pathname !== this.props.location.pathname) {
      const activeGroups = this.getInitialOpenGroups(this.props.location.pathname);
      this.setState((state) => ({
        openGroups: Array.from(new Set([...state.openGroups, ...activeGroups])),
        mobileOpen: false,
      }));
      document.body.classList.remove('spad-admin-menu-open');
      window.scrollTo(0, 0);
    }
  }

  componentWillUnmount() {
    window.removeEventListener('spad-admin-menu-toggle', this.toggleMobileMenu);
    window.removeEventListener('resize', this.handleResize);
    document.body.classList.remove('spad-admin-menu-open');
  }

  handleResize = () => {
    if (window.innerWidth >= 992 && this.state.mobileOpen) {
      this.setState({mobileOpen: false});
      document.body.classList.remove('spad-admin-menu-open');
    }
  };

  toggleMobileMenu = () => {
    this.setState(
      (state) => ({mobileOpen: !state.mobileOpen}),
      () => {
        document.body.classList.toggle('spad-admin-menu-open', this.state.mobileOpen);
      }
    );
  };

  closeMobileMenu = () => {
    this.setState({mobileOpen: false});
    document.body.classList.remove('spad-admin-menu-open');
  };

  filteredList = (items = []) => {
    const {currentUser} = this.props;
    if (!currentUser) return [];

    const permissions = currentUser.permissions || [];
    const roles = currentUser.roles || [];
    if (roles.includes('Super Admin')) return items;

    return items.filter((item) => {
      const hasPermission =
        !item.permission || item.permission.some((permission) => permissions.includes(permission));
      const hasRole = !item.roles || item.roles.some((role) => roles.includes(role));
      return hasPermission && hasRole;
    });
  };

  translatedLabel = (item) => {
    const {intl} = this.props;
    if (!item || !item.label) return '';
    return intl.formatMessage({id: item.label, defaultMessage: item.label});
  };

  searchItems = (items, query) => this.filteredList(items).reduce((found, item) => {
    const matches = this.translatedLabel(item).toLocaleLowerCase().includes(query);
    const subs = item.subs ? this.searchItems(item.subs, query) : [];
    if (matches || subs.length) {
      found.push({...item, ...(item.subs ? {subs: matches ? this.filteredList(item.subs) : subs} : {})});
    }
    return found;
  }, []);

  routeBelongsTo = (item, pathname) => {
    if (!item) return false;
    if (item.to && (pathname === item.to || pathname.startsWith(`${item.to}/`))) return true;
    return !!(item.subs && item.subs.some((sub) => this.routeBelongsTo(sub, pathname)));
  };

  getInitialOpenGroups = (pathname) => {
    const groups = [];
    menuItems.forEach((item) => {
      if (item.subs && this.routeBelongsTo(item, pathname)) groups.push(item.id);
      (item.subs || []).forEach((sub, index) => {
        if (sub.subs && this.routeBelongsTo(sub, pathname)) groups.push(`${item.id}:${index}`);
      });
    });
    return groups;
  };

  toggleGroup = (key) => {
    this.setState((state) => ({
      openGroups: state.openGroups.includes(key)
        ? state.openGroups.filter((item) => item !== key)
        : [...state.openGroups, key],
    }));
  };

  renderNestedItems = (items, parentKey, depth = 1) =>
    this.filteredList(items).map((item, index) => {
      const key = `${parentKey}:${index}`;
      const hasChildren = !!(item.subs && item.subs.length);
      const open = !!this.state.searchQuery.trim() || this.state.openGroups.includes(key) || this.routeBelongsTo(item, this.props.location.pathname);

      if (hasChildren) {
        return (
          <div className="spad-admin-nav-nested" key={key}>
            <button
              type="button"
              className={classnames('spad-admin-nav-link', 'spad-admin-nav-link--nested', {open})}
              onClick={() => this.toggleGroup(key)}
            >
              <span className="spad-admin-nav-link__icon"><i className={item.icon || 'simple-icon-folder'}/></span>
              <span className="spad-admin-nav-link__label"><IntlMessages id={item.label}/></span>
              <i className="simple-icon-arrow-down spad-admin-nav-link__chevron"/>
            </button>
            {open ? <div className="spad-admin-nav-children" data-depth={depth + 1}>{this.renderNestedItems(item.subs, key, depth + 1)}</div> : null}
          </div>
        );
      }

      if (item.newWindow) {
        return (
          <a className="spad-admin-nav-link spad-admin-nav-link--child" href={item.to} target="_blank" rel="noopener noreferrer" key={key}>
            <span className="spad-admin-nav-link__icon"><i className={item.icon || 'simple-icon-link'}/></span>
            <span className="spad-admin-nav-link__label"><IntlMessages id={item.label}/></span>
          </a>
        );
      }

      return (
        <NavLink
          key={key}
          to={item.to}
          className="spad-admin-nav-link spad-admin-nav-link--child"
          activeClassName="active"
          onClick={this.closeMobileMenu}
        >
          <span className="spad-admin-nav-link__icon"><i className={item.icon || 'simple-icon-arrow-left'}/></span>
          <span className="spad-admin-nav-link__label"><IntlMessages id={item.label}/></span>
        </NavLink>
      );
    });

  render() {
    const {mobileOpen, openGroups, searchQuery} = this.state;
    const query = searchQuery.trim().toLocaleLowerCase();
    const visibleItems = query ? this.searchItems(menuItems, query) : this.filteredList(menuItems);

    return (
      <>
        <button
          type="button"
          className={classnames('spad-admin-sidebar-backdrop', {show: mobileOpen})}
          onClick={this.closeMobileMenu}
          aria-label="بستن منو"
        />
        <aside className={classnames('spad-admin-sidebar', {open: mobileOpen})}>
          <div className="spad-admin-sidebar__head">
            <div className="spad-admin-sidebar__icon"><i className="iconsminds-control"/></div>
            <div>
              <strong>مرکز مدیریت</strong>
              <small>دسترسی سریع به بخش‌ها</small>
            </div>
            <button type="button" className="spad-admin-sidebar__close" onClick={this.closeMobileMenu}>
              <i className="simple-icon-close"/>
            </button>
          </div>

          <div className="spad-admin-sidebar__search">
            <i className="simple-icon-magnifier" aria-hidden="true"/>
            <input
              type="search"
              value={searchQuery}
              onChange={(event) => this.setState({searchQuery: event.target.value})}
              placeholder="جستجو در منوی مدیریت..."
              aria-label="جستجو در منوی مدیریت"
            />
          </div>
          <div className="spad-admin-sidebar__scroll">
            <PerfectScrollbar options={{suppressScrollX: true, wheelPropagation: false}}>
              <nav className="spad-admin-nav">
                {visibleItems.length === 0 && <div className="spad-admin-nav-empty">بخشی با این عنوان پیدا نشد</div>}
                {visibleItems.map((item) => {
                  const hasChildren = !!(item.subs && item.subs.length);
                  const open = !!query || openGroups.includes(item.id) || this.routeBelongsTo(item, this.props.location.pathname);

                  if (!hasChildren) {
                    return item.newWindow ? (
                      <a className="spad-admin-nav-link spad-admin-nav-link--root" href={item.to} target="_blank" rel="noopener noreferrer" key={item.id}>
                        <span className="spad-admin-nav-link__icon"><i className={item.icon}/></span>
                        <span className="spad-admin-nav-link__label"><IntlMessages id={item.label}/></span>
                      </a>
                    ) : (
                      <NavLink
                        key={item.id}
                        to={item.to}
                        className="spad-admin-nav-link spad-admin-nav-link--root"
                        activeClassName="active"
                        onClick={this.closeMobileMenu}
                      >
                        <span className="spad-admin-nav-link__icon"><i className={item.icon}/></span>
                        <span className="spad-admin-nav-link__label"><IntlMessages id={item.label}/></span>
                      </NavLink>
                    );
                  }

                  return (
                    <section className={classnames('spad-admin-nav-group', {open})} key={item.id}>
                      <button type="button" className="spad-admin-nav-link spad-admin-nav-link--root" onClick={() => this.toggleGroup(item.id)}>
                        <span className="spad-admin-nav-link__icon"><i className={item.icon}/></span>
                        <span className="spad-admin-nav-link__label"><IntlMessages id={item.label}/></span>
                        <i className="simple-icon-arrow-down spad-admin-nav-link__chevron"/>
                      </button>
                      {open ? <div className="spad-admin-nav-children">{this.renderNestedItems(item.subs, item.id)}</div> : null}
                    </section>
                  );
                })}
              </nav>
            </PerfectScrollbar>
          </div>

          <div className="spad-admin-sidebar__footer">
            <span className="spad-admin-sidebar__status"/>
            <div><strong>مدیریت یکپارچه اسپاد</strong><small>SPAD Admin Console</small></div>
          </div>
        </aside>
      </>
    );
  }
}

const mapStateToProps = ({authUser}) => ({currentUser: authUser.currentUser});

export default withRouter(connect(mapStateToProps)(injectIntl(Sidebar)));
