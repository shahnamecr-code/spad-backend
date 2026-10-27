import React, {Suspense} from "react";
import {Card, CardBody, DropdownMenu, DropdownToggle, Row, UncontrolledButtonDropdown,} from "reactstrap";
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import TableAjax from "../../../../components/TableAjax";
import axios from "axios";
import {adminPathApi, adminRoot} from "../../../../constants/defaultValues";
import {Link, useHistory} from "react-router-dom";


const CitiesList = ({match, currentUser, ...props}) => {
  let history = useHistory();
  const [data, setData] = React.useState([])
  const [loading, setLoading] = React.useState(false)
  const [pageCount, setPageCount] = React.useState(0)

  const Actions = ({id, row}) => {
    return (
      <UncontrolledButtonDropdown>
        <DropdownToggle color="secondary" outline>
          <i className={'simple-icon-options-vertical'}/>
        </DropdownToggle>
        <DropdownMenu>
          <Link to={`${adminRoot}/settings/cities/edit/${id}`} className={"dropdown-item"}>ویرایش</Link>
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
        Cell: (props) => (props.value),
      },
      {
        Header: 'گزینه ها',
        accessor: 'id',
        cellClass: 'text-muted',
        Cell: (props) => (<Actions id={props.value}/>),
      },
    ],
    []
  );


  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      await axios.get(`${adminPathApi}/location/province/${match.params.id}/cities?page=${pageIndex + 1}&per_page=${pageSize}`)
        .then((res) => {
          setData(res.data);
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
          <Breadcrumb heading={"menu.cities.list"} match={match}/>
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

export default CitiesList;

