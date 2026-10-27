import React, {Suspense} from "react";
import {Card, CardBody, CardTitle, DropdownMenu, DropdownToggle, Row, UncontrolledButtonDropdown} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import TableAjax from "../../../components/TableAjax";
import axios from "axios";
import {adminPathApi} from "../../../constants/defaultValues";
import {Link} from "react-router-dom";

const OrdersList = ({match}) => {
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
          <Link to={`${id}`} className={'dropdown-item'}>مشاهده</Link>
        </DropdownMenu>
      </UncontrolledButtonDropdown>
    );
  };

  const cols = React.useMemo(
    () => [
      {
        Header: 'کد سفارش',
        accessor: 'id',
        cellClass: 'list-item-heading',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'نام سفارش دهنده',
        accessor: 'user.full_name',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'نوع پرداخت',
        accessor: 'payment_type',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'تاریخ ثبت سفارش',
        accessor: 'created_at',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'وضعیت',
        accessor: 'status',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'گزینه ها',
        cellClass: 'text-muted text-center',
        Cell: ({row}) => (<Actions id={row.original.id}/>),
      },
    ],
    []
  );

  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      await axios.get(`${adminPathApi}/orders?page=${pageIndex + 1}&per_page=${pageSize}`)
        .then((res) => {
          setData({data: res.data.data, ...res.data.meta});
          setPageCount(res.data.last_page);
          return res
        })
        .catch((error) => error)
    }
    , []);

  return (
    <Suspense fallback={<div className="loading"/>}>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={"menu.orders"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx xss={12}>
        <Card>
          <CardBody>
            <CardTitle>لیست سفارشات</CardTitle>
            <TableAjax columns={cols} data={data} fetchData={fetchData} loading={loading} pageCount={pageCount}/>
          </CardBody>
        </Card>
      </Colxx>
    </Suspense>
  )
}

export default OrdersList;
