import React, { useEffect, useRef, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useIntl } from 'react-intl';
import { adminLabel, adminMenuKey } from './adminNavigation';

function NavigationItem({ item, ancestors, openGroups, setGroup, active, onClose, intl }) {
  const label = adminLabel(intl, item);
  const id = [...ancestors, adminMenuKey(item)].join(':');
  const group = Boolean(item.subs?.length);
  const activeGroup = group && active?.parents.includes(item);
  const isActive = !group && item === active?.item;
  const isOpen = openGroups[id] ?? Boolean(activeGroup);
  const className = 'spad-console-nav-item' + (isActive || activeGroup ? ' is-active' : '') + (ancestors.length ? ' is-child' : '');
  const content = (
    <>
      <i className={item.icon || 'simple-icon-arrow-left'} aria-hidden="true" />
      <span>{label}</span>
    </>
  );

  if (group) {
    return (
      <li>
        <button type="button" className={className} aria-expanded={isOpen} onClick={() => setGroup(id, !isOpen)}>
          {content}<i className={isOpen ? 'simple-icon-arrow-up' : 'simple-icon-arrow-down'} aria-hidden="true" />
        </button>
        {isOpen && (
          <ul className="spad-console-nav-children">
            {item.subs.map((child) => (
              <NavigationItem key={adminMenuKey(child)} {...{ item: child, ancestors: [...ancestors, adminMenuKey(item)], openGroups, setGroup, active, onClose, intl }} />
            ))}
          </ul>
        )}
      </li>
    );
  }
  return (
    <li>
      {item.newWindow
        ? <a className={className} href={item.to} target="_blank" rel="noopener noreferrer" onClick={onClose}>{content}</a>
        : <Link className={className} to={item.to} aria-current={isActive ? 'page' : undefined} onClick={onClose}>{content}</Link>}
    </li>
  );
}

export default function Sidebar({ sections, destinations, active, open, onClose }) {
  const intl = useIntl();
  const location = useLocation();
  const drawer = useRef(null);
  const closeButton = useRef(null);
  const [expanded, setExpanded] = useState({ pathname: '', groups: {} });
  const openGroups = expanded.pathname === location.pathname ? expanded.groups : {};
  const setGroup = (key, value) => setExpanded({ pathname: location.pathname, groups: { ...openGroups, [key]: value } });
  const shortcuts = destinations.filter(({ item }) => item.shortcut);

  useEffect(() => {
    if (!open) return undefined;
    const focusBeforeOpening = document.activeElement;
    const firstFocus = requestAnimationFrame(() => closeButton.current?.focus({ preventScroll: true }));
    const onKeyDown = (event) => {
      if (event.key === 'Escape') onClose();
      if (event.key !== 'Tab') return;
      const items = Array.from(drawer.current.querySelectorAll('a[href], button:not([disabled])'));
      const first = items[0];
      const last = items[items.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last?.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first?.focus();
      }
    };
    document.addEventListener('keydown', onKeyDown);
    document.body.classList.add('spad-admin-menu-open');
    return () => {
      cancelAnimationFrame(firstFocus);
      document.removeEventListener('keydown', onKeyDown);
      document.body.classList.remove('spad-admin-menu-open');
      if (focusBeforeOpening instanceof HTMLElement && focusBeforeOpening.offsetParent) focusBeforeOpening.focus();
    };
  }, [open, onClose]);

  return (
    <>
      {open && <div className="spad-console-backdrop" onClick={onClose} aria-hidden="true" />}
      <aside ref={drawer} id="spad-console-navigation" className={open ? 'spad-console-sidebar is-open' : 'spad-console-sidebar'}>
        <div className="spad-console-side-heading">
          <span>منوی مدیریت</span>
          <button ref={closeButton} type="button" className="spad-console-side-close" onClick={onClose} aria-label="بستن منوی مدیریت">
            <i className="simple-icon-close" aria-hidden="true" />
          </button>
        </div>
        <nav aria-label="بخش‌های پنل مدیریت">
          {shortcuts.length > 0 && <div className="spad-console-shortcuts" aria-label="دسترسی سریع">
            {shortcuts.map(({ item, key }) => <Link key={key} to={item.to} onClick={onClose}>
              <i className={item.icon} aria-hidden="true" /><span>{adminLabel(intl, item)}</span>
              <i className="simple-icon-arrow-left" aria-hidden="true" />
            </Link>)}
          </div>}
          {sections.map(({ title, items }) => (
            <section className="spad-console-nav-section" key={title} aria-label={title}>
              <h2>{title}</h2>
              <ul>
                {items.map((item) => (
                  <NavigationItem key={adminMenuKey(item)} {...{ item, ancestors: [], openGroups, setGroup, active, onClose, intl }} />
                ))}
              </ul>
            </section>
          ))}
        </nav>
      </aside>
    </>
  );
}
