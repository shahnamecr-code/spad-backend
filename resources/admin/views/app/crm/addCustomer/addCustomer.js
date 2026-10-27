import { Field, Form, Formik } from "formik";
import React, { useState } from "react";
import {
  Button,
  Card,
  CardBody,
  FormGroup,
  Input,
  Label,
  Row,
} from "reactstrap";
import {
  Colxx,
  Separator,
} from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
const AddCustomer = ({ match }) => {
  const [initialValues, setInitialValues] = useState({});
  return (
    <>
      <Row>
        <Colxx md="12">
          <Breadcrumb heading="menu.crm.addCustomer" match={match} />
          <Separator className="mb-5" />
        </Colxx>
      </Row>

      <Row>
        <Colxx md="12">
          <Card>
            <CardBody>
              <Formik
                initialValues={initialValues}
                onSubmit={(e) => console.log(e)}
                enableReinitialize
              >
                {({
                  errors,
                  touched,
                  setFieldValue,
                  setFieldTouched,
                  values,
                  setValues,
                }) => (
                  <Form>
                    <Row>
                      <Colxx md="6">
                        <FormGroup>
                          <Label>نام</Label>
                          <Field name={"name"} className={"form-control"} />
                        </FormGroup>
                      </Colxx>
                      <Colxx md="6">
                        <FormGroup>
                          <Label>ایمیل</Label>
                          <Field name={"emial"} className={"form-control"} />
                        </FormGroup>
                      </Colxx>
                      <Colxx md="6">
                        <FormGroup>
                          <Label>شماره تماس</Label>
                          <Field name={"phone"} className={"form-control"} />
                        </FormGroup>
                      </Colxx>
                      <Colxx md="6">
                        <FormGroup>
                          <Label>رمز عبور</Label>
                          <Field name={"password"} className={"form-control"} />
                        </FormGroup>
                      </Colxx>
                    </Row>
                    <Row className="justify-content-end ml-1">
                      <Button type="submit">ثبت مشتری</Button>
                    </Row>
                  </Form>
                )}
              </Formik>
            </CardBody>
          </Card>
        </Colxx>
      </Row>
    </>
  );
};
export default AddCustomer;
