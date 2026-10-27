import React from 'react';
import axios from "axios";
import {adminPathApi, uploadPath} from "../../../../constants/defaultValues";

import ReactToPrint from "react-to-print";
import {Button, Card, CardBody, CardHeader, CardTitle, Row, Table} from "reactstrap";

class FactorToPrint extends React.Component {
  constructor(props) {
    super(props);
    this.state = {setting: {}}
  }

  componentDidMount() {
    axios
      .get(`${adminPathApi}/settings/general_setting`)
      .then(response => this.setState({setting: response.data}))
  }

  render() {
    let factor = this.props.factor.data;
    return (
      <div className="d-flex flex-column w-100 py-4 px-4 justify-content-center">
        <Card>
          <CardHeader>
            <h1 className="factor-site-name">
              <img className="factor-logo" src={`${uploadPath}/${this.state.setting.general_logo}`} alt=''/>
              {this.state.setting.general_site_name}
            </h1>
            <div className="d-flex justify-content-center align-items-center">
              <p className="factor-address flex-1 mx-1">
                آدرس دفتر فروش: {this.state.setting.general_address}
              </p>
            </div>
          </CardHeader>
          <CardBody>
            <CardTitle>
              برگه {factor.action}: #{factor.id}
              <p className="float-right" dir="ltr">{factor.time}</p>
            </CardTitle>
            <Row>
              <Table hover className="table-bordered">
                <tr>
                  <th>نام و نام خانوادگی</th>
                  <td>{factor.user.full_name}</td>
                </tr>
                <tr>
                  <th>شماره تماس</th>
                  <td>{factor.user.mobile}</td>
                </tr>
                <tr>
                  <th>توضیحات</th>
                  <td>{factor.description ?? '---'}</td>
                </tr>
              </Table>
            </Row>
            <Table hover className="mt-4 table-bordered">
              <thead>
              <tr>
                {/*<th className={'text-center'}>کد</th>*/}
                <th className={'text-center'}>عنوان آیتم</th>
                <th className={'text-center'}>شرح آیتم</th>
                <th className={'text-center'}>مقدار</th>
                <th className={'text-center'}>واحد اصلی</th>
                <th className={'text-center'}>فی</th>
                <th className={'text-center'}>تخفیف</th>
                {/*<th className={'text-center'}>جمع</th>*/}
                <th className={'text-center'}>مبلغ</th>
                {/*<th className={'text-center'}>مبلغ کل</th>*/}
              </tr>
              </thead>
              <tbody>
              {factor.items.length > 0 && factor.items.map((item, index) => (
                <tr key={index}>
                  {/*<td>{item.product_id}</td>*/}
                  <td>{item.name}</td>
                  <td>{item.description}</td>
                  <td>{item.quantity * item.unit.quantity}</td>
                  <td>{item.unit.label}</td>
                  <td>{item.price}</td>
                  <td>{item.total_discount}</td>
                  {/*<td>{item.total_quantity_price}</td>*/}
                  <td>{item.total}</td>
                  {/*<td>{item.total}</td>*/}
                </tr>
              ))}
              </tbody>
            </Table>
            <Table hover className="mt-4 table-bordered border-top-0 border-right-0">
              <tr>
                <td colSpan="6" className="border-0"/>
                <th colSpan="2" className="text-center">تخفیف کل</th>
                <td colSpan="2" className="text-center">{factor.discount}</td>
              </tr>
              <tr>
                <td colSpan="6" className="border-0"/>
                <th colSpan="2" className="text-center">جمع</th>
                <td colSpan="2" className="text-center">{factor.total}</td>
              </tr>
              <tr>
                <td colSpan="6" className="border-0"/>
                <th colSpan="2" className="text-center">تخفیف زیر فاکتور</th>
                <td colSpan="2" className="text-center">{factor.discount_under_factor}</td>
              </tr>
              <tr>
                <td colSpan="6" className="border-0"/>
                <th colSpan="2" className="text-center">مجموع اقلام</th>
                <td colSpan="2" className="text-center">{factor.counter}</td>
              </tr>
              <tr>
                <td colSpan="6" className="border-0"/>
                <th colSpan="2" className="text-center">جمع قابل پرداخت</th>
                <td colSpan="2" className="text-center">{factor.amount_payable}</td>
              </tr>
              <tr>
                <th colSpan="3" className="text-center border-left-0">حساب قبلی</th>
                <td colSpan="3" className="text-center border-right-0">
                  {factor.previous_payed>0?(
                    <>
                      بدهی: {factor.previous_payed}
                    </>
                  ):''}
                  {factor.previous_payed<0?(
                    <>
                      بستانکار: {(-1)*factor.previous_payed}
                    </>
                  ):''}
                </td>
                <th colSpan="2" className="text-center">مجموع مبلغ پرداختی</th>
                <td colSpan="2" className="text-center">{factor.payment?.items_sum_price || '---'}</td>
              </tr>
              <tr>
                <th colSpan="3" className="text-center border-left-0" >مانده حساب تا این تاریخ</th>
                <td colSpan="3" className="text-center border-right-0">
                  {((factor.amount_payable - (factor.payment?.items_sum_price || 0))*factor.multiple + factor.previous_payed)>0?(
                    <>
                      بدهی: {(factor.amount_payable - (factor.payment?.items_sum_price || 0))*factor.multiple + factor.previous_payed}
                    </>
                  ):''}
                  {((factor.amount_payable - (factor.payment?.items_sum_price || 0))*factor.multiple + factor.previous_payed)<0?(
                    <>
                      بستانکار: {(-1)*((factor.amount_payable - (factor.payment?.items_sum_price || 0))*factor.multiple + factor.previous_payed)}
                    </>
                  ):''}
                </td>
                <th colSpan="2" className="text-center">مجموع بدهی فاکتور</th>
                <td
                  colSpan="2"
                  className="text-center"
                  style={{direction: 'ltr'}}>{factor.amount_payable - (factor.payment?.items_sum_price || 0)}</td>
              </tr>

            </Table>

            <p className="factor-footer-phone">تلفن: {this.state.setting.general_phone}</p>

          </CardBody>
        </Card>
      </div>
    );
  }
}

const SellFactor = ({match, edit = false, history}) => {
  const id = match.params.id ? match.params.id : null;
  const [item, setItem] = React.useState(null);
  const [loading, setLoading] = React.useState(true);

  const componentRef = React.useRef();

  React.useEffect(async () => {
    if (id) {
      await axios.get(`${adminPathApi}/accounting/factor/${id}`)
        .then((data) => {
          setItem(data)
          setLoading(false)
        })
        .catch((error) => {
        });
    }
  }, [edit]);

  if (loading) {
    return <div className="loading"/>
  }

  return (
    <div>
      <FactorToPrint factor={item} ref={componentRef}/>
      <ReactToPrint
        trigger={() => <Button className="float-right"><span className="simple-icon-printer"/></Button>}
        content={() => componentRef.current}
      />
    </div>
  )
}

export default SellFactor;
