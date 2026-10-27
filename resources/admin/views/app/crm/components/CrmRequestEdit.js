import React, {useEffect, useState} from "react";
import {Button, Card ,Label, CardBody, Modal, ModalBody , ModalHeader, Row} from "reactstrap";
import {adminPathApi} from "../../../../constants/defaultValues";
import {Form, Formik} from "formik";
import CrmFilterItem from "../../../../components/CrmFilterItem";
import {Colxx} from "../../../../components/common/CustomBootstrap";
import {value} from "lodash/seq";
import { NotificationManager } from "../../../../components/common/react-notifications";
import AlertError from "../../../../components/AlertError";

const CrmRequestEdit=({isOpen,toggle,request,update_request})=>{
  const [crmFields, setCrmFields] = useState(false)
  const [init,setInit]=useState({})
  const [errors , setErrors] = useState();
  useEffect(()=>{
    axios
      .get(`${adminPathApi}/crm/edit/${request.id}`)
      .then(res=>res.data)
      .then(data=>{
        setCrmFields(data.crmFields)
        console.log(data)
        setInit(data.data)
      })
  },[request.id])

  const propertiesSave=(values)=>{
    axios
      .post(`${adminPathApi}/crm/edit/${request.id}`,{req:values})
      .then(res=>res.data)
      .then(data=>{
        update_request(data)
        toggle()
      }).catch(({res})=> setErrors(res.data.errors))
  }

  return (
    <Modal
      isOpen={isOpen}
      toggle={toggle}
      size={"xl"}
      backdrop
    >
      <ModalHeader>
        <AlertError errors={errors} />
      </ModalHeader>
      <ModalBody>
          {crmFields && crmFields.length ? (
            <Formik
              initialValues={init}
              onSubmit={propertiesSave}
              enableReinitialize
            >
              {({}) => (
                <Form>
                  <Row>
                    {crmFields.map((field,index) => (
                      <Colxx md={12} className="mb-3">
                      <Card>
                        <CardBody>
                          <Label className="border-primary border-bottom w-100 mb-4 h5">{field.label}</Label>
                          <Row>
                            {field.items.map((field)=> <CrmFilterItem key={index} field={field}/>)}
                          </Row>
                        </CardBody>
                      </Card>
                    </Colxx>
                    ))}
                    <Colxx sm={12}>
                      <Button type={"submit"}>ارسال درخواست</Button>
                    </Colxx>
                  </Row>
                </Form>
              )}</Formik>
          ) : ''}
      </ModalBody>
    </Modal>
  );
}

export default CrmRequestEdit;
