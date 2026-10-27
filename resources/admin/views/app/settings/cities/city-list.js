import React, { Suspense } from "react";
import { Card, CardBody, CardTitle, DropdownMenu, DropdownToggle, Row, UncontrolledButtonDropdown } from "reactstrap";
import { Colxx, Separator } from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import TableAjax from "../../../../components/TableAjax";
import axios from "axios";
import { adminPathApi } from "../../../../constants/defaultValues";
import { Link } from "react-router-dom";

const CitiesList = ({ match }) => {
  const [data, setData] = React.useState([]);
  const [loading] = React.useState(false);
  const [pageCount, setPageCount] = React.useState(0);

  const Actions = ({ id }) => (
    <UncontrolledButtonDropdown>
      <DropdownToggle color="secondary" outline>
        <i className="simple-icon-options-vertical" />
      </DropdownToggle>
      <DropdownMenu>
        <Link to={`edit/${id}`} className="dropdown-item">ویرایش اطلاعات شیراز</Link>
      </DropdownMenu>
    </UncontrolledButtonDropdown>
  );

  const cols = React.useMemo(() => [
    {
      Header: "شهر ثابت سایت",
      accessor: "name",
      cellClass: "list-item-heading",
      Cell: (props) => props.value,
    },
    {
      Header: "وضعیت",
      accessor: "id",
      Cell: () => <span className="badge badge-success">فعال و غیرقابل تغییر</span>,
    },
    {
      Header: "گزینه‌ها",
      accessor: "id",
      cellClass: "text-muted",
      Cell: (props) => <Actions id={props.value} />,
    },
  ], []);

  const fetchData = React.useCallback(async ({ pageSize, pageIndex }) => {
    await axios
      .get(`${adminPathApi}/location/city?page=${pageIndex + 1}&per_page=${pageSize}`)
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
          <Breadcrumb heading="menu.cities.list" match={match} />
          <Separator className="mb-5" />
        </Colxx>
      </Row>
      <Colxx>
        <Card className="mb-4">
          <CardBody>
            <CardTitle>SPAD فقط برای شهر شیراز فعال است</CardTitle>
            <p className="text-muted mb-4">
              استان از ساختار مدیریتی حذف شده و شهر سایت به صورت ثابت «شیراز» است. فقط اطلاعات مکانی خود شیراز قابل ویرایش است.
            </p>
            <TableAjax columns={cols} data={data} fetchData={fetchData} loading={loading} pageCount={pageCount} />
          </CardBody>
        </Card>
      </Colxx>
    </Suspense>
  );
};

export default CitiesList;
