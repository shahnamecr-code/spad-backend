import React, {Suspense, useState} from 'react';
import {
  Row,
  Card,
  CardBody,
  CardTitle,
  FormGroup,
  Label,
  Input,
  CustomInput,
  CardSubtitle,
  CardText,
  Button,
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Table
} from 'reactstrap';
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import {Field, FieldArray, Form, Formik} from "formik";
import {FormikReactSelect} from "../../../../containers/form-validations/FormikFields";
import Select from "react-select";
import SearchSelect from "../../../../components/SearchSelect";
import axios from "axios";
import {adminPathApi} from "../../../../constants/defaultValues";
import CartItem from "./cart-item";
import {date_convertor} from "../../../../helpers/date";
import {banks} from "../../../../data/factor";
import TableAjax from "../../../../components/TableAjax";

const AddToCart = ({match})=>{
  const [show_modal,setShowModal]=React.useState(false);
  const [init,setInit]=React.useState({});
  const [itemToEdit,setItemToEdit]=React.useState(false);
  const [item_initial_values,setItemInitialValues]=React.useState({});

  const toggle=()=>{
    setShowModal(prev=>!prev)
  };
  const submit = (val,arrayHelpers) => {
    if (itemToEdit!==false){
      arrayHelpers.replace(itemToEdit,val)
      setShowModal(false)
    }else{
      arrayHelpers.push(val)
      setShowModal(false)
    }
  };

  const save=(values)=>{
    console.log(values)
  }

  return(
    <Suspense fallback={<div className={'loading'}/>}>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={`menu.orders.cart.addToCart`} match={match}/>
          <Button className={"mx-1"}>
            افزودن برگه
          </Button>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx>
        <Card className="mb-4">
          <CardBody>
            <Formik enableReinitialize initialValues = {init} onSubmit = {save}>
              {
                ({errors, touched, setFieldValue, setFieldTouched, values, setValues}) => (
                  <Form>
                    <FieldArray
                      name="items"
                      render={arrayHelpers => (
                        <>
                          <CartItem modal = {show_modal} toggle = {toggle} submit={(val)=>submit(val,arrayHelpers)} initialValues={item_initial_values}/>
                          <Button onClick={()=> {
                            setItemToEdit(false);
                            setShowModal(true);
                            setItemInitialValues({})
                          }}>افزودن محصول</Button>
                          <Table color="info" className="my-2" bordered striped>
                            <thead>
                            <tr>
                              <th>شناسه محصول</th>
                              <th>شناسه متغیر</th>
                              <th>انبار</th>
                              <th>تعداد</th>
                            </tr>
                            </thead>
                            <tbody>
                            {values.items && values.items.length > 0 ? (
                                <>
                                  {values.items.map((item, index) => (
                                    <tr key={index}>
                                      <td>{item.product_id}</td>
                                      <td>{item.variant_id}</td>
                                      <td>{item.inventory_id}</td>
                                      <td>{item.quantity}</td>
                                      <td>
                                        <Button color="danger" outline size="xs" onClick={() => arrayHelpers.remove(index)}>
                                          <span className="fa fa-trash"/>
                                        </Button>
                                        <Button color="secondary" outline size="xs" onClick={() => {
                                          setItemToEdit(index)
                                          setShowModal(true)
                                          console.log(item)
                                          setItemInitialValues(item)
                                        }}>
                                          <span className="fa fa-pencil-alt"/>
                                        </Button>
                                      </td>
                                    </tr>
                                  ))}
                                </>
                              )
                              :
                              (
                                <tr>
                                  <td colSpan="11">موردی انتخاب نشده است</td>
                                </tr>
                              )
                            }
                            </tbody>
                          </Table>

                        </>
                      )}/>
                    <Button type="submit">ذخیره</Button>

                  </Form>
                )}
            </Formik>
          </CardBody>
        </Card>
      </Colxx>
    </Suspense>
  )
}

export default AddToCart;
