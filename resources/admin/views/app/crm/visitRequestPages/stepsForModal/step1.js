import {Field} from "formik";
import React from "react";
import {Label, ModalBody , ModalHeader, ModalFooter} from "reactstrap";
import classes from '../../css/visitRequests.module.css'

const step1 = ({errors , touched}) => {
  return (
    <>    
        <ModalHeader>
            شماره تماس کاربر
        </ModalHeader>
        <ModalBody>
            <div className="col-md-12">
                <Label>شماره موبایل کاربر:</Label>
                <Field name={"mobile"} className={'form-control rounded'}/>
                <p className={classes.error} >{errors.mobile && touched.mobile && errors.mobile}</p>
            </div>
        </ModalBody>
        <ModalFooter>
            <div className="w-100 d-flex justify-content-center">
                <button type="submit" className='btn btn-success'>تایید</button>         
            </div>     
        </ModalFooter>
    </>
  );
}

export default step1;