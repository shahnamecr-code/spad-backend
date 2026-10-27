import React, {useEffect, useState} from "react";
import {Button, FormGroup, Label, Modal, ModalBody, ModalHeader} from "reactstrap";
import {adminPathApi} from "../../../../constants/defaultValues";
import {Formik, Form, Field, FieldArray, ErrorMessage} from "formik";
import {FormikReactSelect} from "../../../../containers/form-validations/FormikFields";
import axios from "axios";
import {AsyncPaginate} from "react-select-async-paginate";
import {NotificationManager} from "../../../../components/common/react-notifications";

const TransferAdvertisement = ({toggle, isOpen, ads}) => {
  const [loading, setLoading] = useState(true)
  const [init, setInit] = useState({})

  useEffect(() => {
    if (ads) {
      axios
        .get(`${adminPathApi}/advertisements/transform/${ads.id}`)
        .then(res => res.data)
        .then(data => {
          setInit({experts:data.suggested ?? []})
          setLoading(false)
        })
    }
  }, [ads.id])

  const loadExperts = async (search, prevOptions,{page}) => {
    let source=`${adminPathApi}/advertisements/allManagerExperts?page=${page}`
    if (search){
      source+=`&search=${search}`
    }
    let result;

    await axios
      .get(source)
      .then(res=>res.data)
      .then(data=>{
        result=data
      })

    return {
      options: result.data,
      hasMore:result.hasMore,
      additional:{
        page: parseInt(result.page)+1,
      }
    };
  };

  const onSubmit = (values) => {
    axios
      .post(`${adminPathApi}/advertisements/transform/${ads.id}`,values)
      .then(res => res.data)
      .then(data => {
        if (data.status){
          NotificationManager.success(data.message, 'موفقیت');
          toggle()
        }
      })
  }

  return (
    <Modal
      isOpen={isOpen}
      toggle={toggle}
      backdrop
      wrapClassName="modal-right">
      <ModalHeader>انتقال آگهی {ads.id}</ModalHeader>
      <ModalBody>
        {loading ? (<div className="loading"/>) : (
          <Formik initialValues={init} onSubmit={onSubmit} enableReinitialize>
            {({errors, touched, setFieldValue, setFieldTouched, values}) => (
              <Form className="av-tooltip tooltip-label-right">
{/*
                <FieldArray name="experts">
                  {({remove, push}) => (
                    <>
                      <Button
                        color="primary"
                        outline
                        size={"sm"}
                        className={"mx-2 mb-4 btn-block"}
                        onClick={() => push('')}
                      >
                        افزودن کارشناس
                      </Button>
                      {values.experts && values.experts.length >= 0 &&
                        values.experts.map((expert, index) => (
                          <div className="d-flex align-items-center">
                            <FormGroup className={"flex-grow-1 mr-2"}>
                              <Label>انتخاب کنید</Label>
                              <FormikReactSelect
                                options={experts}
                                name={`experts.${index}`}
                                onChange={setFieldValue}
                                onBlur={setFieldTouched}
                                getOptionLabel={(option)=>option.display_name}
                                getOptionValue={(option)=>option.id}
                              />
                              <ErrorMessage
                                name={`experts.${index}`}
                                component="div"
                                className="field-error"
                              />
                            </FormGroup>
                            <Button
                              color="danger"
                              outline
                              size={"xs"}
                              onClick={() => remove(index)}
                            >
                              <span className={"simple-icon-trash"}/>
                            </Button>
                          </div>
                        ))
                      }
                    </>
                  )}
                </FieldArray>
*/}
                <FormGroup>
                  <Label>انتخاب کارشناس</Label>
                  <AsyncPaginate
                    value={values.experts}
                    loadOptions={loadExperts}
                    onChange={(item) => setFieldValue('experts', item)}
                    additional={{
                      page: 1,
                    }}
                    isMulti
                    getOptionLabel={option => `${option.display_name}(${option.manager?option.manager.display_name:'مستقل'})`}
                    getOptionValue={option => option.id}
                  />
                </FormGroup>
                <Button type="submit">انتقال آگهی</Button>
              </Form>
            )}
          </Formik>
        )}
      </ModalBody>
    </Modal>
  );
}
export default TransferAdvertisement;
