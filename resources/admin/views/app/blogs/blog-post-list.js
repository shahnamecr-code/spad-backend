import React from 'react';
import {Card, CardBody, DropdownMenu, DropdownToggle, Row, UncontrolledButtonDropdown} from 'reactstrap';
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import {adminPathApi} from '../../../constants/defaultValues';
import {Link} from 'react-router-dom';
import TableAjax from "../../../components/TableAjax";
import axios from "axios";

const ContentList = ({match})=>{
  const [data, setData] = React.useState([])
  const [loading, setLoading] = React.useState(false)
  const [pageCount, setPageCount] = React.useState(0)

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

  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      await axios.get(`${adminPathApi}/blog-post?page=${pageIndex + 1}&per_page=${pageSize}`)
        .then((res) => {
          setData(res.data)
          setPageCount(res.data.last_page)
          return res
        })
        .catch((error) => error)
    }
    ,[]);

  const cols = React.useMemo(
    () => [
      {
        Header: 'عنوان',
        accessor: 'title',
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
        Header: 'تاریخ انتشار',
        accessor: 'created_at',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'تاریخ شروع',
        accessor: 'start_time',
        cellClass: 'text-muted',
        Cell: (props) => <> {props.value && props.value.year && props.value.day && props.value.month && `${props.value.year}-${props.value.month}-${props.value.day}`}</>,
      },
      {
        Header: 'تاریخ پایان',
        accessor: 'end_time',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value && props.value.year && props.value.day && props.value.month && `${props.value.year}-${props.value.month}-${props.value.day}`}</>,
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
          <Breadcrumb heading="menu.blogs.posts" match={match}/>
          <div className="text-zero top-right-button-container">
            <Link to={`create`} className={"btn btn-primary top-right-button"}>
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
              <TableAjax columns={cols} data={data} fetchData={fetchData} loading={loading} pageCount={pageCount}/>
            </CardBody>
          </Card>
        </Colxx>
      </Row>
    </>
  )
}

export default ContentList;
