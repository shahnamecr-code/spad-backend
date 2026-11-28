import React from 'react';
import { Link } from 'react-router-dom';
import { useIntl } from 'react-intl';
import { adminRoot } from '../../constants/defaultValues';
import { adminLabel } from './adminNavigation';

export default function AdminPageContext({ active, destinations, pathname }) {
  const intl = useIntl();
  const destination = active || destinations.find(({ item }) => !item.newWindow &&
    item.to.split('/')[2] === pathname.split('/')[2]);
  const group = destination?.parents[0];
  const related = group ? destinations.filter(({ parents }) => parents[0] === group) : [];
  if (!destination) return null;

  return (
    <header className="spad-page-context">
      <nav className="spad-page-context__crumb" aria-label="مسیر صفحه">
        <Link to={`${adminRoot}/dashboards/default`}><i className="simple-icon-grid" aria-hidden="true" /> داشبورد</Link>
        {group && <><i className="simple-icon-arrow-left" aria-hidden="true" /><span>{adminLabel(intl, group)}</span></>}
        <i className="simple-icon-arrow-left" aria-hidden="true" />
        <span aria-current="page">{adminLabel(intl, destination.item)}</span>
      </nav>
      {related.length > 1 && (
        <nav className="spad-page-context__related" aria-label="صفحات مرتبط">
          {related.map(({ item, key }) => item.newWindow
            ? <a key={key} href={item.to} target="_blank" rel="noopener noreferrer">{adminLabel(intl, item)}</a>
            : <Link key={key} to={item.to} aria-current={item === destination.item ? 'page' : undefined}>
                <i className={item.icon} aria-hidden="true" />{adminLabel(intl, item)}
              </Link>)}
        </nav>
      )}
    </header>
  );
}
