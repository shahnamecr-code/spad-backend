import React, { useEffect, useState } from "react";

import * as yup from 'yup';
import {
  Button,
  Card,
  CardBody,
  FormGroup,
  Input,
  InputGroup,
  InputGroupAddon,
  Label,
  Row,
} from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { Field, Form, Formik } from "formik";
import { adminPathApi } from "../../../constants/defaultValues";
import AlertError from "../../../components/AlertError";
import axios from "axios";
import ContinuesCategorySelect from "../../../components/ContinuesCategorySelect";
import ReactSelect from "react-select";
import CrmFilterItem from "../../../components/CrmFilterItem";
import classes from './css/visitRequests.module.css'
import { useHistory } from "react-router-dom";
import { NotificationManager } from "../../../components/common/react-notifications";

function FormikReactSelect() {
  return null;
}

const AddCustomer = ({ match, ...rest }) => {
  const history = useHistory();
  const [step, setStep] = useState(1);
  const [firstInit, setFirstInit] = useState({ mobile: "" });
  const [secondInit, setSecondInit] = useState({});
  const [thirdInit, setThirdInit] = useState({});
  const [categories, setCategories] = useState([]);
  const [loadCategories, setLoadCategories] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState(false);
  const [children, setChildren] = useState(false);
  const [crmFields, setCrmFields] = useState(false);
  const [loadingCrmFields, setLoadingCrmField] = useState(false);
  const [user, setUser] = useState(false);

  useEffect(() => {
    let subs;
    if (selectedCategory) {
      subs = categories.filter(
        (category) => category.parent == selectedCategory
      );
      if (subs.length > 0) {
        setChildren(subs);
      } else {
        setChildren(false);
      }
    }
    if (selectedCategory && step == 3 && subs && subs.length == 0) {
      setLoadingCrmField(true);
      axios
        .get(
          `${adminPathApi}/advertisement-category/show-crm-layout/${selectedCategory}`
        )
        .then((res) => res.data)
        .then((data) => {
          setCrmFields(data.searchItems ?? []);
          setLoadingCrmField(false);
        });
    } else {
      setCrmFields(false);
    }
  }, [selectedCategory]);

  useEffect(() => {
    axios
      .get(`${adminPathApi}/advertisement-category/showAllCategoriesWithParent`)
      .then((res) => res.data)
      .then((data) => {
        setCategories(data);
        setLoadCategories(true);
      });
  }, [step]);

  const [serverError, setServerError] = useState({});

  const checkCustomerExistence = (values) => {
    axios
      .post(`${adminPathApi}/customer/check-exist`, values)
      .then((res) => res.data)
      .then((data) => {
        if (data.status) {
          setUser(data.customer);
          setStep(3);
        } else {
          setSecondInit({ mobile: values.mobile ?? "" });
          setStep(2);
        }
      });
    /*    if (values.mobile==1){
          setSecondInit({mobile:values.mobile??''})
          setStep(2)
        }else if (values.mobile==2){

        }*/
  };

  const changeCustomer = (values) => {
    setStep(1);
    setFirstInit(values);
  };

  const addCustomer = (values) => {
    axios
      .post(`${adminPathApi}/customer/add-crm-customer`, values)
      .then((res) => res.data)
      .then((data) => {
        if (data.status) {
          setUser(data.customer);
          setStep(3);
        }
      })
      .catch(({ response }) => {
        setServerError(response.data.errors);
      });
  };

  const addCrmRequest = (values) => {
    axios
      .post(`${adminPathApi}/crm/add-request/${selectedCategory}`, {
        req: values,
        user: user.id,
      })
      .then((res) => res.data)
      .then((data) => {
        if (data.status) {
          history.push('/spad-admin/crm/all-requests');
          NotificationManager.success(data.message);
        }
      });
  };

  let validate = yup.object().shape({
    mobile: yup.string().matches(/^((\+98|0)9\d{9})$/m,"شماره تلفن معتبر نمیباشد").required("این فیلد اجباری است"),
    email: yup.string().email("ایمیل معتبر نیست")
  });

  return (
    <>
      <Row>
        <Colxx md="12">
          <Breadcrumb heading="menu.crm.addCustomer" match={match} />
          <Separator className="mb-5" />
        </Colxx>
      </Row>
      <Row>
        {step == 3 ? (
          <Colxx md={12}>
            <Card>
              <CardBody>
                <ul>
                  <li>
                    <span>نام: </span>
                    <span>{`${user.first_name} ${user.last_name}`}</span>
                  </li>
                  <li>
                    <span>شماره تماس: </span>
                    <span>{`${user.mobile}`}</span>
                  </li>
                </ul>
                <Button
                  onClick={() => {
                    setStep(1);
                  }}
                >
                  تغییر مشتری
                </Button>
              </CardBody>
            </Card>
          </Colxx>
        ) : (
          ""
        )}
        <Colxx md="12" className={"mt-2"}>
          <Card>
            <CardBody>
              {step == 1 ? (
                <Formik
                  initialValues={firstInit}
                  onSubmit={checkCustomerExistence}
                  enableReinitialize
                >
                  {({}) => (
                    <Form>
                      <Row>
                        <Colxx md={5}>
                          <Label>شماره تماس</Label>
                          <InputGroup>
                            <Field className={"form-control"} name={"mobile"} />
                            <InputGroupAddon addonType="append">
                              <Button type={"submit"}>ادامه</Button>
                            </InputGroupAddon>
                          </InputGroup>
                        </Colxx>
                      </Row>
                    </Form>
                  )}
                </Formik>
              ) : (
                ""
              )}
              {step == 2 ? (
                <Formik
                  initialValues={secondInit}
                  onSubmit={addCustomer}
                  enableReinitialize
                  validationSchema={validate}
                >
                  {({errors, touched, setFieldValue, values}) => (
                    <Form>
                      <AlertError errors={serverError} />
                      <Row>
                        <Colxx md="6">
                          <FormGroup>
                            <Label>نام</Label>
                            <Field
                              name={"first_name"}
                              className={"form-control"}
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="6">
                          <FormGroup>
                            <Label>نام خانوادگی</Label>
                            <Field
                              name={"last_name"}
                              className={"form-control"}
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md={6}>
                          <Label>شماره تماس</Label>
                          <Field
                            className={"form-control"}
                            name={"mobile"}
                            disabled
                          />
                        </Colxx>
                      </Row>
                      <Row className="justify-content-end ml-1">
                        <Button
                          onClick={() =>
                            changeCustomer({ mobile: values.mobile ?? "" })
                          }
                          color={"info"}
                          outline
                        >
                          تغییر مشتری
                        </Button>
                        <Button type="submit">ثبت مشتری</Button>
                      </Row>
                    </Form>
                  )}
                </Formik>
              ) : (
                ""
              )}
              {step == 3 ? (
                <>
                <AlertError errors={serverError}/>
                  {loadCategories ? (
                    <Formik
                      initialValues={thirdInit}
                      onSubmit={addCrmRequest}
                      enableReinitialize
                    >
                      {({ values, setFieldValue }) => (
                        <Form>
                          <Row>
                            <ContinuesCategorySelect
                              categories={categories}
                              value={selectedCategory}
                              setSelectedCategory={setSelectedCategory}
                            />
                            {children ? (
                              <Colxx md={4}>
                                <ReactSelect
                                  options={children}
                                  value={""}
                                  getOptionLabel={(option) => option.name}
                                  getOptionValue={(option) => option.slug}
                                  onChange={(value) =>
                                    setSelectedCategory(value.slug)
                                  }
                                />
                              </Colxx>
                            ) : (
                              ""
                            )}
                          </Row>
                        </Form>
                      )}
                    </Formik>
                  ) : (
                    <div className={"loading"} />
                  )}
                </>
              ) : (
                ""
              )}
            </CardBody>
          </Card>
        </Colxx>
        <Colxx md="12" className={"mt-2"}>
          {crmFields ? (
            <>
              {loadingCrmFields ? (<div className={"loading"}/>) : (
                <Card>
                  <CardBody>
                    <div>
                      {crmFields && crmFields.length ? (
                        <Formik
                          initialValues={{}}
                          onSubmit={addCrmRequest}
                          enableReinitialize
                        >
                          {({}) => (
                            <Form>
                              <Row>

                                {crmFields.map((field,index) => (
                                  <Colxx md={12} className="mb-3">
                                    <Card>
                                      <CardBody>
                                        <Label className="border-primary border-bottom w-100 mb-4 h5">{field.label}</Label>
                                        <Row>
                                          {field.items.map((field)=> <CrmFilterItem key={index} field={field}/>)}
                                        </Row>
                                      </CardBody>
                                    </Card>
                                  </Colxx>
                                ))}
                                <Colxx sm={12}>
                                  <Button type={"submit"}>ارسال درخواست</Button>
                                </Colxx>
                              </Row>
                            </Form>
                          )}</Formik>
                      ) : ''}
                    </div>
                  </CardBody>
                </Card>
              )}
            </>
          ) : (
            ""
          )}
        </Colxx>
      </Row>
    </>
  );
};

export default AddCustomer;
