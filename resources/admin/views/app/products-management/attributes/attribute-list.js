import React, {Suspense} from "react";
import {Card, CardBody, DropdownMenu, DropdownToggle, Row, UncontrolledButtonDropdown,} from "reactstrap";
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import TableAjax from "../../../../components/TableAjax";
import axios from "axios";
import {adminPathApi} from "../../../../constants/defaultValues";
import {Link, useHistory} from "react-router-dom";


const AttributeList = ({match,...props}) => {
  let history = useHistory()
  const [data, setData] = React.useState([])
  const [loading, setLoading] = React.useState(false)
  const [pageCount, setPageCount] = React.useState(0)


  const Actions = ({id}) => {
    return (
      <UncontrolledButtonDropdown>
        <DropdownToggle color="secondary" outline>
          <i className={'simple-icon-options-vertical'}/>
        </DropdownToggle>
        <DropdownMenu>
          <Link to={`edit/${id}`} className={"dropdown-item"}>ویرایش</Link>
        </DropdownMenu>
      </UncontrolledButtonDropdown>
    );
  };

  const cols = React.useMemo(
    () => [
      {
        Header: 'نام ویژگی',
        accessor: 'name',
        cellClass: 'list-item-heading',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'نوع ویژگی',
        accessor: 'type',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'کد ویژگی',
        accessor: 'code',
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
      await axios.get(`${adminPathApi}/attribute?page=${pageIndex + 1}&per_page=${pageSize}`)
        .then((res) => {
          setData(res.data)
          setPageCount(res.data.last_page)
          return res
        })
        .catch((error) => error)
    }
    ,[]);


  return (
    <Suspense fallback={<div className={'loading'}/> }>
      <Row>
        <Colxx xss="12">
          <div className="text-zero top-right-button-container">
            <Link to={`create`} className={"btn btn-primary top-right-button"}>
              <i className={"fa fa-plus mr-1"}/>
              افزودن
            </Link>
          </div>
          <Breadcrumb heading={"menu.attributes.list"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx>
        <Card className="mb-4">
          <CardBody>
            <TableAjax columns={cols} data={data} fetchData={fetchData} loading={loading} pageCount={pageCount}/>
          </CardBody>
        </Card>
      </Colxx>
    </Suspense>
  )
};

export default AttributeList;

