import React from 'react';
import {Card, CardBody, DropdownMenu, DropdownToggle, Row, UncontrolledButtonDropdown} from 'reactstrap';
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import {adminPathApi} from '../../../constants/defaultValues';
import {Link} from 'react-router-dom';
import TableAjax from "../../../components/TableAjax";
import axios from "axios";

const ContentList = ({match}) => {
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
          <Link to={`edit/${id}`} className={'dropdown-item'}>ویرایش</Link>
        </DropdownMenu>
      </UncontrolledButtonDropdown>
    );
  };

  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      await axios.get(`${adminPathApi}/employ-request/list?page=${pageIndex + 1}&per_page=${pageSize}`)
        .then((res) => {
          setData({data: res.data.data, ...res.data.meta})
          setPageCount(res.data.meta.last_page)
          return res
        })
        .catch((error) => error)
    }
    , []);

  const cols = React.useMemo(
    () => [
      {
        Header: 'نام',
        cellClass: 'text-muted',
        Cell: (props) => <>{`${props.row.original.name} (${props.row.original.job_demand})`}</>,
      },
      {
        Header: 'آدرس',
        cellClass: 'text-muted',
        Cell: (props) => {
          return (
            <ul>
              <li><span>شهر</span> <span>{props.row.original.city}</span></li>
              <li><span>آدرس</span> <span>{props.row.original.address}</span></li>
              <li><span>کد پستی</span> <span>{props.row.original.postal_code}</span></li>
            </ul>
          )
        }
      },
      {
        Header: 'مدرک تحصیلی',
        accessor: 'study_field',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'اطلاعات تماس',
        accessor: 'contact',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'تضمین',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.row.original.guarantee?(<span className={"badge badge-success"}>دارد</span>):(<span className={"badge badge-danger"}>ندارد</span>)}</>,
      },
      {
        Header: 'سوابق شغلی',
        cellClass: 'text-muted',
        Cell: (props) => <ul>
          {props.row.original && props.row.original.work_experience && props.row.original.work_experience.map((experience,index)=>(
            <li key={index}>{experience}</li>
          ))}
        </ul>,
      }
      /*      {
              Header: 'گزینه ها',
              accessor: 'id',
              cellClass: 'text-muted text-center',
              Cell: (props) => (<Actions id={props.value}/>),
            },*/
    ],
    []
  );


  return (
    <>
      <Row>
        <Colxx md="12">
          <Breadcrumb heading="menu.employ-request.list" match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>

      <Row>
        <Colxx md="12">
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
