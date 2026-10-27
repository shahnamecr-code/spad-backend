import React, { useEffect } from 'react';
import {Card, CardBody, DropdownMenu, DropdownToggle, Row, UncontrolledButtonDropdown} from 'reactstrap';
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import {adminPathApi} from '../../../../constants/defaultValues';
import {Link} from 'react-router-dom';
import TableAjax from "../../../../components/TableAjax";
import axios from "axios";

const SidebarList = ({match})=>{
  const [data, setData] = React.useState([])
  const [loading, setLoading] = React.useState(false)

  const Actions = ({id}) => {
    return (
      <UncontrolledButtonDropdown>
        <DropdownToggle color="secondary" outline>
          <i className={'simple-icon-options-vertical'} />
        </DropdownToggle>
        <DropdownMenu>
          <Link to={`edit/${id}`} className={'dropdown-item'}>ویرایش</Link>
        </DropdownMenu>
      </UncontrolledButtonDropdown>
    );
  };

  const fetchData = React.useCallback(async () => {
      await axios.get(`${adminPathApi}/settings/sidebar`)
        .then((res) => {
          setData(res)
        })
        .catch((error) => error)
    }
    ,[]);
  const cols = React.useMemo(
    () => [
      {
        Header: 'نام سایدبار',
        accessor: 'name',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'تاریخ انتشار',
        accessor: 'created_at',
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


  return(
    <>
      <Row>
        <Colxx md="12">
          <Breadcrumb heading="menu.sidebar" match={match}/>
          <div className="text-zero top-right-button-container">
            <Link to={`sidebar-post`} className={"btn btn-primary top-right-button"}>
              <i className={"fa fa-plus mr-1"}/>
              افزودن
            </Link>
          </div>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>

      <Row>
        <Colxx md = "12">
          <Card>
            <CardBody>
              <TableAjax columns={cols} data={data} fetchData={fetchData} loading={loading}/>
            </CardBody>
          </Card>
        </Colxx>
      </Row>
    </>
  )
}

export default SidebarList;
