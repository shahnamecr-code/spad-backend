import React from "react";
import {useFormikContext} from "formik";
import {FormGroup, Label, Row} from "reactstrap";
import {Field} from "formik";
import {
  FormikCustomRadioGroup,
  FormikReactSelect
} from "../../../../containers/form-validations/FormikFields";
import {Colxx} from "../../../../components/common/CustomBootstrap";

const AdvertisementCategoryFilter = ({item}) => {
  let {values, setFieldValue, setFieldTouched} = useFormikContext();
  let value = values[item.code];
  let range = [];
  if (value && item.range_search) {
    range = value.split('-')
  }

  return (
    <>
      {item.range_search ? (
        <>
          {item.search_type == "text" ? (
            <Row key={item.code}>
              <Colxx md={6}>
                <FormGroup>
                  <Label>{item.label} از</Label>
                  <Field
                    name={`${item.code}.from`}
                    value={range.length > 0 ? range[0] : undefined}
                    onChange={(e) => {
                      range[0] = (e.target.value)
                      setFieldValue(item.code, range.join('-'))
                    }}
                    className={"form-control"}/>
                </FormGroup>
              </Colxx>
              <Colxx md={6}>
                <FormGroup>
                  <Label>تا</Label>
                  <Field
                    name={`${item.code}.to`}
                    value={range.length > 1 ? range[1] : undefined}
                    onChange={(e) => {
                      range[1] = (e.target.value)
                      setFieldValue(item.code, range.join('-'))
                    }}
                    className={"form-control"}/>
                </FormGroup>
              </Colxx>
            </Row>
          ) : ''}
          {item.search_type == "select" ? (
            <Row key={item.code}>
              <Colxx md={6}>
                <FormGroup>
                  <Label>{item.label} از</Label>
                  <FormikReactSelect
                    name={`${item.code}.from`}
                    onChange={(i,v) => {
                      range[0] = (v)
                      setFieldValue(item.code, range.join('-'))
                    }}
                    onBlur={setFieldTouched}
                    value={range.length > 0 ? range[0] : undefined}
                    options={item.options}/>
                </FormGroup>
              </Colxx>
              <Colxx md={6}>
                <FormGroup>
                  <Label>تا</Label>
                  <FormikReactSelect
                    name={`${item.code}.to`}
                    onChange={(i,v) => {
                      range[1] = (v)
                      setFieldValue(item.code, range.join('-'))
                    }}
                    onBlur={setFieldTouched}
                    value={range.length > 1 ? range[1] : undefined}
                    options={item.options}/>
                </FormGroup>
              </Colxx>
            </Row>
          ) : ''}
          {item.search_type == "checkbox" ? (
            <Row key={item.code}>
              <Colxx md={12}>
                <FormGroup>
                  <Label>{item.label} از</Label>
                  <FormikCustomRadioGroup
                    inline
                    label="انتخاب کنید..."
                    name={`${item.code}.from`}
                    onChange={(i,v) => {
                      range[0] = (v)
                      setFieldValue(item.code, range.join('-'))
                    }}
                    onBlur={setFieldTouched}
                    value={range.length > 0 ? range[0] : undefined}
                    options={item.options}/>
                </FormGroup>
              </Colxx>
              <Colxx md={12}>
                <FormGroup>
                  <Label>تا</Label>
                  <FormikCustomRadioGroup
                    inline
                    label="انتخاب کنید..."
                    name={`${item.code}.to`}
                    onChange={(i,v) => {
                      range[1] = (v)
                      setFieldValue(item.code, range.join('-'))
                    }}
                    onBlur={setFieldTouched}
                    value={range.length > 1 ? range[1] : undefined}
                    options={item.options}/>
                </FormGroup>
              </Colxx>
            </Row>
          ) : ''}
        </>
      ) : (
        <>
          {item.search_type == "text" ? (
            <FormGroup key={item.code}>
              <Label>{item.label}</Label>
              <Field
                name={item.code}
                className={"form-control"}/>
            </FormGroup>
          ) : ''}
          {item.search_type == 'select' ? (
            <FormGroup key={item.code}>
              <Label>{item.label}</Label>
              <FormikReactSelect
                name={item.code}
                onChange={setFieldValue}
                onBlur={setFieldTouched}
                value={value}
                options={item.options}/>
            </FormGroup>
          ) : ''}
          {item.search_type == 'checkbox' ? (
            <FormGroup key={item.code}>
              <Label>{item.label}</Label>
              <FormikCustomRadioGroup
                inline
                name={item.code}
                label="انتخاب کنید..."
                value={value}
                onChange={setFieldValue}
                onBlur={setFieldTouched}
                options={item.options}
              />
            </FormGroup>
          ) : ''}
        </>
      )}
    </>

  );
}

export default AdvertisementCategoryFilter;
