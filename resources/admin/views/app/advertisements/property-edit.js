import React, {Suspense, useEffect, useState} from "react";
import {Button, ButtonGroup, Card, CardBody, CardSubtitle, FormGroup, Label, Row} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import AlertError from "../../../components/AlertError";
import {ErrorMessage, Field, FieldArray, Form, Formik} from "formik";
import {FormikReactSelect, FormikSwitch} from "../../../containers/form-validations/FormikFields";
import * as Yup from "yup";
import {
  advertisementPropertyTypes,
  advertisementSearchTypes,
  advertisementSearchLevels,
  advertisementSearchOperators,
  advertisementUnitTypes
} from "../../../constants/advertisementConstants";
import Nestable from 'react-nestable';
import 'react-nestable/dist/styles/index.css';
import Select from "react-select";
import axios from "axios";
import {adminPathApi} from "../../../constants/defaultValues";
import MediaButton from "../../../components/MediaLibrary";
import { NotificationManager } from "../../../components/common/react-notifications";
import MediaLibraryButton from "../../../components/mediaLibrary/MediaLibrary";

const propertySchema = Yup.object().shape({
  title: Yup.string()
    .min(2)
    .max(50)
    .required('وارد کردن عنوان الزامی است.'),
  order: Yup.number()
    .required('وارد کردن اولویت الزامی است.')
});

const NestedElement = ({item, collapseIcon, items, setItems, ...props}) => {
  const [properties, setProperties] = useState([]);
  const [saved, setSaved] = useState(true);
  const relations = [
    {label: "و", value: "AND"},
    {label: "یا", value: "OR"},
  ];
  const [init, setInit] = useState(item);

  useEffect(() => {
    axios
      .get(`${adminPathApi}/properties`)
      .then(res => res.data)
      .then(data => setProperties(data))
      .catch((e)=>{
        if (e.response.status==402){
          window.location.replace(e.response.data.url)
        }
      })
  }, [])

  const onSubmit = (values) => {
    setItems(editNested(items, item.id, values))
  }

  const removeNested = (items, id) => {
    let allItems = [];
    items.map((item, index) => {
      if (item.id != id) {
        let copyItem = {...item}
        if (item.children && removeNested(item.children, id).length > 0) {
          copyItem['children'] = removeNested(item.children, id)
        } else {
          delete copyItem['children']
        }
        allItems.push(copyItem)
      }
    })
    return allItems;
  }

  const editNested = (items, id, value) => {
    let allItems = [];
    items.map((item, index) => {
      if (item.id != id) {
        let copyItem = {...item}
        if (item.children) {
          copyItem['children'] = editNested(item.children, id, value)
        }
        allItems.push(copyItem)
      } else {
        allItems.push({...item, ...value})
      }
    })
    return allItems;
  }

  return (
    <Row>
      <Colxx md={4}>
        <FormGroup>
          <Label>ویژگی وابسته</Label>
          <Select
            options={properties}
            value={properties.find(litem => litem.id == init.property_id)}
            getOptionLabel={option => option.title}
            getOptionValue={option => option.id}
            onChange={(selected) => {
              if (selected) {
                setSaved(false)
                setInit(prev => {
                  return {...prev, property_id: selected.id};
                })
              }
            }}
          />
        </FormGroup>
      </Colxx>
      <Colxx md={4}>
        <FormGroup>
          <Label>نوع رابطه</Label>
          <Select
            options={relations}
            value={relations.find(litem => litem.value == init.relation)}
            onChange={(selected) => {
              if (selected) {
                setSaved(false)
                setInit(prev => {
                  return {...prev, relation: selected.value};
                })
              }
            }}
          />
        </FormGroup>
      </Colxx>
      <Colxx md={2}>
        <FormGroup>
          <Label>تنظیمات</Label>
          <ButtonGroup className="d-flex">
            <Button
              onClick={() => {
                setItems(editNested(items, item.id, init))
                setSaved(true)
              }}
              disabled={saved}
              size="xs">
              <span className="fa fa-check"/>
            </Button>
            <Button
              onClick={() => {
                setItems(removeNested(items, item.id))
              }}
              size="xs">
              <span className="fa fa-times"/>
            </Button>
          </ButtonGroup>
        </FormGroup>
      </Colxx>
    </Row>
  );
}

const PropertyEdit = ({match, edit = false, history}) => {
  const [serverError, setServerError] = useState({})
  const [initialValues, setInitialValues] = useState({
    title: '',
    order: '',
  });

  const [fileUrls, setFileUrls] = useState([]);


  useEffect(()=>{
    if (edit){
      axios
        .get(`${adminPathApi}/properties/edit/${match.params.id}`)
        .then(res=>res.data)
        .then(data=>{
          setInitialValues(data)
          setFileUrls(data.icon)
        })
    }
  },[])

  const [dependencies, setDependencies] = useState([
    {id: 0, text: 'Andy', relation: 'AND'},
    {
      id: 1, text: 'Harry', relation: 'OR',
      children: [
        {id: 2, text: 'David', relation: 'OR'}
      ]
    },
    {id: 3, text: 'Lisa', relation: 'AND'}
  ]);

  const renderItem = (props) => {
    return (
      <NestedElement setItems={setDependencies} items={dependencies} {...props}/>
    );
  };

  const onSubmit = (values) => {
    if (edit){
      axios
        .post(`${adminPathApi}/properties/edit/${match.params.id}`,values)
        .then(res=>res.data)
        .then(data=>{
          if (data.status) {
            NotificationManager.success(data.message, 'عملیات با موفقیت انجام شد.');
            history.push("/spad-admin/advertisements/properties/list");
          }
        })
    }else{
      axios
        .post(`${adminPathApi}/properties/add`,values)
        .then(res=>res.data)
        .then(data=>{
          if (data.status) {
            NotificationManager.success(data.message, 'عملیات با موفقیت انجام شد.');
            history.push("/spad-admin/advertisements/properties/list");
          }
        })
    }
  }

  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={"menu.wrappers"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx xss={12}>
        <Card className="mb-4">
          <CardBody>
            <AlertError errors={serverError}/>
            <Formik enableReinitialize initialValues={initialValues} onSubmit={onSubmit}
                    validationSchema={propertySchema}>
              {({errors, touched, setFieldValue, setFieldTouched, values}) => (
                <Form className="av-tooltip tooltip-label-right">
                  <FormGroup row>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>عنوان</Label>
                        <Field name={"title"} className={'form-control'}/>
                        {errors.title && touched.title && (
                          <div className="invalid-feedback d-block">
                            {errors.title}
                          </div>
                        )}
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>شناسه</Label>
                        <Field name={"slug"} className={'form-control'}/>
                        {errors.slug && touched.slug && (
                          <div className="invalid-feedback d-block">
                            {errors.slug}
                          </div>
                        )}
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup className={'error-l-75'}>
                        <Label>ترتیب</Label>
                        <Field type="number" name={"order"} className={'form-control'}/>
                        {errors.order && touched.order && (
                          <div className="invalid-feedback d-block">
                            {errors.order}
                          </div>
                        )}
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>نوع ویژگی</Label>
                        <FormikReactSelect
                          value={values.type}
                          onChange={setFieldValue}
                          onBlur={setFieldTouched}
                          options={advertisementPropertyTypes}
                          name={'type'}/>
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FieldArray name="enum">
                        {({insert, remove, push}) => (
                          <>
                            <CardSubtitle>مقادیر
                              <Button
                                color="primary"
                                outline
                                size={"sm"}
                                className={"mx-2"}
                                onClick={() => push('')}
                              >
                                افزودن
                              </Button>
                            </CardSubtitle>
                            {values.enum && values.enum.length > 0 &&
                              values.enum.map((item, index) => (
                                <Row key={index} className={"mb-1"}>
                                  <Colxx>
                                    <Field
                                      className="form-control"
                                      name={`enum.${index}`}
                                      placeholder="عنوان ویژگی"
                                      type="text"
                                    />
                                    <ErrorMessage
                                      name={`enum.${index}`}
                                      component="div"
                                      className="field-error"
                                    />
                                  </Colxx>
                                  <Colxx>
                                    <Button
                                      color="danger"
                                      outline
                                      size={"sm"}
                                      onClick={() => remove(index)}
                                    >
                                      <span className={"simple-icon-trash"}/>
                                    </Button>
                                  </Colxx>
                                </Row>
                              ))}
                          </>
                        )}
                      </FieldArray>
                    </Colxx>
                    <Colxx md={6}>
                      <FieldArray name="enumNames">
                        {({insert, remove, push}) => (
                          <>
                            <CardSubtitle>برچسب مقادیر
                              <Button
                                color="primary"
                                outline
                                size={"sm"}
                                className={"mx-2"}
                                onClick={() => push('')}
                              >
                                افزودن
                              </Button>
                            </CardSubtitle>
                            {values.enumNames && values.enumNames.length > 0 &&
                              values.enumNames.map((item, index) => (
                                <Row key={index} className={"mb-1"}>
                                  <Colxx>
                                    <Field
                                      className="form-control"
                                      name={`enumNames.${index}`}
                                      placeholder="عنوان ویژگی"
                                      type="text"
                                    />
                                    <ErrorMessage
                                      name={`enumNames.${index}`}
                                      component="div"
                                      className="field-error"
                                    />
                                  </Colxx>
                                  <Colxx>
                                    <Button
                                      color="danger"
                                      outline
                                      size={"sm"}
                                      onClick={() => remove(index)}
                                    >
                                      <span className={"simple-icon-trash"}/>
                                    </Button>
                                  </Colxx>
                                </Row>
                              ))}
                          </>
                        )}
                      </FieldArray>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>جداکننده</Label>
                        <FormikSwitch
                          name={'separator'}
                          className="custom-switch custom-switch-primary"
                          value={values.separator}
                          onChange={setFieldValue}
                          onBlur={setFieldTouched}
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>واحد اندازه گیری</Label>
                        <FormikReactSelect
                          value={values.unit}
                          onChange={setFieldValue}
                          onBlur={setFieldTouched}
                          options={advertisementUnitTypes}
                          name={'unit'}/>
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>آیکون</Label>
                        <MediaLibraryButton
                          multiple={false}
                          onSubmit={(values) => setFieldValue('icon', values)}
                          onChange={(values) => setFieldValue('icon', values)}
                          defaultFileIdz={values['icon'] || []}
                          defaultFileUrls={fileUrls ? fileUrls : []}
                          setDefaultFileUrls={setFileUrls}
                        />
{/*                        <MediaButton onSubmit={(value) => setFieldValue('icon', value.files)}
                                     default_value={values.icon ? values.icon : undefined} name='icon'
                                     setFieldValue={setFieldValue}/>*/}
                      </FormGroup>
                    </Colxx>
{/*                    <Colxx md={6}>
                      <FormGroup>
                        <Label>استفاده در درخواست ملک</Label>
                        <FormikSwitch
                          name={'show_in_crm'}
                          className="custom-switch custom-switch-primary"
                          value={values.show_in_crm}
                          onChange={setFieldValue}
                          onBlur={setFieldTouched}
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>امکان جستجو</Label>
                        <FormikSwitch
                          name={'searchable'}
                          className="custom-switch custom-switch-primary"
                          value={values.searchable}
                          onChange={(name, value) => {
                            setFieldValue(name, value)
                            if (!value) {
                              setFieldValue('search_type', undefined)
                              setFieldValue('search_level', undefined)
                            }
                          }}
                          onBlur={setFieldTouched}
                        />
                      </FormGroup>
                    </Colxx>
                    {values.searchable ? (
                      <>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>جستجوی بازه ای</Label>
                            <FormikSwitch
                              name={'range_search'}
                              className="custom-switch custom-switch-primary"
                              value={values.range_search}
                              onChange={setFieldValue}
                              onBlur={setFieldTouched}
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>نوع جستجو</Label>
                            <FormikReactSelect
                              value={values.search_type}
                              onChange={setFieldValue}
                              onBlur={setFieldTouched}
                              options={advertisementSearchTypes}
                              name={'search_type'}/>
                          </FormGroup>
                        </Colxx>
                        <Colxx md={6}>
                          <FieldArray name="search_enum">
                            {({insert, remove, push}) => (
                              <>
                                <CardSubtitle>مقادیر
                                  <Button
                                    color="primary"
                                    outline
                                    size={"sm"}
                                    className={"mx-2"}
                                    onClick={() => push('')}
                                  >
                                    افزودن
                                  </Button>
                                </CardSubtitle>
                                {values.search_enum && values.search_enum.length > 0 &&
                                  values.search_enum.map((item, index) => (
                                    <Row key={index} className={"mb-1"}>
                                      <Colxx>
                                        <Field
                                          className="form-control"
                                          name={`search_enum.${index}`}
                                          placeholder="عنوان ویژگی"
                                          type="text"
                                        />
                                        <ErrorMessage
                                          name={`search_enum.${index}`}
                                          component="div"
                                          className="field-error"
                                        />
                                      </Colxx>
                                      <Colxx>
                                        <Button
                                          color="danger"
                                          outline
                                          size={"sm"}
                                          onClick={() => remove(index)}
                                        >
                                          <span className={"simple-icon-trash"}/>
                                        </Button>
                                      </Colxx>
                                    </Row>
                                  ))}
                              </>
                            )}
                          </FieldArray>
                        </Colxx>
                        <Colxx md={6}>
                          <FieldArray name="search_enumNames">
                            {({insert, remove, push}) => (
                              <>
                                <CardSubtitle>برچسب مقادیر
                                  <Button
                                    color="primary"
                                    outline
                                    size={"sm"}
                                    className={"mx-2"}
                                    onClick={() => push('')}
                                  >
                                    افزودن
                                  </Button>
                                </CardSubtitle>
                                {values.search_enumNames && values.search_enumNames.length > 0 &&
                                  values.search_enumNames.map((item, index) => (
                                    <Row key={index} className={"mb-1"}>
                                      <Colxx>
                                        <Field
                                          className="form-control"
                                          name={`search_enumNames.${index}`}
                                          placeholder="عنوان ویژگی"
                                          type="text"
                                        />
                                        <ErrorMessage
                                          name={`search_enumNames.${index}`}
                                          component="div"
                                          className="field-error"
                                        />
                                      </Colxx>
                                      <Colxx>
                                        <Button
                                          color="danger"
                                          outline
                                          size={"sm"}
                                          onClick={() => remove(index)}
                                        >
                                          <span className={"simple-icon-trash"}/>
                                        </Button>
                                      </Colxx>
                                    </Row>
                                  ))}
                              </>
                            )}
                          </FieldArray>
                        </Colxx>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>عملگر جستجو</Label>
                            <FormikReactSelect
                              value={values.search_operator}
                              onChange={setFieldValue}
                              onBlur={setFieldTouched}
                              options={advertisementSearchOperators}
                              name={'search_operator'}/>
                          </FormGroup>
                        </Colxx>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>دقت جستجو</Label>
                            <FormikReactSelect
                              value={values.search_level}
                              onChange={setFieldValue}
                              onBlur={setFieldTouched}
                              options={advertisementSearchLevels}
                              name={'search_level'}/>
                          </FormGroup>
                        </Colxx>
                      </>
                    ) : ''}*/}
{/*
                    <Colxx md={12}>
                      <Button
                        className="mb-4"
                        size="xs"
                      onClick={()=>{
                        setDependencies(prev=>{
                          return [...prev,{}]
                        })
                      }}>
                        <span className="mr-2">افرودن وابستگی</span>
                        <span className="fa fa-plus"/>
                      </Button>
                      <Nestable
                        items={dependencies}
                        renderItem={renderItem}
                        onChange={({items}) => {
                          setDependencies(items)
                        }}
                      />

                      <FieldArray name="dependencies">
                        {({insert, remove, push}) => (
                          <>
                            <CardSubtitle>وابستگی ها
                              <Button
                                color="primary"
                                outline
                                size={"sm"}
                                className={"mx-2"}
                                onClick={() => push({})}
                              >
                                افزودن
                              </Button>
                            </CardSubtitle>
                            {values.dependencies && values.dependencies.length > 0 &&
                              values.dependencies.map((dependency, index) => (
                                <Row key={index} className={"mb-1"}>
                                  <Colxx md={4}>
                                    <FormGroup>
                                      <Label>فیلد مورد نظر را انتخاب کنید</Label>
                                      <Field
                                        className="form-control"
                                        name={`dependencies.${index}.property_dependent_id`}
                                        placeholder="عنوان ویژگی"
                                        type="text"
                                      />
                                    </FormGroup>
                                  </Colxx>
                                  <Colxx md={4}>
                                    <FormGroup>
                                      <Label>مقادیر متناظر را انتخاب کنید</Label>
                                      <Field
                                        className="form-control"
                                        name={`dependencies.${index}.property_dependent_value`}
                                        placeholder="عنوان ویژگی"
                                        type="text"
                                      />
                                    </FormGroup>
                                  </Colxx>
                                  <Colxx>
                                    <Button
                                      color="danger"
                                      outline
                                      size={"sm"}
                                      onClick={() => remove(index)}
                                    >
                                      <span className={"simple-icon-trash"}/>
                                    </Button>
                                  </Colxx>
                                </Row>
                              ))}
                          </>
                        )}
                      </FieldArray>

                    </Colxx>
*/}
                  </FormGroup>
                  <Button color="primary" type="submit" className="float-left">
                    ثبت
                  </Button>
                </Form>
              )}
            </Formik>
          </CardBody>
        </Card>
      </Colxx>
    </Suspense>
  );
}

export default PropertyEdit;
