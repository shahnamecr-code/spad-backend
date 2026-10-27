import React from 'react';
import { Nav, NavItem, NavLink } from 'reactstrap';
import { Colxx } from '../../components/common/CustomBootstrap';

const Pagination = ({
                      totalPage = 0,
                      currentPage = 1,
                      onChangePage,
                      pagination_links,
                      setPaginationLinks,
                    }) => {

  const firstPageButtonClassName = currentPage <= 1 ? 'disabled' : '';
  const lastPageButtonClassName = currentPage >= totalPage ? 'disabled' : '';
  const prevPageButtonClassName = currentPage <= 1 ? 'disabled' : '';
  const nextPageButtonClassName = currentPage >= totalPage ? 'disabled' : '';
  return totalPage > 1 ? (
    <Colxx xxs="12" className="mt-3 mb-3">
      <Nav className="pagination justify-content-center">
        {pagination_links.map((link,index) => {
          if(index==0){
            return (
              <NavItem key="0" className={`page-item ${prevPageButtonClassName}`}>
                <NavLink
                  className="page-link prev c-pointer"
                  onClick={() => onChangePage(currentPage - 1)}
                >
                  <i className="simple-icon-arrow-left" />
                </NavLink>
              </NavItem>
            );
          }
          if (index==pagination_links.length-1){
            return (
              <NavItem key={pagination_links.length-1} className={`page-item ${nextPageButtonClassName}`}>
                <NavLink
                  className="page-link next c-pointer"
                  onClick={() => onChangePage(currentPage + 1)}
                >
                  <i className="simple-icon-arrow-right" />
                </NavLink>
              </NavItem>
            );
          }
          return (
            <NavItem
              key={index}
              className={`page-item ${link.active && 'active'}`}
            >
              <NavLink
                className="page-link c-pointer"
                onClick={() => {
                  if (link.url)
                    onChangePage(link.label);
                }}
              >
                {link.label}
              </NavLink>
            </NavItem>
          );
        })}
      </Nav>
    </Colxx>
  ) : (
    <Colxx xxs="12" className="mt-2" />
  )
}

export default Pagination;
