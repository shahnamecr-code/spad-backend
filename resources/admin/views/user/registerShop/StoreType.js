import {CardTitle, CustomInput, FormGroup, Label} from "reactstrap";
import {Field} from "formik";
import React from "react";
import Select from "react-select";


const Actual = ({setFieldValue, setTouched, values}) => {
  return (
    <>
      <CardTitle>اطلاعات شخصی</CardTitle>
      <FormGroup className="has-float-label  mb-4">
        <Label>
          نام
        </Label>
        <Field className="form-control" name={'first_name'}/>
      </FormGroup>
      <FormGroup className="has-float-label">
        <Label>
          نام خانوادگی
        </Label>
        <Field type="email" className="form-control" name={'last_name'}/>
      </FormGroup>
      <FormGroup className="has-float-label  mb-4">
        <Label>
          تاریخ تولد
        </Label>
        <Field className="form-control" name={'birthday'}/>
      </FormGroup>
      <FormGroup className="has-float-label  mb-4">
        <Label>
          شماره شناسنامه
        </Label>
        <Field className="form-control" name={'identity_card_number'}/>
      </FormGroup>
      <FormGroup className="has-float-label  mb-4">
        <Label>
          کد ملی
        </Label>
        <Field className="form-control" name={'national_identity_number'}/>
      </FormGroup>
      <FormGroup className="mb-4">
        <Label>
          جنسیت
        </Label>
        <div>
          <CustomInput key={'men'} type="radio" id={'men'} label={"مرد"} name={'gender'}
                       checked={values.gender == 'men' ? true : false}
                       onChange={(val) => setFieldValue('gender', 'men')} onBlur={setTouched} inline
          />
          <CustomInput key={'women'} type="radio" id={'women'} label={"زن"} name={'gender'}
                       checked={values.gender == 'women' ? true : false}
                       onChange={(val) => setFieldValue('gender', 'women')} onBlur={setTouched} inline/>
        </div>
      </FormGroup>
    </>
  )
};

const Legal = ({setFieldValue, setTouched, values}) => {
  const companyType = [
    {label: 'سهامی عام', value: "0", key: 0},
    {label: 'سهامی خاص', value: "1", key: 1},
    {label: 'مسولیت محدود', value: "2", key: 2},
    {label: 'تعاونی', value: "3", key: 3},
    {label: 'تضامنی', value: "4", key: 4},
  ];

  return (
    <>
      <CardTitle>اطلاعات شرکتی</CardTitle>
      <FormGroup className="has-float-label  mb-4">
        <Label>
          نام شرکت
        </Label>
        <Field className="form-control" name={'company_name'}/>
      </FormGroup>
      <FormGroup className="has-float-label  mb-4">
        <Label>نوع شرکت</Label>
        <Select
          name={'company_type'}
          options={companyType}
          className={'react-select'}
          classNamePrefix="react-select"
          onChange={(val) => setFieldValue('company_type', val.value)}
          value={companyType ? companyType.find(option => option.value === values.company_type) : ''}
        />
      </FormGroup>
      <FormGroup className="has-float-label  mb-4">
        <Label>
          شماره ثبت
        </Label>
        <Field className="form-control" name={'register_number'}/>
      </FormGroup>
      <FormGroup className="has-float-label  mb-4">
        <Label>
          شناسه ملی
        </Label>
        <Field className="form-control" name={'identity_number'}/>
      </FormGroup>
      <FormGroup className="has-float-label  mb-4">
        <Label>
          کد اقتصادی
        </Label>
        <Field className="form-control" name={'economic_number'}/>
      </FormGroup>
    </>
  )
};



export {Actual,Legal};
