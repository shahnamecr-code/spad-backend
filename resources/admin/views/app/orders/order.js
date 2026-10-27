import React, {Suspense} from "react";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import {
  Badge,
  Card,
  CardBody,
  CardTitle,
  Dropdown,
  DropdownItem,
  DropdownMenu,
  DropdownToggle,
  Row,
  Table
} from "reactstrap";
import {adminPathApi} from "../../../constants/defaultValues";

const Order = ({match}) => {
  const [loading,setLoading]=React.useState(true)
  const [item,setItem]=React.useState({})
  const [dropdownOpen, setDropdownOpen] = React.useState(false);
  const [statusClass,setStatusClass]=React.useState(undefined)
  const [status,setStatus]=React.useState(undefined)
  const [statusText,setStatusText]=React.useState('نامشخص')

  const toggle = () => setDropdownOpen(prevState => !prevState);

  const cancel_order=(id)=>{
    swal('ایا از لغو سفارش اطمینان دارید؟',{
      dangerMode: true,
      buttons: true,
    }).then(value=>{
      if(value){
        axios
          .post(`${adminPathApi}/orders/${id}/change-status`,{status:-1})
          .then(res=>res.data)
          .then(data=>{
            if(data.status){
              setItem(data.item)
            }
          })
      }
    })
  }

  const process_order=(id)=>{
    swal('ایا می خواهید سفارش به وضعیت در حال پردازش برگردد؟',{
      dangerMode: true,
      buttons: true,
    }).then(value=>{
      if(value){
        axios
          .post(`${adminPathApi}/orders/${id}/change-status`,{status:1})
          .then(res=>res.data)
          .then(data=>{
            if(data.status){
              setItem(data.item)
            }
          })
      }
    })
  }

  const finish_order=(id)=>{
    swal('ایا از تکمیل سفارش اطمینان دارید؟',{
      dangerMode: true,
      buttons: true,
    }).then(value=>{
      if(value){
        axios
          .post(`${adminPathApi}/orders/${id}/change-status`,{status:2})
          .then(res=>res.data)
          .then(data=>{
            if(data.status){
              setItem(data.item)
            }
          })
      }
    })
  }

  React.useEffect(()=>{
    axios
      .get(`${adminPathApi}/orders/${match.params.id}`)
      .then(res=>res.data)
      .then(data=>{
        setItem(data)
        setLoading(false)
      })
  },[])

  if(loading){
    return <div className="loading"/>
  }

  return (
    <Suspense fallback={<div className="loading"/>}>
      <Row>
        <Colxx xxs="12">
          <Breadcrumb heading={"menu.order"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx xxs={12} className={'mb-2'}>
        <Card>
          <CardBody>
            <CardTitle>اطلاعات سفارش</CardTitle>
            <Row>
              <Colxx md={3}>
                <p><b>شماره فاکتور: </b>#{item.id}</p>
              </Colxx>
              <Colxx md={3}>
                <p><b>مبلغ فاکتور: </b>{item.total_price}</p>
              </Colxx>
              <Colxx md={3}>
                <p><b>تاریخ ثبت سفارش: </b>{item.created_at}</p>
              </Colxx>
              <Colxx md={3}>
                  <Dropdown isOpen={dropdownOpen} toggle={toggle}>
                    <DropdownToggle caret color={item.status_class}>
                      وضعیت: {item.status_text}
                    </DropdownToggle>
                    <DropdownMenu>
                      <DropdownItem onClick={()=>cancel_order(item.id)}>لغو شده</DropdownItem>
                      <DropdownItem disabled>ثبت شده</DropdownItem>
                      <DropdownItem disabled={[-1,1].includes(item.status)} onClick={()=>process_order(item.id)}>درحال  پردازش</DropdownItem>
                      <DropdownItem disabled={[-1,2].includes(item.status)} onClick={()=>finish_order(item.id)}>تکمیل سفارش</DropdownItem>
                    </DropdownMenu>
                  </Dropdown>
              </Colxx>
            </Row>
          </CardBody>
        </Card>
      </Colxx>
      <Colxx xxs={12} className={'mb-2'}>
        <Card>
          <CardBody>
            <CardTitle>اطلاعات شخص</CardTitle>
            <Row>
              <Colxx md={6}>
                <p><b>نام و نام خانوادگی:</b> {item.user.full_name}</p>
              </Colxx>
              <Colxx md={6}>
                <p><b>شماره تماس:</b> {item.user.mobile}</p>
              </Colxx>
            </Row>
          </CardBody>
        </Card>
      </Colxx>
      <Colxx xxs={12} className={'mb-2'}>
        <Card>
          <CardBody>
            <CardTitle>آدرس ارسال</CardTitle>
            <Row>
              <Colxx md={3}>
                <p><b>نام و نام خانوادگی: </b>{item.address.full_name}</p>
              </Colxx>
              <Colxx md={2}>
                <p><b>استان: </b>{item.address.province.name}</p>
              </Colxx>
              <Colxx md={2}>
                <p><b>شهر: </b>{item.address.city.name}</p>
              </Colxx>
              <Colxx md={3}>
                <p><b>کد پستی: </b>{item.address.post_code}</p>
              </Colxx>
              <Colxx md={12}>
                <p><b>آدرس: </b>{item.address.address}</p>
              </Colxx>
              <Colxx md={2}>
                <p><b>پلاک: </b>{item.address.pelak}</p>
              </Colxx>
              <Colxx md={2}>
                <p><b>واحد: </b>{item.address.vahed}</p>
              </Colxx>
              <Colxx md={3}>
                <p><b>شماره موبایل: </b>{item.address.mobile}</p>
              </Colxx>
              <Colxx md={3}>
                <p><b>شماره ثابت: </b>{item.address.phone}</p>
              </Colxx>
            </Row>
          </CardBody>
        </Card>
      </Colxx>
      <Colxx xxs={12}>
        <Card>
          <CardBody>
            <CardTitle>محصولات سفارش داده شده</CardTitle>
            <Table bordered responsive hover>
              <thead>
              <tr>
                <td>نام محصول</td>
                <td>تعداد</td>
                <td>قیمت</td>
                <td>قیمت کل</td>
                <td>میزان تخفیف</td>
              </tr>
              </thead>
              <tbody>
              {item.items.map(orderItem=>(
                <tr>
                  <td>{orderItem.product.name}</td>
                  <td>{orderItem.quantity}</td>
                  <td>{orderItem.price}</td>
                  <td>{orderItem.total_price}</td>
                  <td>{orderItem.discount_amount}</td>
                </tr>
              ))}
              </tbody>
            </Table>
          </CardBody>
        </Card>
      </Colxx>
    </Suspense>
  );
}

export default Order;
