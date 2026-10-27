/* eslint-disable react/no-array-index-key */
import React, {createRef, useState} from 'react';
import {Step, Steps, Wizard} from "react-albus";
import {Field, Form, Formik} from "formik";
import {Button, Card, CardBody, FormGroup, Label, Modal, ModalBody, ModalFooter, ModalHeader, Table} from "reactstrap";
import ProductAttribute from "../forms/product-attribute";
import {injectIntl} from "react-intl";
import TopNavigation from "../../../../components/wizard/TopNavigation";
import ProductCategory from "../forms/product-category";
import {adminPathApi} from "../../../../constants/defaultValues";
import axios from "axios";
import DefaultAttribute from "../forms/default-attribute";
import ProductVariables from "../forms/product-variables";
import ProductFeature from "../forms/product_feature";
import {NotificationManager} from "../../../../components/common/react-notifications";
import {FormikSwitch} from "../../../../containers/form-validations/FormikFields";
import Select from "react-select";

const ProductUnitForm=({template,show_modal,setShowModal,unit_to_edit,setUnits})=>{
  const [init,setInit]=React.useState({name:'',value:''})
  const [loading,setLoading]=React.useState(true);

  React.useEffect(()=>{
    if(unit_to_edit){
      axios
        .get(`${adminPathApi}/productUnit/${unit_to_edit}`)
        .then(res=>res.data)
        .then(data=>setInit(data))
    }else{
      setInit({name:'',value: ''});
      setLoading(false);
    }
  },[unit_to_edit]);

  const save_unit=(values)=>{
    if(unit_to_edit){
      axios
        .post(`${adminPathApi}/template/${template}/edit_unit/${unit_to_edit}`,values)
        .then(res=>res.data)
        .then(data=>{
          if(data.status){
            setUnits(data.data)
          }else {
            NotificationManager.error(data.message,null,1000,null,null,'filled');
          }
          setShowModal(false)
        })
    }else {
      axios
        .post(`${adminPathApi}/template/${template}/add_unit`,values)
        .then(res=>res.data)
        .then(data=>{
          if(data.status){
            setUnits(data.data)
          }else {
            NotificationManager.error(data.message,null,1000,null,null,'filled');
          }
          setShowModal(false)
        })
    }
  };

  if(loading){
    return <div className="loading"/>
  }

  return (
    <Modal isOpen={show_modal} toggle={() => {
      setShowModal(!show_modal)
    }} backdrop wrapClassName="modal-right">
      <Formik
        initialValues={init}
        onSubmit={save_unit}
        enableReinitialize
      >
        {({errors, touched, setFieldValue, setFieldTouched, values}) => (
          <Form className="av-tooltip tooltip-label-right">
            <ModalHeader>
              واحد اندازه گیری محصول
            </ModalHeader>
            <ModalBody>
              <FormGroup>
                <label>عنوان</label>
                <Field className="form-control" name="name"/>
              </FormGroup>
              <FormGroup>
                <label>تعداد</label>
                <Field className="form-control" name="value"/>
              </FormGroup>
            </ModalBody>
            <ModalFooter>
              <Button color="danger" onClick={()=>setShowModal(false)}>
                لغو
              </Button>
              <Button color="primary" type="submit">
                ذخیره
              </Button>
            </ModalFooter>
          </Form>
          )}
      </Formik>
    </Modal>
  );
}

const ProductUnit=({id,wizardHelper})=>{
  const [units,setUnits]=React.useState([]);
  const [show_modal,setShowModal]=React.useState(false);
  const [unit_to_edit,setUnitToEdit]=React.useState(false);

  React.useEffect(async ()=>{
    axios
      .get(`${adminPathApi}/template/${id}/units`)
      .then(res=>res.data)
      .then(data=>setUnits(data))
  },[])

  const setDefaultUnit=(value)=>{
    axios
      .post(`${adminPathApi}/template/${id}/setDefaultUnit/${value}`)
      .then(res=>res.data)
      .then(data=>{
        setUnits(data)
      })
  }

  return (
    <>
      <h1>
        واحد های اندازه گیری محصول
        <Button size="xs" color="primary" onClick={()=>{setUnitToEdit(false);setShowModal(true)}}>
          افزودن
        </Button>
      </h1>
      <Table striped className="position-relative table-info">
        <thead>
          <th>عنوان</th>
          <th>تعداد</th>
          <th/>
        </thead>
        <tbody>
        {units.map(unit=>(
            <tr>
              <td>{unit.name}</td>
              <td>{unit.value}</td>
              <td>
                <Button
                  color="info"
                  outline
                  size="xs"
                  onClick={()=> {
                    setUnitToEdit(unit.id);
                    setShowModal(true);
                  }}
                >
                  <span className="simple-icon-pencil"></span>
                </Button>
              </td>
            </tr>
        ))}
        </tbody>
      </Table>
      {units && units.length>0?(
        <FormGroup>
          <Label>واحد پیش فرض</Label>
          <Select options={units} getOptionLabel={option=>option.name} getOptionValue={option=>option.value} value={units.filter(item=>item.default)[0]} onChange={(selected)=>setDefaultUnit(selected.id)}/>
        </FormGroup>
      ):''}
      <ProductUnitForm setShowModal={setShowModal} show_modal={show_modal} unit_to_edit={unit_to_edit} template={id} setUnits={setUnits}/>
    </>
  );

}

const ProductEdit = ({match, edit = false, intl}) => {
  const forms = [createRef(null), createRef(null)];
  const [bottomNavHidden, setBottomNavHidden] = useState(false);
  const [init, setInit] = React.useState([{}, {}]);
  const [validationErrors, setValidationErrors] = React.useState([{}, {}]);
  const id = edit ? match.params.id : false;

  const [category_options,setCategoryOptions]=React.useState([])
  const [mainCat, setMainCat] = useState(null);

  React.useEffect(async ()=>{
    await axios
      .get(
        `${adminPathApi}/category/get_categories`,
      )
      .then((res) => {
        return res.data;
      })
      .then((data) => {
        setCategoryOptions(data)
      });

  },[])

  const topNavClick = (stepItem, push,step) => {
    if(stepItem.isDone){
      push(stepItem.id);
    }
  };

  const {messages} = intl;

  return (
    <Card>
      <CardBody className={"wizard wizard-default"}>
        <Wizard>
          {(wizardHelper) => (
            <>
              <TopNavigation className="justify-content-center" topNavClick={topNavClick}/>
              <Steps>
                <Step id="general" name={messages['wizard.product.general']}>
                  <div className="wizard-basic-step">
                    <DefaultAttribute id={id ? id : undefined} list={category_options} setMainCat={setMainCat} mainCat={mainCat} wizardHelper={wizardHelper}/>
                  </div>
                </Step>
                <Step id="feature" name={messages['wizard.product.feature']}>
                  <div className="wizard-basic-step">
                    <ProductFeature id={id ? id : undefined}  wizardHelper={wizardHelper}/>
                  </div>
                </Step>
                <Step id="attribute" name={messages['wizard.product.attribute']}>
                  <div className="wizard-basic-step">
                    <ProductAttribute id={id ? id : undefined} mainCat={mainCat} wizardHelper={wizardHelper}/>
                  </div>
                </Step>
                <Step id="variable" name={messages['wizard.product.variable']}>
                  <div className="wizard-basic-step">
                    <ProductVariables id={id ? id : undefined} wizardHelper={wizardHelper}/>
                  </div>
                </Step>
                <Step id="category" name={messages['wizard.product.category']}>
                  <div className="wizard-basic-step">
                    <ProductCategory id={id ? id : undefined} wizardHelper={wizardHelper}/>
                  </div>
                </Step>
                <Step id="unit" name={messages['wizard.product.unit']}>
                  <div className="wizard-basic-step">
                    <ProductUnit id={id ? id : undefined} wizardHelper={wizardHelper}/>
                  </div>
                </Step>
              </Steps>
            </>
          )}
          {/*<BottomNavigation
          onClickNext={onClickNext}
          onClickPrev={onClickPrev}
          className={`justify-content-center ${
            bottomNavHidden && 'invisible'
          }`}
          prevLabel={messages['wizard.prev']}
          nextLabel={messages['wizard.next']}
        />*/}
        </Wizard>
      </CardBody>
    </Card>
  );
};

export default injectIntl(ProductEdit);
