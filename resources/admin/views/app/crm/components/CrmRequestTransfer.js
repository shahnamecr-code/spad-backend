import React, {useEffect, useState} from "react";
import {Button, FormGroup, Label, Modal, ModalBody} from "reactstrap";
import {Formik,Form} from "formik";
import {adminPathApi} from "../../../../constants/defaultValues";
import axios from "axios";
import {AsyncPaginate} from "react-select-async-paginate";
import { NotificationManager } from "../../../../components/common/react-notifications";

const CrmRequestTransfer=({isOpen,toggle,request,update_request})=>{
  const [init,setInit]=useState({admin:request.admin??undefined})

  const transferRequest=(values)=>{
    axios
      .post(`${adminPathApi}/crm/transfer/${request.id}`,values)
      .then(res=>res.data)
      .then(data=>{
        update_request(data)
        toggle()
        NotificationManager.success('عملیات با موفقیت انجام شد.');
      })
  }

  const loadExperts = async (search, prevOptions,{page}) => {
    let source=`${adminPathApi}/getAllAdmins?page=${page}`
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

  return (
    <Modal
      isOpen={isOpen}
      toggle={toggle}
      backdrop
      wrapClassName="modal-right"    >
      <ModalBody>
        <Formik
          initialValues={init}
          onSubmit={transferRequest}
          enableReinitialize
        >
          {({values,setFieldValue}) => (
            <Form>
              <FormGroup>
                <Label>انتخاب کارشناس</Label>
                <AsyncPaginate
                  value={values.admin}
                  loadOptions={loadExperts}
                  onChange={(item) => setFieldValue('admin', item)}
                  additional={{
                    page: 1,
                  }}
                  getOptionLabel={option => `${option.display_name}(${option.manager?option.manager.display_name:'مستقل'})`}
                  getOptionValue={option => option.id}
                />
              </FormGroup>
              <Button type="submit">ثبت درخواست</Button>
            </Form>
            )}
        </Formik>
      </ModalBody>
    </Modal>
  );
}

export default CrmRequestTransfer;
