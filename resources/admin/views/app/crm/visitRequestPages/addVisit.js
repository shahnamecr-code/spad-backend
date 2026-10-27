import {Field, Form, Formik} from "formik";
import React, {useEffect, useState} from "react";
import {Button, Card, CardBody, FormGroup, Input, InputGroup, InputGroupAddon, Label, Row} from "reactstrap";
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import classes from '../css/visitRequests.module.css'
import VisitModal from './visitModal'
import {adminPathApi} from "../../../../constants/defaultValues";
import axios from "axios";
import ReactSelect from "react-select";
import * as Yup from "yup";

const addVisit = ({type , cate}) => {
  const [showModal , setShowModal] = useState(false);
  const [data , setData] = useState(null);
  const [modalIndex , setModalIndex] = useState(null);
  const [searchId , setSearchId] = useState(null);

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
        searchId ?
        axios.get(`${adminPathApi}/advertisements/list?type=skill&category=${searchId}`)
          .then(res =>
            setData(res.data.data)
          ).catch(response =>{
            alert(response)
          })
          :
          axios.get(`${adminPathApi}/advertisements/list?type=skill`)
          .then(res =>
            setData(res.data.data)
          ).catch(response =>{
            alert(response)
          })
      },[searchId])
      const func = (index) =>{
        setShowModal(true);
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
                              <Field placeholder="کد آگهی..." name={"code"} className={'form-control rounded mb-2'}/>
                            </Colxx>
                            <Colxx md={4}>
                              <ReactSelect
                                  placeholder="جستجو با نوع معامله"
                                  options={cate}
                                  getOptionLabel={(option) => option.name}
                                  getOptionValue={(option) => option.id}
                                  onChange={(value) =>{
                                    console.log(value)
                                    setFieldValue('code' , value.id);
                                    setSearchId(value.id);
                                  }}
                                />
                            </Colxx>
                            <Colxx md={4}>
                              <Field placeholder="کد آگهی..." name={"code"} className={'form-control rounded mb-2'}/>
                            </Colxx>
                        </CardBody>
                      </Card>
                    </div>
                    <div className="mt-2 mb-2">
                      <Card>
                        <CardBody>
                            <Row>
                              {data && data.length > 0 ? data.map((cardData , index)=>(
                                cardData && cardData.main_props[0] &&
                                <Colxx xxl={2} xl={3} lg={4} md={6} sm={12} className="p-2">
                                <div className={classes.card}>
                                    <img src={cardData.image} />
                                    <div className={classes.absolute_div}>
                                        <div className={classes.absolute_div_header}>
                                            <button onClick={() => func(index)}>ثبت زمان </button>
                                            {showModal && modalIndex == index && <VisitModal adver_id={data[index].id} type={type} showModal={showModal} setShowModal={setShowModal} />}
                                            <div>
                                                <p>کد: {cardData.id}</p>
                                                <p>{cardData.category}</p>
                                            </div>
                                        </div>
                                        <div className={classes.absolute_div_footer}>
                                            <div>
                                                <p>{cardData.title}</p>
                                            </div>
                                            <div className={classes.absolute_div_footer_price}>
                                                <p>{cardData.main_props[0]?.value} : {cardData.main_props[0]?.unit}</p>
                                                <div>
                                                    <p>{cardData.date}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                </Colxx>
                              ))
                              :
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

export default addVisit;
