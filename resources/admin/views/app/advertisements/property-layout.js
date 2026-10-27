import React, {Suspense, useEffect, useState} from "react";
import {Button, ButtonGroup, Card, CardBody, CardFooter, CardSubtitle, FormGroup, Label, Row} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import {Link} from "react-router-dom";
import {adminPathApi, adminRoot} from "../../../constants/defaultValues";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import {Formik, Form, FieldArray, Field, ErrorMessage} from "formik";
import axios from "axios";
import {FormikReactSelect, FormikSwitch} from "../../../containers/form-validations/FormikFields";

import {advertisementDependencyTypes} from "../../../constants/advertisementConstants";

/*import Nestable from 'react-nestable';
import 'react-nestable/dist/styles/index.css';*/

/*
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
*/

const PropertyRelation = ({name, values,deleteItem, properties, onChange, onBlur}) => {
  return (
    <FieldArray name={name}>
      {({insert, remove, push}) => (
        <>
          <CardSubtitle>
            <Button
              color="danger"
              outline
              size={"sm"}
              className={"mx-2"}
              onClick={() => deleteItem()}
            >
              حذف کردن فیلد
            </Button>
            <Button
              color="primary"
              outline
              size={"sm"}
              className={"mx-2"}
              onClick={() => push({})}
            >
              افزودن فیلد مرتبط
            </Button>
          </CardSubtitle>

          {values && values.length > 0 &&
            values.map((item, index) => (
              <Row key={index} className={"mb-1"}>
                <Colxx md="6">
                  <FormGroup>
                    <Label>نوع شرط</Label>
                    <FormikReactSelect
                      options={advertisementDependencyTypes}
                      name={`${name}.${index}.dependency_type`}
                      value={values[index].dependency_type}
                      onChange={onChange}
                      onBlur={onBlur}
                    />
                    <ErrorMessage
                      name={`${name}.${index}.dependency_type`}
                      component="div"
                      className="field-error"
                    />
                  </FormGroup>
                </Colxx>
                <Colxx md="6">
                  <FormGroup>
                    <Label>مقدار متناظر</Label>
                    <Field
                      name={`${name}.${index}.dependency_limit`}
                      value={values[index].dependency_limit}
                      className="form-control"/>
                  </FormGroup>
                </Colxx>
                <Colxx md="12">
                  <Row className="px-4">
                    <Colxx>
                      <FieldArray name={`${name}.${index}.items`}>
                        {(innerProps) => (
                          <>
                          <CardSubtitle>
                            <Button
                              color="danger"
                              outline
                              size={"sm"}
                              className={"mx-2"}
                              onClick={() => {
                                innerProps.push({})
                              }}
                            >
                              حذف فیلد
                            </Button>
                            <Button
                              color="primary"
                              outline
                              size={"sm"}
                              className={"mx-2"}
                              onClick={() => {
                                innerProps.push({})
                              }}
                            >
                              افزودن فیلد مرتبط
                            </Button>
                          </CardSubtitle>
                            {values[index]['items'] && values[index]['items'].length>0 &&
                              values[index]['items'].map((item1,number)=>(
                                <>
                                  <Colxx md="6">
                                    <FormGroup>
                                      <Label>انتخاب ویژگی</Label>
                                      <FormikReactSelect
                                        options={properties}
                                        name={`${name}.${index}.items.${number}.property_id`}
                                        value={values[index]['items'][number].property_id}
                                        onChange={onChange}
                                        onBlur={onBlur}
                                        getOptionLabel={option => option.title}
                                        getOptionValue={option => option.id}
                                      />
                                      <ErrorMessage
                                        name={`${name}.${index}.items.${number}.property_id`}
                                        component="div"
                                        className="field-error"
                                      />
                                    </FormGroup>
                                  </Colxx>
                                  <PropertyRelation
                                    name={`${name}.${index}.items.${number}.dependency`}
                                    values={values[index]['items'][number].dependency}
                                    deleteItem={()=>innerProps.remove(number)}
                                    properties={properties}
                                    onChange={onChange}
                                    onBlur={onBlur}/>
                                </>
                              ))}
                          </>
                        )}
                      </FieldArray>
{/*                      <PropertyRelation
                        name={`${name}.${index}.dependency`}
                        values={values[index]['dependency']}
                        deleteItem={()=>remove(index)}
                        properties={properties}
                        onChange={onChange}
                        onBlur={onBlur}/>*/}
                    </Colxx>
                  </Row>
                </Colxx>

                {/*<Nestable*/}
                {/*  items={dependencies}*/}
                {/*  renderItem={renderItem}*/}
                {/*  onChange={({items}) => {*/}
                {/*    setDependencies(items)*/}
                {/*  }}*/}
                {/*/>*/}
              </Row>
            ))}
        </>
      )}
    </FieldArray>
  );
}

const PropertyLayout = ({match}) => {
  /*  const [dependencies, setDependencies] = useState([
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
    };*/
  const [initialValues, setInitialValues] = useState({
    layout: [],
  });
  const [properties, setProperties] = useState([]);

  useEffect(() => {
    axios
      .get(`${adminPathApi}/properties/`)
      .then(res => res.data)
      .then(data => {
        setProperties(data)
      })
      .catch((e)=>{
        if (e.response.status==402){
          window.location.replace(e.response.data.url)
        }
      })
  }, [])

  const onSubmit = (values) => {
    console.log(values)
  }

  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Row>
        <Colxx xss="12">
          <div className="text-zero top-right-button-container">
            <Link to={`${adminRoot}/advertisements/wrappers/list`} className={"btn btn-primary top-right-button"}>
              <i className={"fa fa-plus mr-1"}/>
              افزودن
            </Link>
          </div>
          <Breadcrumb heading={"menu.properties"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>

      <Colxx md={12}>
        <Formik
          enableReinitialize
          initialValues={initialValues}
          onSubmit={onSubmit}>
          {({errors, touched, setFieldValue, setFieldTouched, values}) => (
            <Form className="av-tooltip tooltip-label-right">
              <FieldArray name={'layout'}>
                {({insert, remove, push}) => (
                  <>
                    <Button
                      color="primary"
                      outline
                      size={"sm"}
                      className={"mx-2 mb-4 btn-block"}
                      onClick={() => push({})}
                    >
                      افزودن فیلد
                    </Button>
                    {(values.layout && values.layout.length > 0) ? (
                      <>
                        {values.layout.map((item, index) => (
                          <Card className="mb-3 px-3">
                            <CardBody>
                              <Colxx md="6">
                                <FormGroup>
                                  <Label>انتخاب ویژگی</Label>
                                  <FormikReactSelect
                                    options={properties}
                                    name={`layout.${index}.property_id`}
                                    value={values.layout[index].property_id}
                                    onChange={setFieldValue}
                                    onBlur={setFieldTouched}
                                    getOptionLabel={option => option.title}
                                    getOptionValue={option => option.id}
                                  />
                                  <ErrorMessage
                                    name={`layout.${index}.property_id`}
                                    component="div"
                                    className="field-error"
                                  />
                                </FormGroup>
                              </Colxx>
                              <PropertyRelation
                                name={`layout.${index}.dependency`}
                                values={values.layout[index].dependency}
                                deleteItem={()=>remove(index)}
                                onBlur={setFieldTouched}
                                onChange={setFieldValue}
                                properties={properties}/>
                            </CardBody>
                          </Card>
                        ))}
                      </>
                    ) : (
                      <Card className="mb-3">
                        <CardBody>هنوز هیچ ویژگی افزوده نشده است</CardBody>
                      </Card>
                    )
                    }
                  </>
                )}
              </FieldArray>
              <Button type="submit">ذخیره</Button>
            </Form>
          )}
        </Formik>
        {/*        <Button
          className="mb-4"
          size="xs"
          onClick={() => {
            setDependencies(prev => {
              return [...prev, {}]
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
        />*/}
      </Colxx>

    </Suspense>
  );
}

export default PropertyLayout;
