import {Field, Form, Formik} from "formik";
import React, {useEffect, useState} from "react";
import {Button, Card, CardBody, FormGroup, Input, InputGroup, InputGroupAddon, Label, Row , Modal, ModalHeader, ModalBody, ModalFooter} from "reactstrap";
import {Colxx, Separator} from "../../../../../components/common/CustomBootstrap";
import classes from '../../css/visitRequests.module.css'
import {Calendar} from "react-multi-date-picker";
import persian from "react-date-object/calendars/persian"
import persian_fa from "react-date-object/locales/persian_fa"
import TimePicker from "react-multi-date-picker/plugins/time_picker";
import {adminPathApi} from "../../../../../constants/defaultValues";
import axios from "axios";

const step2 = ({values , setFieldValue}) => {
    const calendarFunc = (date) =>{
        let zaman = date.valueOf() / 1000;
        setFieldValue('time.timestamp', zaman);
    }
  return (
    <>
        <div className={classes.header_modal}>
            <div className="">
                <Field disabled value={values.last_name} name={"last_name"} className={'form-control rounded border-0'}/>
            </div>
            <div className="">
                <Field disabled value={values.mobile} name={"mobile"} className={'form-control rounded border-0'}/>
            </div>
        </div>
        <ModalBody>
            <div>
                <p className="text-center w-100">ثبت زمان بازدید</p>
                <div className="mt-2 mb-2">
                    <Calendar 
                        onChange={date => calendarFunc(date)} 
                        selected={values.time.timestamp}
                        format="MM/DD/YYYY HH:mm:ss"
                        plugins={[
                            <TimePicker position="bottom" />
                        ]} 
                        calendar={persian}
                        locale={persian_fa}
                        name="time.timestamp"
                        calendarPosition="bottom-right"
                        className="m-auto"
                        minDate={new Date()}
                    />
                </div>
                <div className="">
                    <Label>توضیحات</Label>
                    <Field name="description" className={classes.textarea} as="textarea" ></Field>
                </div>  
            </div>
        </ModalBody>
        <ModalFooter>
            <div className="w-100 d-flex justify-content-center">
                <button type="submit" className='btn btn-success'>تایید زمان بازدید</button>         
            </div>     
        </ModalFooter>
    </>
  );
}

export default step2;