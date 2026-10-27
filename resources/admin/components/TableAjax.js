import {usePagination, useTable} from "react-table";
import React from "react";
import {Table} from "reactstrap";
import DatatablePagination from "./DatatablePagination";
import {connect} from "react-redux";

const TableAjax = ({
                     columns,
                     data: serverResponse,
                     fetchData,
                     loading,
                     pageCount: controlledPageCount,
                     debug = false,
                     shopId,
                     rowsProps,
                     striped = true,
                     setPageIndexFor,
                     setPageSizeFor,
                   }) => {
  const {data = [], links, last_page} = serverResponse;
  const {
    getTableProps,
    getTableBodyProps,
    headerGroups,
    prepareRow,
    page,
    canPreviousPage,
    canNextPage,
    pageOptions,
    pageCount,
    gotoPage,
    nextPage,
    previousPage,
    setPageSize,
    // Get the state from the instance
    state: {pageIndex, pageSize},
  } = useTable(
    {
      columns,
      data,
      initialState: {pageIndex: 0}, // Pass our hoisted table state
      manualPagination: true, // Tell the usePagination
      // hook that we'll handle our own data fetching
      // This means we'll also have to provide our own
      // pageCount.
      pageCount: last_page,
    },
    usePagination
  )

  // Listen for changes in pagination and use the state to fetch our new data
  React.useEffect(() => {
    if(setPageIndexFor){
      setPageIndexFor(pageIndex);
    }
    if(setPageSizeFor){
      setPageSizeFor(pageSize);
    }
    fetchData({pageIndex, pageSize})
  }, [fetchData, pageIndex, pageSize, shopId])

  const rowProps = (props, rowInfo) => {
    return {
      ...props,
      ...rowsProps(props, rowInfo)
    };
  };
  // Render the UI for your table
  return (
    <>
      {debug &&
      <pre>
        <code>
          {JSON.stringify(
            {
              pageIndex,
              pageSize,
              pageCount,
              canNextPage,
              canPreviousPage,
            },
            null,
            2
          )}
        </code>
      </pre>
      }
      <Table {...getTableProps()} responsive bordered striped={striped}>
        <thead>
        {headerGroups.map(headerGroup => (
          <tr {...headerGroup.getHeaderGroupProps()}>
            {headerGroup.headers.map(column => (
              <th {...column.getHeaderProps()}>
                {column.render('Header')}
                <span>
                    {column.isSorted
                      ? column.isSortedDesc
                        ? ' 🔽'
                        : ' 🔼'
                      : ''}
                  </span>
              </th>
            ))}
          </tr>
        ))}
        </thead>
        <tbody {...getTableBodyProps()}>
        {page.map((row, i) => {
          prepareRow(row)
          return (
            <tr {...row.getRowProps(rowsProps ? rowProps : undefined)}>
              {row.cells.map(cell => {
                return <td {...cell.getCellProps({
                  className: cell.column.cellClass,
                })}>{cell.render('Cell')}</td>
              })}
            </tr>
          )
        })}
        <tr>
          {loading && (
            // Use our custom loading state to show a loading indicator
            <td colSpan="10000">Loading...</td>
          )}
        </tr>
        </tbody>
      </Table>
      <DatatablePagination
        ellipse
        links={links}
        page={pageIndex}
        pages={pageCount}
        canPrevious={canPreviousPage}
        canNext={canNextPage}
        pageSizeOptions={[1, 4, 10, 20, 30, 40, 50]}
        showPageSizeOptions={true}
        showPageJump={false}
        defaultPageSize={pageSize}
        onPageChange={(p) => gotoPage(p)}
        onPageSizeChange={(s) => setPageSize(s)}
        paginationMaxSize={pageCount}
      />
    </>
  )
};

const mapStateToProps = ({authUser: {shopId}}) => {
  return {shopId}
};

export default connect(mapStateToProps)(TableAjax);
