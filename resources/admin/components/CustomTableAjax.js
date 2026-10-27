import {usePagination, useTable} from "react-table";
import React from "react";
import {Table} from "reactstrap";
import CustomDatatablePagination from "./CustomDatatablePagination";
import {connect} from "react-redux";
import {useLocation,useHistory} from "react-router-dom";

const CustomTableAjax = ({
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
  let location=useLocation();
  let history=useHistory();
  let query=new URLSearchParams(location.search)
  let init_per_page=10;
  let init_page=1;
  if(query.has('per_page')){
    init_per_page=query.get('per_page')
  }
  if(query.has('page')){
    init_page=query.get('page')
  }

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
    // gotoPage,
    nextPage,
    previousPage,
    // setPageSize,
    // Get the state from the instance
    state: {pageIndex, pageSize},
  } = useTable(
    {
      columns,
      data,
      initialState: {pageIndex: Math.max(0,parseInt(init_page)-1),pageSize:parseInt(init_per_page)}, // Pass our hoisted table state
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
    // let queryParameters=new URLSearchParams(location.search)
    // queryParameters.set('per_page',pageSize)
    // queryParameters.set('page',Math.max(pageIndex,1))
    // history.push('?'+queryParameters.toString())

  }, [ pageIndex, pageSize, shopId])

  React.useEffect(()=>{
    if(setPageIndexFor){
      setPageIndexFor(pageIndex);
    }
    if(setPageSizeFor){
      setPageSizeFor(pageSize);
    }
    fetchData({pageIndex, pageSize})
  },[fetchData])

  const gotoPage=(page)=>{
    let queryParameters=new URLSearchParams(location.search)
    queryParameters.set('page',1+parseInt(page))
    history.push('?'+queryParameters.toString())
  }

  const setPageSize=(per_page)=>{
    let queryParameters=new URLSearchParams(location.search)
    queryParameters.set('per_page',per_page)
    queryParameters.set('page',1)
    history.push('?'+queryParameters.toString())
    // setPageIndexFor(1)
  }

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
      <CustomDatatablePagination
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

export default connect(mapStateToProps)(CustomTableAjax);
