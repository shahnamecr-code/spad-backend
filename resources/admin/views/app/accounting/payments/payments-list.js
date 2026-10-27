import React, {Suspense} from "react";
import {
  Button,
  Card,
  CardBody, CardHeader,
  DropdownItem,
  DropdownMenu,
  DropdownToggle, Input,
  Row,
  UncontrolledButtonDropdown
} from "reactstrap";
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import {useHistory} from "react-router-dom";
import TableAjax from "../../../../components/TableAjax";
import axios from "axios";
import {adminPathApi} from "../../../../constants/defaultValues";
import {date_convertor} from "../../../../helpers/date";
import PaymentForm from "./utils";
import Select from "react-select";

const PaymentsList = ({match, action, ...props}) => {
  let history = useHistory()
  const [data, setData] = React.useState([])
  const [loading, setLoading] = React.useState(false)
  const [pageCount, setPageCount] = React.useState(0)
  const [show_modal, setShowModal] = React.useState(false)
  const [payment_to_edit, setPaymentToEdit] = React.useState(false)

  const [search,setSearch]=React.useState('');

  const deletePayment=(id)=>{
    swal("آیا از حذف بزگه اطمینان دارید؟", {
      icon: "warning",
      buttons: ['نه', 'بله'],
    })
      .then(res=>{
        if(res){
          axios
            .delete(`${adminPathApi}/payments/${id}/delete`)
            .then(res=>res.data)
            .then(info=>{
              if(info.status){
                setData(prev=>{return {...prev,data:prev.data.filter(item=>item.id!=id)}})
                swal(info.message,{icon:'success',timer:3000,buttons:false})
              }else{
                if(info.message)
                  swal(info.message,{icon:'warning',timer:3000,buttons:false})
              }
            }).catch(error=>{
            swal('مشکلی پیش آمده است کمی بعد دوباره تلاش کنید',{icon:'warning',timer:3000,buttons:false})
          })
        }
      });
  }

  const Actions = ({id}) => {
    return (
      <UncontrolledButtonDropdown tag={'a'}>
        <DropdownToggle color="secondary" outline>
          <i className={'simple-icon-options-vertical'}/>
        </DropdownToggle>
        <DropdownMenu>
          <DropdownItem onClick={() => {
            setShowModal(true);
            setPaymentToEdit(id)
          }}>
            ویرایش
          </DropdownItem>
          <DropdownItem onClick={()=>deletePayment(id)} >حذف</DropdownItem>
        </DropdownMenu>
      </UncontrolledButtonDropdown>
    );
  };

  const cols = React.useMemo(
    () => [
      {
        Header: 'سریال برگه',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.row.original.id}</>,
      },
      {
        Header: `${(action == 'buy') ? 'دریافت از' : ((action == 'sell') ? 'پرداخت به' : 'عملیات نامشخض')}`,
        cellClass: 'text-muted',
        Cell: (props) => <>{`${props.row.original.user.first_name} ${props.row.original.user.last_name}`}</>,
      },
      {
        Header: 'توضیحات',
        accessor: 'description',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'تاریخ',
        accessor: 'created_at',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value ? date_convertor(props.value) : '---'}</>,
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

  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      let source=`${adminPathApi}/payments?page=${pageIndex + 1}&per_page=${pageSize}&action=${action}`;
      if(search){
        source+=`&search=${search}`
      }
      await axios.get(source)
        .then((res) => {
          setData(res.data)
          setPageCount(res.data.last_page)
          return res
        })
        .catch((error) => error)
    }
    , [search]);


  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={`menu.accounting.${action}-payments.list`} match={match}/>
          <Button className={"mx-1"} onClick={() => {
            setPaymentToEdit(false)
            setShowModal(true)
          }}>
            افزودن برگه
          </Button>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx>
        <Card className="mb-4">
          <CardHeader className="py-4 bg-info">
            <Row>
              <Colxx sm={6} md={3} className="mb-1">
                <Input placeholder="جستجو..." className="form-control" onChange={(e)=>{
                  setSearch(e.target.value)
                }}/>
              </Colxx>
            </Row>
          </CardHeader>
          <CardBody>
            <TableAjax columns={cols} data={data} fetchData={fetchData} loading={loading} pageCount={pageCount}/>
          </CardBody>
        </Card>
      </Colxx>
      {show_modal && (
        <PaymentForm action={action} setShowModal={setShowModal} show_modal={show_modal}
                     payment_to_edit={payment_to_edit} setPaymentToEdit={setPaymentToEdit} setData={setData}/>
      )}
    </Suspense>
  );
}

export default PaymentsList;
