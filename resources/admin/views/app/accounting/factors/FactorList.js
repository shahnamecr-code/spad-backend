import React, {Suspense} from "react";
import {
  Card,
  CardBody,
  CardHeader,
  DropdownItem,
  DropdownMenu,
  DropdownToggle,
  Input,
  Row,
  UncontrolledButtonDropdown
} from "reactstrap";
import {Link} from "react-router-dom";
import axios from "axios";
import {adminPathApi, adminRoot} from "../../../../constants/defaultValues";
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import TableAjax from "../../../../components/TableAjax";
import Select from "react-select";

const FactorList = ({match, history, type: factorType}) => {
  const [data, setData] = React.useState([]);
  const [loading, setLoading] = React.useState(false);
  const [pageCount, setPageCount] = React.useState(0);
  const [pageSizeFor, setPageSizeFor] = React.useState(1);
  const [pageIndexFor, setPageIndexFor] = React.useState(1);
  const [search, setSearch] = React.useState('');
  const [order, setOrder] = React.useState({label: 'جدیدترین ها', value: ''});

  const orderOptions = [
    {label: 'جدیدترین ها', value: ''},
    {label: 'بیشترین مبلغ', value: 'high-cost'},
    {label: 'کمترین مبلغ', value: 'low-cost'},
  ]

  const deleteFactor = (id) => {
    swal("آیا از حذف فاکتور اطمینان دارید؟", {
      icon: "warning",
      buttons: ['نه', 'بله'],
    })
      .then(res => {
        if (res) {
          axios
            .delete(`${adminPathApi}/accounting/factor/${factorType}/${id}`)
            .then(res => res.data)
            .then(info => {
              if (info.status) {
                setData(prev => {
                  return {...prev, data: prev.data.filter(item => item.id != id)}
                })
                swal(info.message, {icon: 'success', timer: 1500, buttons: false})
              } else {
                if (info.message)
                  swal(info.message, {icon: 'warning', timer: 1500, buttons: false})
              }
            }).catch(error => {
            swal('مشکلی پیش آمده است کمی بعد دوباره تلاش کنید', {icon: 'warning', timer: 3000, buttons: false})
          })
        }
      });
  }

  const Actions = ({id, location}) => {
    return (
      <UncontrolledButtonDropdown>
        <DropdownToggle color="secondary" outline>
          <i className={'simple-icon-options-vertical'}/>
        </DropdownToggle>
        <DropdownMenu>
          <Link to={{
            pathname: `${factorType}/${id}`,
            state: {from: location}
          }} className={'dropdown-item'}>ویرایش</Link>
          <Link
            to={`${adminRoot}/accounting/factor/print/${id}`}
            className={"top-right-button dropdown-item"}
            target="_blank"
            rel="noopener noreferrer">
            چاپ
          </Link>
          <DropdownItem onClick={() => deleteFactor(id)}>حذف</DropdownItem>
        </DropdownMenu>
      </UncontrolledButtonDropdown>
    );
  };

  const cols = React.useMemo(
    () => [
      {
        Header: 'کد',
        accessor: 'id',
        cellClass: 'list-item-heading',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'نام و نام خانوادگی',
        accessor: 'first_last_name',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'تعداد اقلام',
        accessor: 'factor_item_count',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'جمع',
        accessor: 'factor_item_sum_total',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'گزینه ها',
        cellClass: 'text-muted text-center',
        Cell: (props) => (<Actions id={props.row.original.id}/>),
      },
    ],
    []
  );

  //const tableInstance = useTable({ columns, data })
  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      let source = `${adminPathApi}/accounting/factor/list/${factorType}?page=${pageIndex + 1}&per_page=${pageSize}`;
      if (search) {
        source += `&search=${search}`
      }
      if (order.value) {
        source += `&order=${order.value}`
      }
      await axios.get(source)
        .then((res) => {
          setData(res.data)
          setPageCount(res.data.last_page)
          return res
        })
        .catch((error) => error)
    }
    , [factorType, search, order]);


  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Row>
        <Colxx xss="12">
          <div className="text-zero top-right-button-container">
            <Link
              className="top-right-button btn btn-primary btn-lg"
              to={factorType == 'buy' ? `buy` : `sell`}>
              افزودن
            </Link>
          </div>
          <Breadcrumb heading={"menu.factor"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx>
        <Card className="mb-4">
          <CardHeader className="py-4 bg-info">
            <Row>
              <Colxx sm={6} md={3} className="mb-1">
                <Select options={orderOptions} value={order} onChange={(value) => setOrder(value)}/>
              </Colxx>
              <Colxx sm={6} md={3} className="mb-1">
                <Input placeholder="جستجو..." className="form-control" onChange={(e) => {
                  setSearch(e.target.value)
                }}/>
              </Colxx>
            </Row>
          </CardHeader>
          <CardBody>
            <TableAjax columns={cols} data={data} fetchData={fetchData} loading={loading} pageCount={pageCount}
                       setPageIndexFor={setPageIndexFor} setPageSizeFor={setPageSizeFor}/>
          </CardBody>
        </Card>
      </Colxx>
    </Suspense>
  );
};

export default FactorList;
