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
import {Link, useHistory} from "react-router-dom";
import {connect} from "react-redux";


const OperatorsList = ({match, error, shopId}) => {
  let history = useHistory();
  const [data, setData] = React.useState([]);
  const [loading, setLoading] = React.useState(false);
  const [pageCount, setPageCount] = React.useState(0);

  const deleteOperator = (id) => {
    swal("آیا از حذف کارشناس اطمینان دارید؟", {
      icon: "warning",
      buttons: ['نه', 'بله'],
    })
      .then(res => {
        if (res) {
          axios
            .delete(`${adminPathApi}/admin/${id}`)
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
          <Link to={`edit/${id}`} className={"dropdown-item"}>ویرایش</Link>
          <Link to={`permission/${id}`} className={"dropdown-item"}>ویرایش دسترسی ها</Link>
          <DropdownItem onClick={() => deleteOperator(id)}>حذف</DropdownItem>
        </DropdownMenu>
      </UncontrolledButtonDropdown>
    );
  };

  const cols = React.useMemo(
    () => [
      {
        Header: 'نام',
        accessor: 'first_name',
        cellClass: 'list-item-heading',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'نام خانوادگی',
        accessor: 'last_name',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'نام کاربری',
        accessor: 'username',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'ایمیل',
        accessor: 'email',
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
      await axios.get(`${adminPathApi}/operator?page=${pageIndex + 1}&per_page=${pageSize}`)
        .then((res) => {
          setData(res.data)
          setPageCount(res.data.last_page)
          return res
        })
        .catch((error) => error)
    }
    , []);

  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={"menu.operators.list"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx>
        <Card className="mb-4">
          <CardBody>
            <CardTitle>اپراتور ها</CardTitle>
            <TableAjax columns={cols} data={data} fetchData={fetchData} loading={loading} pageCount={pageCount}/>
          </CardBody>
        </Card>
      </Colxx>
    </Suspense>
  )
};

const mapStateToProps = ({authUser}) => {
  const {error} = authUser;
  return {error};
};

export default connect(mapStateToProps)(OperatorsList);
