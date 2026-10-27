import {ErrorMessage, Field, FieldArray} from "formik";
import {Button, CardSubtitle, FormGroup, Label, Row} from "reactstrap";
import {Colxx} from "../../../../components/common/CustomBootstrap";
import {FormikReactSelect} from "../../../../containers/form-validations/FormikFields";
import {
  advertisementDependencyTypes,
  advertisementFieldAccessOptions
} from "../../../../constants/advertisementConstants";
import React, {useEffect, useState} from "react";
import {adminPathApi} from "../../../../constants/defaultValues";

const TransactionSelect = ({name, value, onChange, onBlur,isMulti}) => {
  const [transactions, setTransactions] = useState([]);
  useEffect(()=>{
    axios
      .get(`${adminPathApi}/transactions`)
      .then(res=>res.data)
      .then(data=>setTransactions(data))
  },[])

  return (
    <FormikReactSelect
      options={transactions}
      name={name}
      value={value}
      onChange={onChange}
      onBlur={onBlur}
      getOptionLabel={option => option.name}
      getOptionValue={option => option.id}
      isMulti={isMulti}/>
  );
}

const LandTypeSelect = ({name, value, onChange, onBlur,isMulti}) => {
  const [land_types, setLandTypes] = useState([]);

  useEffect(()=>{
    axios
      .get(`${adminPathApi}/land-types`)
      .then(res=>res.data)
      .then(data=>setLandTypes(data))
  },[])

  return (
    <FormikReactSelect
      options={land_types}
      name={name}
      value={value}
      onChange={onChange}
      onBlur={onBlur}
      getOptionLabel={option => option.name}
      getOptionValue={option => option.id}
      isMulti={isMulti}/>
  );
}

const PropertyRelation = ({name, values,field ,deleteItem, properties, onChange, onBlur}) => {

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
                    {field.type=='transaction'?(
                      <TransactionSelect
                        className={"form-control"}
                        name={`${name}.${index}.dependency_limit`}
                        value={values[index].dependency_limit}
                        onChange={onChange}
                        onBlur={onBlur}
                        isMulti={values[index].dependency_type=='like'}/>
                    ):(<>
                      {field.type=='land_type'?(
                        <LandTypeSelect
                          className={"form-control"}
                          name={`${name}.${index}.dependency_limit`}
                          value={values[index].dependency_limit}
                          onChange={onChange}
                          onBlur={onBlur}
                          isMulti={values[index].dependency_type=='like'}/>
                      ):(
                        <Field
                          className={"form-control"}
                          name={`${name}.${index}.dependency_limit`}
                          value={values[index].dependency_limit}/>
                      )}
                    </>)}
                  </FormGroup>
                </Colxx>
{/*                <Colxx md="6">
                  <FormGroup>
                    <Label>مقدار متناظر</Label>
                    <Field
                      name={`${name}.${index}.dependency_limit`}
                      value={values[index].dependency_limit}
                      className="form-control"/>
                  </FormGroup>
                </Colxx>*/}
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
                                  remove(index)
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
                                  <Row>
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
                                          getOptionValue={option => option.slug}
                                        />
                                        <ErrorMessage
                                          name={`${name}.${index}.items.${number}.property_id`}
                                          component="div"
                                          className="field-error"
                                        />
                                      </FormGroup>
                                    </Colxx>
                                    <Colxx md="6">
                                      <FormGroup>
                                        <Label>تعیین کلاس برای ویژگی</Label>
                                        <Field
                                          name={`${name}.${index}.items.${number}.class`}
                                          className={"form-control"}
                                        />
                                        <ErrorMessage
                                          name={`${name}.${index}.items.${number}.class`}
                                          component="div"
                                          className="field-error"
                                        />
                                      </FormGroup>
                                    </Colxx>
                                    <Colxx md="6">
                                      <FormGroup>
                                        <Label>سطح دسترسی فیلد</Label>
                                        <FormikReactSelect
                                          options={advertisementFieldAccessOptions}
                                          name={`${name}.${index}.items.${number}.access`}
                                          value={values[index]['items'][number].access??0}
                                          onChange={onChange}
                                          onBlur={onBlur}
                                        />
                                        <ErrorMessage
                                          name={`${name}.${index}.items.${number}.access`}
                                          component="div"
                                          className="field-error"
                                        />
                                      </FormGroup>
                                    </Colxx>
                                  </Row>
                                  <PropertyRelation
                                    name={`${name}.${index}.items.${number}.dependency`}
                                    values={values[index]['items'][number].dependency}
                                    field={properties.find(field=>field.slug==values[index]['items'][number].property_id)}
                                    deleteItem={()=>innerProps.remove(number)}
                                    properties={properties}
                                    onChange={onChange}
                                    onBlur={onBlur}/>
                                </>
                              ))}
                          </>
                        )}
                      </FieldArray>
                    </Colxx>
                  </Row>
                </Colxx>
              </Row>
            ))}
        </>
      )}
    </FieldArray>
  );
}

export default PropertyRelation;
