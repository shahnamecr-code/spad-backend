import {Field, Form, Formik} from "formik";
import React, {useEffect, useState} from "react";
import {Button, Card, CardBody, FormGroup, Input, InputGroup, InputGroupAddon, Label, Row} from "reactstrap";
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import classes from '../css/visitRequests.module.css'
import VisitModal from './visitModal'
import {adminPathApi} from "../../../../constants/defaultValues";
import axios from "axios";
import * as Yup from "yup";

const listVisit = ({type}) => {
  const [showModal , setShowModal] = useState(false);
  const [data , setData] = useState(null);
  const [indexx , setIndexx] = useState(null);
  const [modalIndex , setModalIndex] = useState(null);

    const propertySchema = Yup.object().shape({
        name: Yup.string()
          .min(2)
          .max(50)
          .required('وارد کردن عنوان الزامی است.'),
      });
      const [initialValues, setInitialValues] = useState({
        code: ''
      });
      const onSubmit = (values) =>{
        console.log(values)
      }
      useEffect(()=>{
        if(type == 'waiting'){
          axios.get(`${adminPathApi}/my-meetings/waiting`)
          .then(res =>{
              setData(res.data)
              console.log(res)
          })
          .catch(({response}) => {
              alert(response)
          })
        }else if(type == 'done'){
          axios.get(`${adminPathApi}/my-meetings/done`)
          .then(res =>
              setData(res.data)
          )
          .catch(({response}) => {
              alert(response)
          })
        }else if(type == 'rejected'){
          axios.get(`${adminPathApi}/my-meetings/canceled`)
          .then(res =>
              setData(res.data)
          )
          .catch(({response}) => {
              alert(response)
          })
        }else if(type == 'expired'){
          axios.get(`${adminPathApi}/my-meetings/expired`)
          .then(res =>
              setData(res.data)
          )
          .catch(({response}) => {
              alert(response)
          })
        }
      },[type])
      const func = (index) =>{
        setShowModal(true);
        setIndexx(index);
        setModalIndex(index)
      }
  return (
    <>
      <div>
      <Formik enableReinitialize initialValues={initialValues} onSubmit={onSubmit}
                    validationSchema={propertySchema}>
              {({errors, touched, setFieldValue, setFieldTouched, values}) => (
                <Form className="av-tooltip tooltip-label-right" id="usrform">
                    <div>
                      <Card>
                        <CardBody className={`d-flex flex-wrap justify-content-around`}>
                            <Colxx md={4}>
                              <div className="d-flex">
                                <Field name={"code"} placeholder="کد آگهی..." className={'form-control rounded mb-2'}/>
                                <i className={`simple-icon-magnifier ${classes.magnify}`}></i>
                              </div>
                            </Colxx>
                            <Colxx md={4}>
                              <div className="d-flex">
                                <Field name={"code"} placeholder="کد آگهی..." className={'form-control rounded mb-2'}/>
                                <i className={`simple-icon-magnifier ${classes.magnify}`}></i>
                              </div>
                            </Colxx>
                            <Colxx md={4}>
                              <div className="d-flex">
                                <Field name={"code"} placeholder="کد آگهی..." className={'form-control rounded mb-2'}/>
                                <i className={`simple-icon-magnifier ${classes.magnify}`}></i>
                              </div>
                            </Colxx>
                        </CardBody>
                      </Card>
                    </div>
                    <div className="mt-2 mb-2">
                      <Card>
                        <CardBody>
                            <Row>
                                {data !== null && data.data.length > 0 ?
                                  data.data.map((cardData , index)=>
                                    <Colxx key={index} xxl={2} xl={3} lg={4} md={6} sm={12} className="p-2">
                                      <div className={classes.card}>
                                          <img src={cardData.advertisement.image} />
                                          <div className={classes.absolute_div}>
                                              <div className={classes.absolute_div_header}>
                                                  <button onClick={() => func(index)}>{type !== "waiting" ? 'نمایش بازدید ' : 'ثبت زمان'}</button>
                                                  {showModal && modalIndex == index && <VisitModal type={type} index={indexx} setData={setData} data={data.data} showModal={showModal} setShowModal={setShowModal} />}

                                                  <div>
                                                      <p>کد: {cardData.advertisement.id}</p>
                                                      <p>{cardData.status_text}</p>
                                                  </div>
                                              </div>
                                              <div className={classes.absolute_div_footer}>
                                                  <div>
                                                      <p>{cardData.advertisement.title}</p>
                                                  </div>
                                                  <div className={classes.absolute_div_footer_price}>
                                                      <p>{cardData.advertisement.main_props[0]?.value} {cardData.advertisement.main_props[0]?.unit}</p>
                                                      <div>
                                                          <p>{cardData.advertisement.date}</p>
                                                      </div>
                                                  </div>
                                              </div>
                                          </div>
                                      </div>
                                    </Colxx>
                                  ):
                                  <p className="w-100 m-0 text-center text-muted">بازدیدی ثبت نشده</p>
                                }
                            </Row>
                          </CardBody>
                        </Card>
                    </div>
                </Form>
              )}
            </Formik>
      </div>
    </>
  );
}

export default listVisit;
