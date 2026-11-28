import React, { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { useIntl } from 'react-intl';
import { adminLabel, normalizeAdminSearch } from './adminNavigation';

export default function AdminMenuSearch({ destinations, open, onClose }) {
  const intl = useIntl();
  const dialog = useRef(null);
  const input = useRef(null);
  const links = useRef([]);
  const [query, setQuery] = useState('');
  const terms = normalizeAdminSearch(query).split(' ').filter(Boolean);
  const results = destinations.map(({ item, parents, key }) => ({
    item, key,
    title: adminLabel(intl, item),
    parent: parents.map((entry) => adminLabel(intl, entry)).join(' / '),
  })).filter(({ item, title, parent }) => {
    const text = normalizeAdminSearch([title, parent, item.keywords, item.to].join(' '));
    return terms.every((term) => text.includes(term));
  }).slice(0, 16);

  useEffect(() => {
    const modal = dialog.current;
    if (open && !modal.open) {
      setQuery('');
      modal.showModal();
      input.current.focus();
    } else if (!open && modal.open) {
      modal.close();
    }
  }, [open]);

  const navigate = (event) => {
    if (!['ArrowDown', 'ArrowUp'].includes(event.key) || !results.length) return;
    event.preventDefault();
    const options = links.current.slice(0, results.length);
    const current = options.indexOf(document.activeElement);
    if (event.key === 'ArrowUp' && current === 0) {
      input.current.focus();
      return;
    }
    const next = current < 0
      ? (event.key === 'ArrowDown' ? 0 : options.length - 1)
      : (current + (event.key === 'ArrowDown' ? 1 : -1)) % options.length;
    options[next]?.focus();
  };

  return (
    <dialog className="spad-console-search-dialog" aria-labelledby="spad-console-search-title" ref={dialog}
      onCancel={onClose} onKeyDown={navigate} onClick={(event) => { if (event.target === dialog.current) onClose(); }}>
      <div className="spad-console-search-body">
        <header>
          <h2 id="spad-console-search-title">جست‌وجوی منوی مدیریت</h2>
          <button type="button" className="spad-console-icon-button" aria-label="بستن جست‌وجو" onClick={onClose}>
            <i className="simple-icon-close" aria-hidden="true" />
          </button>
        </header>
        <label className="spad-console-search-field">
          <i className="simple-icon-magnifier" aria-hidden="true" />
          <input ref={input} type="search" value={query} aria-label="نام بخش یا صفحه" placeholder="مثلاً آمار کاربران یا ثبت آگهی"
            onChange={(event) => setQuery(event.target.value)} />
        </label>
        {results.length ? (
          <ul aria-label="نتایج جست‌وجو">
            {results.map(({ item, title, parent, key }, index) => {
              const content = <><i className={item.icon || 'simple-icon-arrow-left'} aria-hidden="true" /><span>{title}{parent && <small>{parent}</small>}</span><i className="simple-icon-arrow-left" aria-hidden="true" /></>;
              const register = (link) => { links.current[index] = link; };
              return <li key={key}>{item.newWindow
                ? <a ref={register} href={item.to} target="_blank" rel="noopener noreferrer" onClick={onClose}>{content}</a>
                : <Link innerRef={register} to={item.to} onClick={onClose}>{content}</Link>}</li>;
            })}
          </ul>
        ) : <p role="status">بخشی با این عبارت پیدا نشد.</p>}
      </div>
    </dialog>
  );
}
