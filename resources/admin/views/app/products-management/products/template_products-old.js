import React, {Suspense} from "react";
import axios from "axios";
import {adminPathApi, adminRoot} from "../../../../constants/defaultValues";
import {Link, useHistory} from "react-router-dom";
import {
  Badge,
  Card,
  CardBody,
  DropdownItem,
  DropdownMenu,
  DropdownToggle,
  Row,
  UncontrolledButtonDropdown
} from "reactstrap";
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import TableAjax from "../../../../components/TableAjax";

const TemplateProducts=({match,...props})=>{
  let history = useHistory()
  const [data, setData] = React.useState([])
  const [template_name, setTemplateName] = React.useState('...')
  const [loading, setLoading] = React.useState(false)
  const [templateLoading, setTemplateLoading] = React.useState(true)
  const [pageCount, setPageCount] = React.useState(0)

  const Actions = ({id,row}) => {
    return (
      <UncontrolledButtonDropdown tag={'a'}>
        <DropdownToggle color="secondary" outline>
          <i className={'simple-icon-options-vertical'} />
        </DropdownToggle>
        <DropdownMenu>
          <DropdownItem onClick={() => history.push(`${adminRoot}/products-management/products/edit/${id}`)}>
            ویرایش
          </DropdownItem>
          {row.status!=0 && (
            <DropdownItem onClick={() => awaited(id)}>
              در انتظار تأیید
            </DropdownItem>
          )}
          {row.status!=1 && (
            <DropdownItem onClick={() => approve(id)}>
              تأیید
            </DropdownItem>
          )}
          {row.status!=2 && (
            <DropdownItem onClick={() => reject(id)}>
              رد کردن
            </DropdownItem>
          )}
        </DropdownMenu>
      </UncontrolledButtonDropdown>
    );
  };

  const reject=(id)=>{
    swal({
      title: 'علت رد محصول را بنویسید',
      icon: "warning",
      content: {
        element: "input",
        attributes: {
          type: "text",
        },
      },
      buttons: ['لغو', 'ثبت و رد محصول'],
    }).then(async (result)=> {
      if (result !== null) {
        axios
          .post(`${adminPathApi}/product/change-status/${id}`,{action:'reject',reject_reason:result})
          .then(res=>res.data)
          .then(data=>{
            if(data.status){
              setData((items)=>{
                return items.map(item => item.id == id ? {...item,status:2}:item);
              })
              swal(data.message,{icon:'success',timer:3000,buttons:false})
            }
          })
      }
    })
  }

  const approve=(id)=>{
    axios
      .post(`${adminPathApi}/product/change-status/${id}`,{action:'approve'})
      .then(res=>res.data)
      .then(data=>{
        if(data.status){
          setData((items)=>{
            return items.map(item => item.id == id ? {...item,status:1}:item);
          })
          swal(data.message,{icon:'success',timer:3000,buttons:false})
        }
      })
  }

  const awaited=(id)=>{
    axios
      .post(`${adminPathApi}/product/change-status/${id}`,{action:'await'})
      .then(res=>res.data)
      .then(data=>{
        if(data.status){
          setData((items)=>{
            return items.map(item => item.id == id ? {...item,status:0}:item);
          })
          swal(data.message,{icon:'success',timer:3000,buttons:false})
        }
      })
  }

  const cols = React.useMemo(
    () => [
      {
        Header: 'نام فروشگاه',
        accessor: 'shop.name',
        cellClass: 'list-item-heading',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'موجودی',
        accessor: 'quantity',
        cellClass: 'list-item-heading',
        Cell: (props) => <span className="product-quantity">{props.row.original.quantity}</span>,
      },
      {
        Header: 'قیمت',
        cellClass: 'list-item-heading',
        Cell: (props) => <span className="product-price">{props.row.original.minPrice==props.row.original.maxPrice?props.row.original.minPrice:`از ${props.row.original.minPrice} تا ${props.row.original.maxPrice}`}</span>,
      },
      {
        Header: 'وضعیت',
        cellClass: 'list-item-heading',
        Cell: (props) => (
          <>
            {props.row.original.status == 0 && (
              <Badge color="secondary">در انتظار تأیید</Badge>
            )}
            {props.row.original.status == 1 && (
              <Badge color="success">تأیید شده</Badge>
            )}
            {props.row.original.status == 2 && (
              <Badge color="danger">رد شده</Badge>
            )}
          </>
        )
      },
      {
        Header: 'گزینه ها',
        accessor: 'id',
        cellClass: 'text-muted text-center',
        Cell: (props) => (<Actions id={props.value} row={props.row.original}/>),
      },
    ],
    []
  );

  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      await axios.get(`${adminPathApi}/template/products/${match.params.id}?page=${pageIndex + 1}&per_page=${pageSize}`)
        .then((res) => {
          setData(res.data)
          setPageCount(res.data.last_page)
          return res
        })
        .catch((error) => error)
    }
    ,[]);

  React.useEffect(async ()=>{
    setTemplateLoading(true)
    await axios
      .get(`${adminPathApi}/template/${match.params.id}/get_name`)
      .then(res=>res.data)
      .then(data=>{
        if(data.status){
          setTemplateName(data.title)
          setTemplateLoading(false)
        }
      })
  },[]);

  if(templateLoading){
    return (<div className="loading"/>);
  }

  return (
    <Suspense fallback={<div className={'loading'}/> }>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={template_name} match={match}/>
          <Link className={"mx-1"} to={`${match.params.id}/create`}>
            <span className={"simple-icon-plus"}/>
          </Link>
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
export default TemplateProducts;
