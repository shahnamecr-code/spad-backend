import React, {Suspense, useState} from 'react';
import {Modal, ModalHeader, ModalBody, ModalFooter, Row , Card,CardBody, CardTitle, FormGroup, Label,Input, CustomInput,CardSubtitle, CardText ,Button } from 'reactstrap';
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import {Field, Form, Formik} from "formik";
import {FormikSwitch} from "../../../containers/form-validations/FormikFields";
import SearchSelect from "../../../components/SearchSelect";
import axios from "axios";
import {adminPathApi} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";
import AlertError from "../../../components/AlertError";
import MediaButton from "../../../components/MediaLibrary";


const BlogCategory = ({status, toggle,category_to_edit,setCategoryToEdit,setData}) =>{
  const [initialValues, setInitialValues] = useState({
    name : "",
    slug : "",
    parent : undefined,
    status : false,
    order:undefined,
    image:undefined,
  })
  const [validationErrors,setValidationErrors]=React.useState(false);
  const [loading,setLoading]=React.useState(true);

  const getCategoriesList = (setloadOptions, search = '') => {
    axios
      .get(
        `${adminPathApi}/blog-category?page=1&per_page=10&search=${search}`,
      )
      .then((res) => {
        return res.data;
      })
      .then((data) => {
        //setShowAttributeList(false)
        setloadOptions(data.data);
      });
  }

  React.useEffect(()=>{
    if(category_to_edit){
      setLoading(true)
      axios
        .get(`${adminPathApi}/blog-category/${category_to_edit}`)
        .then(res=>res.data)
        .then(data=>{
          setInitialValues(data)
        })
        .finally(()=>setLoading(false))
    }else{
      setLoading(false);
    }
  },[category_to_edit]);

  const save=(values)=>{
    if(category_to_edit){
      axios
        .post(`${adminPathApi}/blog-category/${category_to_edit}/edit`,{...values,parent:values.parent?(values.parent.id):null})
        .then(res=>res.data)
        .then(data=>{
          if(data.status){
            NotificationManager.success(data.message,null,1000,null,null,'filled');
          }else{
            NotificationManager.error(data.message,null,1000,null,null,'filled');
          }
          setValidationErrors(false);
          setCategoryToEdit(false);
          setData(prev=>{
            let items=[...prev.data];
            let cnt=items.findIndex(item=>item.id==category_to_edit);
            items[cnt]=data.item;
            return {...prev,data:items};
          })
          if(status){
            toggle()
          }
        })
        .catch(error=>{
          if(error.response.status == 422){
            setValidationErrors(error.response.data.errors)
          }else {
            NotificationManager.error('خطایی در ارسال اطلاعات رخ داده است',null,1000,null,null,'filled');
          }
        })
    }else{
      axios
        .post(`${adminPathApi}/blog-category/create`,{...values,parent:values.parent?(values.parent.id):null})
        .then(res=>res.data)
        .then(data=>{
          if(data.status){
            NotificationManager.success(data.message,null,1000,null,null,'filled');
          }else{
            NotificationManager.error(data.message,null,1000,null,null,'filled');
          }
          setValidationErrors(false);
          setData(prev=>{
            let output=[...prev.data];
            output.push(data.item)
            return {...prev,data:output}
          })
          if(status){
            toggle()
          }
        })
        .catch(error=>{
          if(error.response.status == 422){
            setValidationErrors(error.response.data.errors)
          }else {
            NotificationManager.error('خطایی در ارسال اطلاعات رخ داده است',null,1000,null,null,'filled');
          }
        })
    }

  }

  if(loading){
    return <div className="loading"/>
  }

  return(
    <>
      <Row>
        <Colxx md = '12'>
          <Modal isOpen = {status} toggle = {toggle}>
            <ModalHeader toggle = {toggle}>
              افزودن دسته بندی
            </ModalHeader>

            <ModalBody>
              <Formik enableReinitialize initialValues = {initialValues} onSubmit = {save}>
                {
                  ({errors, touched, setFieldValue, setFieldTouched, values})=>(
                    <Form>
                      <Row>
                        <Colxx md = "12">
                          <AlertError errors={validationErrors}/>
                        </Colxx>
                      </Row>
                      <Row>
                        <Colxx md = "12">
                          <FormGroup className = "mt-3">
                            <Label>نام</Label>
                            <Field
                              name = "name"
                              className = "form-control"
                            />
                          </FormGroup>
                        </Colxx>
                      </Row>

                      <Row>
                        <Colxx md = "12">
                          <FormGroup className = "mt-3">
                            <Label>پیوند یکتا</Label>
                            <Field
                              name = "slug"
                              className = "form-control"
                            />
                          </FormGroup>
                        </Colxx>
                      </Row>

                      <Row>
                        <Colxx md = "12">
                          <FormGroup className = "mt-3">
                            <Label>دسته والد</Label>
                            <SearchSelect request={getCategoriesList} value={values.parent} name='parent'
                                          onChange={setFieldValue} getOptionLabel={option => option.name}
                                          getOptionValue={option => option.id}/>
                          </FormGroup>
                        </Colxx>
                      </Row>

                      {/* <Row>
                        <Colxx md = "12">
                          <FormGroup className = "mt-3">
                            <Label>ترتیب</Label>
                            <Field
                              name = "order"
                              className = "form-control"
                            />
                          </FormGroup>
                        </Colxx>
                      </Row>

                      <Row>
                        <Colxx md="12">
                          <FormGroup key={name}>
                            <Label>تصویر</Label>
                            <MediaButton onSubmit={(value)=>setFieldValue('image',value.files)} default_value={values['image']} name='image' setFieldValue={setFieldValue}/>
                          </FormGroup>
                        </Colxx>
                      </Row> */}

                      <Row>
                        <Colxx md = "12">
                          <FormGroup className = "mt-3">
                            <Label>توضیحات</Label>
                            <Field
                              name = "description"
                              className = "form-control"
                              as="textarea"
                            />
                          </FormGroup>
                        </Colxx>
                      </Row>

                      <Row>
                        <Colxx md = "12">
                          <FormGroup className = "mt-3">
                            <Label>وضعیت</Label>
                            <FormikSwitch
                              name = "status"
                              onChange = {setFieldValue}
                              onBlur = {setFieldTouched}
                              className={'custom-switch custom-switch-primary ml-2'}
                              value={values.status}
                            />
                          </FormGroup>
                        </Colxx>
                      </Row>

                      <Row>
                        <Colxx md = "12">
                          <Button outline type = "submit" className = "float-right">ثبت</Button>
                        </Colxx>
                      </Row>

                    </Form>
                  )
                }
              </Formik>
            </ModalBody>

          </Modal>
        </Colxx>
      </Row>
    </>
  )
}

export default BlogCategory;
