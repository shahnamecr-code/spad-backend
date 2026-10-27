import {Form, Formik} from "formik";
import React, { useState} from "react";
import {Modal} from "reactstrap";
import {adminPathApi} from "../../../../constants/defaultValues";
import axios from "axios";
import * as yup from 'yup';
import Step1 from './stepsForModal/step1'
import Step2 from './stepsForModal/step2'
import Step3 from './stepsForModal/step3'
import Step4 from './stepsForModal/step4'
const visitModal = ({showModal , setShowModal , type , data , index , adver_id , setData}) => {
    const [initialValues , setInitialValues] = useState({
        first_name: (data  && data[index]) ? (data  && data[index]).user.first_name : '',
        mobile: (data  && data[index]) ? (data  && data[index]).user.mobile : '',
        last_name: (data  && data[index]) ? (data  && data[index]).user.last_name : '',
        password: '',
        email: '',
        time: (data  && data[index]) ? (data[index].times.find((sts)=> sts.status == true  )?? {timestamp: '' , id: ''} ) : {},
        description: '',
        meeting_id: (data  && data[index]) ? (data  && data[index]).id : ''
    });
    const [id , setId] = useState(null);
    const [times , setTimes] = useState(null);
    const [description , setDescription] = useState(null);
    const [status , setStatus] = useState(null);
    const [step ,setStep] = useState(type == 'add' ? 1 : 4);
    const [meetingId ,setMeetingId] = useState(null);
    const [visitId ,setVisitId] = useState(null);
    let validate = yup.object().shape({
        mobile: yup.string().matches(/^((\+98|0)9\d{9})$/m,"شماره تلفن معتبر نمیباشد").required("این فیلد اجباری است"),
        email: yup.string().email("ایمیل معتبر نیست")
    });
    const onSubmit = (values) =>{
        if(step == 1){
            axios.post(`${adminPathApi}/customer/check-exist`, values)
            .then(res => res.data)
            .then(data => {
                if (data.status) {
                    values.last_name = data.customer.last_name;
                    setId(data.customer.id)
                    axios.get(`${adminPathApi}/advertisements/${adver_id}/meetings/user/${data.customer.id}`)
                    .then(res => res.data)
                    .then(data => {
                        if(data == ""){
                            setStep(2)
                        }else{
                            setStep(4)
                            setTimes(data.times)
                            setMeetingId(data.times[0].meeting_id)
                            setDescription(data.description)
                            setVisitId(data.id)
                        }
                    })
                    .catch(({response}) => {
                        alert(response)
                    })

                } else {
                    setStep(3)
                }
            })
        }else if((step == 4 || step == 2)){
            !id && axios.post(`${adminPathApi}/advertisements/${data[index].advertisement.id}/meetings/user/${data[index].user.id}` , values)
                .then(res =>{
                    setShowModal(false)
                    if(type == 'waiting'){
                        axios.get(`${adminPathApi}/my-meetings/waiting`)
                        .then(res =>{
                            setData(res.data)
                        })
                        .catch(({response}) => {
                            alert(response)
                        })
                      }
                })
                .catch(({response}) => {
                    alert(response)
                })
            id && axios.post(`${adminPathApi}/advertisements/${adver_id}/meetings/user/${id}` , values)
                .then(res =>{
                    setShowModal(false)
                    setId(null)
                })
                .catch(({response}) => {
                    setId(null)
                    alert(response)
                })
        }else if(step == 3){
            axios.post(`${adminPathApi}/customer/add-crm-customer`, values)
            .then(res => res.data)
            .then(data => {
                if (data.status) {
                    setStep(4)
                }
            })
            .catch(({response}) => {
                alert(response)
            })
        }
    }
  return (
    <>
        <Modal
            isOpen={showModal}
            toggle={()=> setShowModal(!showModal)}
            backdrop
            wrapClassName="modal-center"
        >
            <Formik validationSchema={validate} enableReinitialize initialValues={initialValues} onSubmit={onSubmit}>
                {({errors, touched, setFieldValue, setFieldTouched, values}) => (
                    <Form className="av-tooltip tooltip-label-right">
                        {step == 1 && <Step1 errors={errors} touched={touched}/>}
                        {step == 2 && <Step2 setFieldValue={setFieldValue} values={values} />}
                        {step == 3 && <Step3 errors={errors} touched={touched} setStep={setStep} values={values} />}
                        {step == 4 &&
                        <Step4
                            status={status}
                            setStatus={setStatus}
                            setFieldValue={setFieldValue}
                            description={description}
                            times={times}
                            values={values}
                            id={id}
                            meetingId={meetingId}
                            data={data && data}
                            type={type}
                            index={index }
                            visitId={visitId}
                            setShowModal={setShowModal}
                            setData={setData}
                        />}
                    </Form>
                )}
            </Formik>
        </Modal>
    </>
  );
}

export default visitModal;
