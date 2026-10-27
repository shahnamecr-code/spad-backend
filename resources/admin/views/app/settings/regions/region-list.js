import React, { Suspense } from "react";
import { Card, CardBody, DropdownMenu, DropdownToggle, Row, UncontrolledButtonDropdown } from "reactstrap";
import { Colxx, Separator } from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import TableAjax from "../../../../components/TableAjax";
import axios from "axios";
import { adminPathApi } from "../../../../constants/defaultValues";
import { Link } from "react-router-dom";

const RegionList = ({ match }) => {
  const [data, setData] = React.useState([]);
  const [loading] = React.useState(false);
  const [pageCount, setPageCount] = React.useState(0);

  const Actions = ({ id }) => (
    <UncontrolledButtonDropdown>
      <DropdownToggle color="secondary" outline><i className="simple-icon-options-vertical" /></DropdownToggle>
      <DropdownMenu><Link to={`edit/${id}`} className="dropdown-item">ویرایش</Link></DropdownMenu>
    </UncontrolledButtonDropdown>
  );

  const cols = React.useMemo(() => [
    { Header: "نام خیابان", accessor: "name", cellClass: "list-item-heading", Cell: (props) => props.value },
    { Header: "گزینه‌ها", accessor: "id", cellClass: "text-muted", Cell: (props) => <Actions id={props.value} /> },
  ], []);

  const fetchData = React.useCallback(async ({ pageSize, pageIndex }) => {
    await axios.get(`${adminPathApi}/location/region?page=${pageIndex + 1}&per_page=${pageSize}`)
      .then((res) => {
        setData(res.data);
        setPageCount(res.data.last_page || 1);
      })
      .catch(() => undefined);
  }, []);

  return (
    <Suspense fallback={<div className="loading" />}>
      <Row>
        <Colxx xss="12">
          <div className="text-zero top-right-button-container">
            <Link className="top-right-button btn btn-primary btn-lg" to="create">افزودن خیابان</Link>
          </div>
          <Breadcrumb heading="menu.regions.list" match={match} />
          <Separator className="mb-5" />
        </Colxx>
      </Row>
      <Colxx>
        <Card className="mb-4">
          <CardBody>
            <TableAjax columns={cols} data={data} fetchData={fetchData} loading={loading} pageCount={pageCount} />
          </CardBody>
        </Card>
      </Colxx>
    </Suspense>
  );
};

export default RegionList;
