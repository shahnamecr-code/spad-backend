import {Field} from "formik";
import React from "react";
import {Label, ModalBody, ModalFooter, ModalHeader} from "reactstrap";
import classes from '../../css/visitRequests.module.css'
const step2 = ({values , setStep , errors , touched}) => {

  return (
    <>
        <ModalHeader>
            ثبت اطلاعات کاربر
        </ModalHeader>
        <ModalBody>
            <div className="col-md-12">
                <Label className="mb-1">نام:</Label>
                <Field name={"first_name"} className={'form-control mb-2'}/>
            </div>
            <div className="col-md-12">
                <Label className="mb-1">نام خانوادگی:</Label>
                <Field name={"last_name"} className={'form-control mb-2'}/>
            </div>
            <div className="col-md-12">
                <Label className="mb-1">ایمیل:</Label>
                <Field name={"email"} className={'form-control mb-2'}/>
                <p className={classes.error} >{errors.email && touched.email && errors.email}</p>
            </div>
            <div className="col-md-12">
                <Label className="mb-1">رمز عبور:</Label>
                <Field name={"password"} className={'form-control mb-2'}/>
            </div>
            <div className="col-md-12">
                <Label className="mb-1">شماره موبایل کاربر:</Label>
                <Field disabled value={values.mobile} name={"mobile"} className={'form-control mb-2'}/>
            </div>
        </ModalBody>
        <ModalFooter>
            <div className="w-100 d-flex justify-content-between">
                <button type="submit" className='btn btn-success'>تایید</button>       
                <p onClick={() => setStep(1)} className='btn btn-primary mb-0'>تغییر شماره تماس</p>   
            </div>     
        </ModalFooter>
    </>
  );
}

export default step2;