import React from 'react';
import {CardTitle, FormGroup, Label} from 'reactstrap';
import {Colxx} from "../../../components/common/CustomBootstrap";

import {Field} from "formik";
import {FormikReactSelect} from "../../../containers/form-validations/FormikFields";


const FormLegal = ({errors, touched, setFieldValue, setFieldTouched, values}) => {
  const companyType = [
    {label: 'سهامی عام', value: "0", key: 0},
    {label: 'سهامی خاص', value: "1", key: 1},
    {label: 'مسولیت محدود', value: "2", key: 2},
    {label: 'تعاونی', value: "3", key: 3},
    {label: 'تضامنی', value: "4", key: 4},
  ];

  return (
    <>
      <CardTitle tag="h3" className="mt-5">اطلاعات شرکتی</CardTitle>
      <FormGroup row>
        <Colxx md={8}>
          <FormGroup>
            <Label>نام شرکت</Label>
            <Field
              className="form-control"
              name="company_name"
              id="company_name"
            />
          </FormGroup>
        </Colxx>
        <Colxx md={4}>
          <FormGroup>
            <Label>نوع شرکت</Label>
            <FormikReactSelect
              name="company_type"
              options={companyType}
              onChange={setFieldValue}
              onBlur={setFieldTouched}
              value={values.company_type}
            />
          </FormGroup>
        </Colxx>
        <Colxx md={4}>
          <FormGroup>
            <Label>شماره ثبت</Label>
            <Field
              className="form-control text-md-left"
              name="register_number"
            />
          </FormGroup>
        </Colxx>
        <Colxx md={4}>
          <FormGroup>
            <Label>شناسنامه ملی</Label>
            <Field
              className="form-control text-md-left"
              name="identity_number"
            />

          </FormGroup>
        </Colxx>
        <Colxx md={4}>
          <FormGroup>
            <Label>کد اقتصادی</Label>
            <Field
              className="form-control text-md-left"
              name="economic_number"
            />
          </FormGroup>
        </Colxx>
      </FormGroup>

    </>
  )
}

export default FormLegal;
