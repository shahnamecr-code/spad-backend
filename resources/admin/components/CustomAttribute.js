import React, { useEffect, useState } from "react";
import { Card, CardBody, Button, FormGroup, Label, Row } from "reactstrap";
import {
  FormikReactSelect,
  FormikSwitch,
} from "../containers/form-validations/FormikFields";
import ReactQuill, { Quill } from "react-quill";
import "react-quill/dist/quill.snow.css";
import ImageUploader from "quill-image-uploader";
import axios from "axios";
import { Field, FieldArray, useFormikContext } from "formik";
import MediaButton from "./MediaLibrary";
import { Colxx } from "./common/CustomBootstrap";
import SearchMultiSelect from "./SearchMultiSelect";
import { adminPathApi } from "../constants/defaultValues";
import ReactSelect from "react-select";
import MediaLibraryButton from "./mediaLibrary/MediaLibrary";

Quill.register("modules/imageUploader", ImageUploader);
const quillModules = {
  // #3 Add "image" to the toolbar
  toolbar: [
    ["bold", "italic", "underline", "strike", "blockquote"],
    [
      { list: "ordered" },
      { list: "bullet" },
      { indent: "-1" },
      { indent: "+1" },
    ],
    ["link", "image"],
    ["clean"],
  ],
  // # 4 Add module and upload function
  imageUploader: {
    upload: (file) => {
      return new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append("file", file);

        axios
          .post(`${adminPathApi}/file/upload`, formData)
          .then((response) => resolve(response.data.url))
          .catch((error) => {
            reject("Upload failed");
            console.error("Error:", error);
          });
      });
    },
  },
};

const CustomAttribute = ({
  errors,
  touched,
  setFieldValue,
  setFieldTouched,
  values,
  attribute,
  ...props
}) => {
  const { getFieldMeta } = useFormikContext();
  let name = props.prefix
    ? `${props.prefix}.${attribute.code}`
    : attribute.code;
  /* let defaultValue;
   if(props.prefix){
     defaultValue = values[props.prefix] && values[props.prefix][attribute.code]?values[props.prefix][attribute.code]:''
   }else{
     defaultValue = values[attribute.code]?values[attribute.code]:'';
   }*/
  const [fileUrls, setFileUrls] = useState([]);

  let output = "";
  switch (attribute.type) {
    case "image":
      output = (
        <FormGroup key={name}>
          <Label>
            {attribute.name}
            {props.children && props.children}
          </Label>
          <MediaLibraryButton
            multiple={false}
            onSubmit={(values) => setFieldValue(name, values)}
            onChange={(values) => setFieldValue(name, values)}
            defaultFileIdz={getFieldMeta(name).value || []}
          />
          {/*          <MediaButton onSubmit={(value) => setFieldValue(name, value.files)} default_value={getFieldMeta(name).value}
                       name={name} setFieldValue={setFieldValue}/>*/}
        </FormGroup>
      );
      break;
    case "gallery":
      output = (
        <FormGroup key={name}>
          <Label>
            {attribute.name}
            {props.children && props.children}
          </Label>
          <MediaLibraryButton
            multiple={true}
            onSubmit={(values) => setFieldValue(name, values)}
            onChange={(values) => setFieldValue(name, values)}
            defaultFileIdz={getFieldMeta(name).value || []}
          />
          {/*          <MediaButton onSubmit={(value) => setFieldValue(name, value.files)} default_value={values[name]} name={name}
                       setFieldValue={setFieldValue} multiple/>*/}
        </FormGroup>
      );
      break;
    case "select":
      const options = [];
      if (attribute.options) {
        attribute.options.map((option) => {
          options.push({
            label: option.value,
            value: option.id,
            key: option.id,
          });
        });
      }
      output = (
        <FormGroup key={attribute.code}>
          <Label>
            {attribute.name}
            {props.children && props.children}
          </Label>
          <FormikReactSelect
            value={getFieldMeta(name).value}
            onChange={setFieldValue}
            onBlur={setFieldTouched}
            options={options}
            name={name}
          />
        </FormGroup>
      );
      break;
    case "color":
      break;
    case "boolean":
      output = (
        <FormGroup key={name}>
          <Label>
            {attribute.name}
            {props.children && props.children}
          </Label>
          <FormikSwitch
            name={name}
            className="custom-switch custom-switch-primary"
            value={getFieldMeta(name).value}
            onChange={setFieldValue}
            onBlur={setFieldTouched}
          />
        </FormGroup>
      );
    case "category-select":
      const [cateData, setCateData] = useState(null);
      const [notChange, setNotChange] = useState(null);
      useEffect(() => {
        if (cateData == null) {
          axios
            .get(
              `${adminPathApi}/advertisement-category/displayCategoryFilters`
            )
            .then((res) => setCateData({ first: res.data.categories }));
        }
      }, [notChange]);
      const transaction = (id) => {
        axios
          .get(
            `${adminPathApi}/advertisement-category/displayCategoryFilters?category=${id}`
          )
          .then((res) =>
            setCateData({
              ...cateData,
              second: res.data.categories,
              third: null,
            })
          );
      };
      const application_type = (id) => {
        axios
          .get(
            `${adminPathApi}/advertisement-category/displayCategoryFilters?category=${id}`
          )
          .then((res) =>
            setCateData({ ...cateData, third: res.data.categories })
          );
      };

      output = (
        <FormGroup key={name}>
          <Label>
            {attribute.name}
            {props.children && props.children}
          </Label>
          <Row>
            <Colxx md={4}>
              <ReactSelect
                //value={}
                onChange={(val) => {
                  setFieldValue(`${name}.transaction`, val.id);
                  setFieldValue(`${name}.application_type`, null);
                  setFieldValue(`${name}.land_type`, null);
                  setCateData({ ...cateData, third: null, second: null });
                  transaction(val.id);
                }}
                onBlur={setFieldTouched}
                options={cateData && cateData.first}
                name={`${name}.transaction`}
                getOptionLabel={(data) => data.name}
                getOptionValue={(data) => data.id}
              />
            </Colxx>
            {cateData && cateData.second && (
              <Colxx md={4}>
                <ReactSelect
                  //value={}
                  onChange={(val) => {
                    setFieldValue(`${name}.application_type`, val.id);
                    setFieldValue(`${name}.land_type`, null);
                    setCateData({ ...cateData, third: null });
                    application_type(val.id);
                  }}
                  onBlur={setFieldTouched}
                  options={cateData && cateData.second}
                  name={`${name}.application_type`}
                  getOptionLabel={(data) => data.name}
                  getOptionValue={(data) => data.id}
                />
              </Colxx>
            )}
            {cateData && cateData.third && (
              <Colxx md={4}>
                <ReactSelect
                  //value={}
                  onChange={(val) => {
                    setFieldValue(`${name}.land_type`, val.id);
                  }}
                  onBlur={setFieldTouched}
                  options={cateData && cateData.third}
                  name={`${name}.land_type`}
                  getOptionLabel={(data) => data.name}
                  getOptionValue={(data) => data.id}
                />
              </Colxx>
            )}
          </Row>
        </FormGroup>
      );
      break;
    case "textarea":
      const quillFormats = [
        "header",
        "bold",
        "italic",
        "underline",
        "strike",
        "blockquote",
        "list",
        "bullet",
        "indent",
        "link",
        "image",
      ];
      output = (
        <FormGroup key={name}>
          <Label>
            {attribute.name}
            {props.children && props.children}
          </Label>
          {attribute.simple ? (
            <Field
              className={"form-control"}
              name={name}
              as={"textarea"}
              style={{ height: "150px" }}
            />
          ) : (
            <ReactQuill
              theme="snow"
              value={getFieldMeta(name).value ?? ""}
              modules={quillModules}
              formats={quillFormats}
              onChange={(val) => {
                setFieldValue(name, val);
              }}
              name={name}
            />
          )}
        </FormGroup>
      );
      break;
    case "list-item":
      if (attribute.settings) {
        let settings = attribute.settings;

        output = (
          <FormGroup>
            <FieldArray name={name}>
              {({ insert, remove, push }) => (
                <>
                  <Card>
                    <CardBody>
                      <label>{attribute.name}</label>
                      <Row>
                        {values[name] &&
                          values[name].length > 0 &&
                          values[name].map((item, index) => (
                            <Colxx
                              md="12"
                              className="custom-inner-cards-wrapper mb-2"
                            >
                              {settings.map((setting) => (
                                <CustomAttribute
                                  attribute={{
                                    ...setting,
                                    code: `${attribute.code}.${index}.${setting.code}`,
                                  }}
                                  errors={errors}
                                  touched={touched}
                                  setFieldValue={setFieldValue}
                                  setFieldTouched={setFieldTouched}
                                  getFieldMeta={getFieldMeta}
                                  values={values}
                                />
                              ))}
                              <button
                                className="custom-inner-cards-close"
                                onClick={() => remove(index)}
                              >
                                <span className="fa fa-times" />
                              </button>
                            </Colxx>
                          ))}
                      </Row>
                      <Button
                        outline
                        color="primary"
                        size="sm"
                        className="mx-2 w-100"
                        onClick={() => push({})}
                      >
                        <span className="fa fa-plus" />
                      </Button>
                    </CardBody>
                  </Card>
                </>
              )}
            </FieldArray>
          </FormGroup>
        );
      }
      break;
    case "product-category-multi-select":
      const fetch = (setloadOptions, search = "") => {
        axios
          .get(`${adminPathApi}/category?per_page=10`)
          .then((res) => res.data)
          .then((data) => {
            setloadOptions(data.data);
          });
      };
      output = (
        <SearchMultiSelect
          name={name}
          onChange={setFieldValue}
          request={fetch}
          getOptionLabel={(option) => option.name}
          getOptionValue={(option) => option.id}
          value={getFieldMeta(name).value || ""}
        />
      );
      break;
    default:
      output = (
        <FormGroup key={name}>
          <Label>
            {attribute.name}
            {props.children && props.children}
            {/*            {attribute.is_variable ? (
              <label>
                <Field type="checkbox" name='super_attributes' value={attribute.code} onChange={(e)=>{
                  let super_attributes;
                  let prev=values.super_attributes?values.super_attributes:[];
                  if(e.target.checked){
                    super_attributes=[...prev,attribute.code]
                  }else{
                    super_attributes=prev.filter(function (value,index){
                      return value!=attribute.code;
                    })
                  }
                  setFieldValue('super_attributes',super_attributes)
                }} />
                استفاده به عنوان متغیر
              </label>
            ):''}*/}
          </Label>
          {/*{(!values.super_attributes || !values.super_attributes.includes(attribute.code)) && (*/}
          {/*  <>*/}
          <Field className="form-control" name={`${name}`} />
          {errors[name] && touched[name] && (
            <div className="invalid-feedback d-block">
              {errors[attribute.code]}
            </div>
          )}
          {/*            </>
          )}*/}
        </FormGroup>
      );
  }

  return output;
};
export default CustomAttribute;
