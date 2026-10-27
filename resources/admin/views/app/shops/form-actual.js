import React from 'react';
import {CardTitle, CustomInput, FormGroup, Label} from 'reactstrap';
import {Colxx} from "../../../components/common/CustomBootstrap";

import {Field} from "formik";


const FormActual = ({errors, touched, setFieldValue, setFieldTouched, values}) => {


  return (
    <>
      <CardTitle tag="h3" className="mt-5">اطلاعات شخصی</CardTitle>
      <FormGroup row>

        <Colxx md={6}>
          <FormGroup>
            <Label>نام</Label>
            <Field className="form-control" name="first_name" id="first_name"/>
          </FormGroup>
        </Colxx>

        <Colxx md={6}>
          <FormGroup>
            <Label>نام خانوادگی</Label>
            <Field
              className="form-control"
              name="last_name"
              id="last_name"
            />
          </FormGroup>
        </Colxx>

      </FormGroup>

      <FormGroup row>
        <Colxx md="12">
          <Label>تاریخ تولد</Label>
          <Field
            className="form-control"
            name="birthday"
          />

        </Colxx>
      </FormGroup>

      <FormGroup row className="mt-5">
        <Colxx md="6">
          <FormGroup>
            <Label>جنسیت</Label>
            <CustomInput type="radio" label="مرد" id="gender-male" value={values.gender} name="gender"
                         onChange={() => setFieldValue("gender", "men")}
                         checked={values.gender == "men" ? true : false} inline/>
            <CustomInput type="radio" label="زن" id="gender-female" value={values.gender} name="gender"
                         onChange={() => setFieldValue("gender", "women")}
                         checked={values.gender == "women" ? true : false} inline/>
          </FormGroup>
        </Colxx>
      </FormGroup>

      <FormGroup row className="mt-3">
        <Colxx md="6">
          <FormGroup>
            <Label>شماره شناسنامه</Label>
            <Field
              className="form-control"
              name="identity_card_number"
            />
          </FormGroup>
        </Colxx>

        <Colxx md="6">
          <FormGroup>
            <Label>کد ملی</Label>
            <Field
              className="text-md-left form-control"
              name="national_identity_number"
            />

          </FormGroup>
        </Colxx>
      </FormGroup>

    </>
  )
}

export default FormActual;
