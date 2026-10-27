import React, {useState} from 'react';
import {
  Button,
  Card,
  CardBody,
  DropdownItem,
  DropdownMenu,
  DropdownToggle,
  Row,
  UncontrolledButtonDropdown
} from 'reactstrap';
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import TableAjax from "../../../components/TableAjax";
import axios from "axios";
import {adminPathApi} from "../../../constants/defaultValues";

const BlogCategory = React.lazy(() => import("./blog-category"))

const BlogCategoryList = ({match}) => {
  const [data, setData] = React.useState([])
  const [loading, setLoading] = React.useState(false)
  const [pageCount, setPageCount] = React.useState(0)
  const [status, setStatus] = useState(false);
  const [category_to_edit, setCategoryToEdit] = React.useState(false);

  const toggle = () => setStatus(!status);

  const Actions = ({id, name}) => {
    return (
      <UncontrolledButtonDropdown tag={'a'}>
        <DropdownToggle color="secondary" outline>
          <i className={'simple-icon-options-vertical'}/>
        </DropdownToggle>
        <DropdownMenu>
          <DropdownItem onClick={() => {
            setCategoryToEdit(id);
            setStatus(true);
          }}>
            ویرایش
          </DropdownItem>
        </DropdownMenu>
      </UncontrolledButtonDropdown>
    );
  };

  const cols = React.useMemo(
    () => [
      {
        Header: 'نام دسته بندی',
        accessor: 'name',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'نامک',
        accessor: 'slug',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'دسته مادر',
        accessor: 'parent.name',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value ? props.value : 'بدون دسته مادر' }</>,
      },
      {
        Header: 'تاریخ ایجاد',
        accessor: 'created_at',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'گزینه ها',
        accessor: 'id',
        cellClass: 'text-muted text-center',
        Cell: (props) => (<Actions id={props.value} name={props.row.original.name}/>),
      },
    ],
    []
  );
  //const tableInstance = useTable({ columns, data })
  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      await axios.get(`${adminPathApi}/blog-category?page=${pageIndex + 1}&per_page=${pageSize}`)
        .then((res) => {
          setData(res.data)
          setPageCount(res.data.last_page)
          return res
        })
        .catch((error) => error)
    }
    , []);

  return (
    <>
      <Row>
        <Colxx md="12">
          <div className="text-zero top-right-button-container">
            <Button
              color="primary"
              className="top-right-button"
              onClick={toggle}
            >
              <i className={"fa fa-plus mr-1"}/>
              افزودن
            </Button>
          </div>
          <Breadcrumb heading="menu.blog" match={match}/>
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

      <BlogCategory status={status} toggle={toggle} category_to_edit={category_to_edit}
                    setCategoryToEdit={setCategoryToEdit} setData={setData}/>
    </>

  )
}

export default BlogCategoryList;
