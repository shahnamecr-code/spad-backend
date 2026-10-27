import {Field} from "formik";
import React, {useEffect, useState} from "react";
import {Label, ModalBody, ModalFooter} from "reactstrap";
import classes from '../../css/visitRequests.module.css'
import {Calendar} from "react-multi-date-picker";
import persian from "react-date-object/calendars/persian"
import persian_fa from "react-date-object/locales/persian_fa"
import TimePicker from "react-multi-date-picker/plugins/time_picker";
import {adminPathApi} from "../../../../../constants/defaultValues";
import axios from "axios";

const step4 = ({values , times , description , setFieldValue , meetingId , data , setData , type , index , visitId , setShowModal}) => {
    const [add , setAdd] = useState(false);

    const timestamper = (time) =>{
        const timestamp = new Date(time); // This would be the timestamp you want to format
        let date = new Intl.DateTimeFormat('fa-IR',
        {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            weekday: "long"
        }).format(timestamp);
        return(date);
    }
    const cancel = () =>{
        type == 'add' ?
            axios.put(`${adminPathApi}/meeting/${visitId}/cancel`)
            .then(res =>
                setShowModal(false)
            )
            .catch(({response}) => {
                alert(response)
            })
        :
            axios.put(`${adminPathApi}/meeting/${data[index].id}/cancel`)
            .then(res =>{
                setShowModal(false)
                axios.get(`${adminPathApi}/my-meetings/waiting`)
                .then(res =>{
                    setData(res.data)
                })
                .catch(({response}) => {
                    alert(response)
                })
            })
            .catch(({response}) => {
                alert(response)
            })

    }
    const calendarFunc = (date) =>{
        setFieldValue('time', {} )
        setFieldValue('time.timestamp', date.valueOf() / 1000)
        setFieldValue('time.id', "" )
    }
    useEffect(()=> {
        meetingId !== null && setFieldValue('meeting_id', meetingId )
        times !== null &&
        times.find((date)=> date.status == true &&
            setFieldValue('time', {} ) && setFieldValue('time.timestamp', date.timestamp ) && setFieldValue('time.id', date.id )
         )
    },[meetingId])
    const selectInAdd = (date) =>{
        type == 'add' && setFieldValue('time' , {})
        setFieldValue('time.id', date.target.id )
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
                {console.log(values)}
                <p className="text-center w-100">زمان های بازدید ثبت شده</p>
                <div className="mt-2 mb-2">
                    {times !== null && !data && !index && times.map((date , index)=>

                        <label onClick={(date) => selectInAdd(date) } className="w-100" htmlFor={date.id}>
                            <div className={`d-flex justify-content-around border rounded mb-2 p-3 ${classes.select_div}`}>
                                <Field id={date.id} name="time.timestamp" value={`${date.timestamp}`} type="radio" checked={date.timestamp == values.time.timestamp } />
                                {timestamper(date.timestamp * 1000).split(" ").map((date) =>
                                    <span>
                                        {
                                            date.split("،").map((secondDate) => <span>{secondDate}</span>)
                                        }
                                    </span>
                                )}
                            </div>
                        </label>
                    )}
                    {data && data[index] && type == 'waiting' &&
                        data[index].times.map((date , index)=>
                        <label onClick={() => setFieldValue('time.id', date.id ) } className="w-100" htmlFor={date.id}>
                            <div className={`d-flex justify-content-around border rounded mb-2 p-3 ${classes.select_div}`}>
                                <Field id={date.id} name="time.timestamp" value={`${date.timestamp}`} type="radio" checked={date.timestamp == values.time.timestamp } />
                                {timestamper(date.timestamp * 1000).split(" ").map((date) =>
                                    <span>
                                        {
                                            date.split("،").map((secondDate) => <span>{secondDate}</span>)
                                        }
                                    </span>
                                )}
                            </div>
                        </label>
                    )}
                    {data && data[index] && type !== 'waiting' &&
                        data[index].times.map((date , index)=>
                        <label onClick={(date) => setFieldValue('time.id', date.id ) } className="w-100" htmlFor={date.id}>
                            <div className={`d-flex justify-content-around border rounded mb-2 p-3 ${type == 'waiting' ? classes.select_div : classes.select_div_blur }`}>
                                <Field disabled id={date.id} name="time.timestamp" value={`${date.timestamp}`} type="radio" checked={date.timestamp == values.time.timestamp } />
                                {timestamper(date.timestamp * 1000).split(" ").map((date) =>
                                    <span>
                                        {
                                            date.split("،").map((secondDate) => <span>{secondDate}</span>)
                                        }
                                    </span>
                                )}
                            </div>
                        </label>
                    )}
                    {!add && type == "add" && <div onClick={()=> setAdd(true)} className={`${classes.add_container} ${classes.bg_primaryy} mb-3`}>
                          افزودن زمان جدید +
                    </div>}
                    {add  && type == "add" &&
                        <div className="text-center mt-3 mb-3">
                            <Calendar
                                onChange={date => calendarFunc(date)}
                                selected={values.time.timestamp}
                                format="MM/DD/YYYY HH:mm:ss"
                                plugins={[
                                    <TimePicker position="bottom" />
                                ]}
                                calendar={persian}
                                locale={persian_fa}
                                calendarPosition="bottom-right"
                                className="m-auto"
                                minDate={new Date()}
                            />
                        </div>
                    }
                    <div className="">
                        <Label>توضیحات</Label>
                        {description == null && <Field disabled name="description" value={"توضیحاتی درج نشده"} className={classes.textarea} as="textarea" ></Field>}
                        {description !== null && <Field disabled name="description" value={description} className={classes.textarea} as="textarea" ></Field>}
                    </div>
                </div>
            </div>
        </ModalBody>
        <ModalFooter>
            <div className="w-100 d-flex justify-content-start">
                {(type == 'waiting' || type == 'add') && <button type="submit" className='btn btn-success mx-2'>تایید زمان بازدید</button>}
                {(type == 'waiting' || type == 'add') && <span onClick={()=> cancel()} className='btn btn-danger mx-2 cursor-pointer'>رد زمان بازدید</span>}
            </div>
        </ModalFooter>
    </>
  );
}

export default step4;
