import React, {Suspense} from "react";
import {Card, CardBody, CardTitle, DropdownMenu, DropdownToggle, Row, UncontrolledButtonDropdown,} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import TableAjax from "../../../components/TableAjax";
import axios from "axios";
import {adminPathApi} from "../../../constants/defaultValues";
import {Link} from "react-router-dom";

const ContentsList = ({match, location, history}) => {
  const [data, setData] = React.useState([]);
  const [loading, setLoading] = React.useState(false);
  const [pageCount, setPageCount] = React.useState(0);


  const Actions = ({id}) => {
    return (
      <UncontrolledButtonDropdown>
        <DropdownToggle color="secondary" outline>
          <i className={'simple-icon-options-vertical'}/>
        </DropdownToggle>
        <DropdownMenu>
          <Link to={{
            pathname: `edit/${id}`,
            state: {from: location}
          }} className={'dropdown-item'}>ویرایش</Link>
          <Link to={`address/${id}`} className={'dropdown-item'}>آدرس ها</Link>
        </DropdownMenu>
      </UncontrolledButtonDropdown>
    );
  };


  const cols = React.useMemo(
    () => [
      {
        Header: 'نام',
        accessor: 'name',
        cellClass: 'list-item-heading',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'شماره تماس',
        accessor: 'mobile',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'موضوع',
        accessor: 'factor_item_count',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'محتوا',
        accessor: 'content',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'زمان ایجاد',
        accessor: 'created_at',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'وضعیت',
        accessor: 'status_text',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
    //   {
    //     Header: 'گزینه ها',
    //     cellClass: 'text-muted text-center',
    //     Cell: (props) => (<Actions id={props.row.original.id}/>),
    //   },
    ],
    []
  );

  //const tableInstance = useTable({ columns, data })
  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      await axios.get(`${adminPathApi}/contact/list${location.search ? location.search + '&' : '?'}page=${pageIndex + 1}&per_page=${pageSize}`)
        .then((res) => {
          setData({...res.data , ...res.data.meta})
          setPageCount(res.data.meta.last_page)
          return res
        })
        .catch((error) => error)
    }
    , [location.search]);


  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Row>
        <Colxx xss="12">
          <div className="text-zero top-right-button-container">
          </div>
          <Breadcrumb heading={"menu.customers.list"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx>
        <Card className="mb-4">
          <CardBody>
            <CardTitle>مشتری ها</CardTitle>
            <TableAjax columns={cols} data={data} fetchData={fetchData} loading={loading} pageCount={pageCount}/>
          </CardBody>
        </Card>
      </Colxx>
    </Suspense>
  )
};


export default ContentsList;
