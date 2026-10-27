import React, {Suspense} from "react";
import {
  Card,
  CardBody,
  CardTitle,
  DropdownItem,
  DropdownMenu,
  DropdownToggle,
  Row,
  UncontrolledButtonDropdown,
} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import TableAjax from "../../../components/TableAjax";
import axios from "axios";
import {adminPathApi} from "../../../constants/defaultValues";
import {Link} from "react-router-dom";

const CustomersList = ({match, location, history}) => {
  const [data, setData] = React.useState([]);
  const [loading, setLoading] = React.useState(false);
  const [pageCount, setPageCount] = React.useState(0);

  const deleteCustomer = (id) => {
    swal("آیا از حذف مشتری اطمینان دارید؟", {
      icon: "warning",
      buttons: ['نه', 'بله'],
    })
      .then(res => {
        if (res) {
          axios
            .delete(`${adminPathApi}/customer/${id}`)
            .then(res => res.data)
            .then(info => {
              if (info.status) {
                setData(prev => {
                  let data = prev.data.filter(item => id !== item.id);
                  let total=prev.total-1;
                  return {...prev,total:total,data:data};
                })
                swal(info.message, {icon: 'success', timer: 1500, buttons: false})
              } else if (info.message)
                swal(info.message, {icon: 'warning', timer: 1500, buttons: false})
            })
        }
      })
  }


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
           <DropdownItem onClick={() => deleteCustomer(id)}>حذف</DropdownItem>
           {/* <Link to={`address/${id}`} className={'dropdown-item'}>آدرس ها</Link> */}
        </DropdownMenu>
      </UncontrolledButtonDropdown>
    );
  };

  const cols = React.useMemo(
    () => [
      {
        Header: 'نام و نام خانوادگی',
        accessor: 'full_name',
        cellClass: 'list-item-heading',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'ایمیل',
        accessor: 'email',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'شماره تماس',
        accessor: 'mobile',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'گزینه ها',
        accessor: 'id',
        cellClass: 'text-muted text-center',
        Cell: (props) => (<Actions id={props.value}/>),
      },
    ],
    []
  );

  //const tableInstance = useTable({ columns, data })
  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      await axios.get(`${adminPathApi}/customer${location.search ? location.search + '&' : '?'}page=${pageIndex + 1}&per_page=${pageSize}`)
        .then((res) => {
          setData(res.data)
          setPageCount(res.data.last_page)
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
            <Link to={`create`} className={"btn btn-primary top-right-button"}>
              <i className={'simple-icon-user-follow mr-1'}/>
              افزودن
            </Link>
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


export default CustomersList;
