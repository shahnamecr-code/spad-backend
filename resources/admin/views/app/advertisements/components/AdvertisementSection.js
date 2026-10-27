import React, { useEffect, useState } from "react";
import {
  Button,
  Card,
  CardBody,
  CardHeader,
  CardTitle,
  FormGroup,
  FormText,
  Label,
} from "reactstrap";
import { Field, useFormikContext } from "formik";
import {
  FormikCheckboxGroup,
  FormikCustomRadioGroup,
  FormikReactSelect,
  FormikSwitch,
} from "../../../../containers/form-validations/FormikFields";
import TransactionSelect from "./TransactionSelect";
import LandTypeSelect from "./LandTypeSelect";
import MediaButton from "../../../../components/MediaLibrary";
import Num2persian from "num2persian";
import { adminPathApi } from "../../../../constants/defaultValues";
import axios from "axios";
import { Colxx } from "../../../../components/common/CustomBootstrap";
import Select from "react-select";
import MediaLibraryButton from "../../../../components/mediaLibrary/MediaLibrary";

const normalizeMoneyDigits = (value) => String(value ?? "").replace(/[^0-9]/g, "");

const isMoneyField = (field) => {
  if (!field || field.type !== "integer") return false;
  const unit = `${field.unit || ""} ${field.unit_name || ""}`;
  return /IRT|IRR|تومان|ریال/i.test(unit) || /(قیمت|مبلغ|اجاره|ودیعه|رهن)/i.test(`${field.title || ""} ${field.slug || ""}`);
};

const moneyUnit = (field) => {
  const unit = String(field?.unit_name || field?.unit || "").trim();
  if (/^(IRT|تومان)$/i.test(unit)) return "تومان";
  if (/^(IRR|ریال)$/i.test(unit)) return "ریال";
  return unit || "تومان";
};

const moneyPreview = (value, field) => {
  const digits = normalizeMoneyDigits(value);
  if (!digits) return null;
  const unit = moneyUnit(field);
  const number = Number(digits);
  let words = "";
  try {
    words = Number.isFinite(number) ? Num2persian(number) : "";
  } catch (_) {
    words = "";
  }

  return (
    <div className="spad-ad-money-preview" style={{ display: "block", lineHeight: 1.9 }}>
      <div>{number.toLocaleString("fa-IR")} {unit}</div>
      {words ? <strong style={{ display: "block", fontWeight: 600 }}>{words} {unit}</strong> : null}
    </div>
  );
};

const AdvertisementPosition = ({ name, value = {} }) => {
  const { setFieldValue, setFieldTouched } = useFormikContext();
  const [regions, setRegions] = useState([]);
  const [shiraz, setShiraz] = useState(value.city || null);

  useEffect(() => {
    axios
      .get(`${adminPathApi}/location/allCities?page=1&search=شیراز`)
      .then((res) => res.data?.data?.[0])
      .then((city) => {
        if (!city) return;
        setShiraz(city);
        if (!value.city || value.city.id !== city.id) {
          setFieldValue(name, { ...value, city, region: undefined });
        }
        return axios.get(`${adminPathApi}/location/cityAllRegions/${city.id}`);
      })
      .then((res) => {
        if (res?.data) setRegions(res.data);
      })
      .catch(() => setRegions([]));
  }, []);

  useEffect(() => {
    if (shiraz?.id) {
      axios
        .get(`${adminPathApi}/location/cityAllRegions/${shiraz.id}`)
        .then((res) => setRegions(res.data || []));
    }
  }, [shiraz?.id]);

  return (
    <>
      <FormGroup>
        <Label>شهر</Label>
        <input className="form-control" value="شیراز" disabled readOnly />
        <FormText color="muted">شهر همه آگهی‌های SPAD به صورت ثابت شیراز است.</FormText>
      </FormGroup>
      <FormGroup>
        <Label>خیابان</Label>
        <Select
          options={regions}
          value={value.region}
          name={`${name}.region`}
          onChange={(item) => setFieldValue(name, { ...value, city: shiraz, region: item })}
          onBlur={() => setFieldTouched(`${name}.region`, true)}
          getOptionLabel={(option) => option.name}
          getOptionValue={(option) => option.id}
          placeholder="انتخاب خیابان"
        />
      </FormGroup>
      <FormGroup>
        <Label>آدرس کامل</Label>
        <Field className="form-control" name={`${name}.address`} />
      </FormGroup>
    </>
  );
};

const CustomField = ({ name, field, fileUrls,setDefaultFileUrls, ...props }) => {
  let { values, setFieldValue, setFieldTouched } = useFormikContext();
  function separate(Number) {
    Number += "";
    Number = Number.replace(",", "");
    var x = Number.split(".");
    var y = x[0];
    var z = x.length > 1 ? "." + x[1] : "";
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(y)) y = y.replace(rgx, "$1" + "-" + "$2");
    return y + z;
}
  let value = values[name];
  let output = "";
  let options = [];
  if (field !== undefined) {
    switch (field.type) {
      case "text":
        output = (
          <FormGroup>
            <Label>{field.title}</Label>
            <Field name={name} value={value} className="form-control" />
          </FormGroup>
        );
        break;
      case "textarea":
        output = (
          <FormGroup>
            <Label>{field.title}</Label>
            <Field
              as="textarea"
              name={name}
              value={value}
              className="form-control"
            />
          </FormGroup>
        );
        break;
      case "boolean":
        output = (
          <FormGroup>
            <Label>{field.title}</Label>
            <FormikSwitch
              name={name}
              className="custom-switch custom-switch-primary"
              value={value}
              onChange={setFieldValue}
              onBlur={setFieldTouched}
            />
          </FormGroup>
        );
        break;
      case "integer":
        output = (
          <FormGroup>
            <Label>{field.title}</Label>
            <Field
              type="number"
              name={name}
              value={value}
              className="form-control"
            />
            {isMoneyField(field) && value
              ? moneyPreview(value, field)
              : field.separator == true && value
              ? <FormText>{separate(value)} {field.unit_name}</FormText>
              : null}
          </FormGroup>
        );
        break;
      case "select":
        if (field.enum) {
          field.enum.map((item, index) => {
            let val = item;
            let label = item;
            if (field.enumNames && field.enumNames[index])
              label = field.enumNames[index];
            options.unshift({ label: label, value: val });
          });
        }
        output = (
          <FormGroup>
            <Label>{field.title}</Label>
            <FormikReactSelect
              value={value}
              onChange={setFieldValue}
              onBlur={setFieldTouched}
              options={options}
              name={name}
            />
          </FormGroup>
        );
        break;
      case "multiselect":
        if (field.enum) {
          field.enum.map((item, index) => {
            let val = item;
            let label = item;
            if (field.enumNames && field.enumNames[index])
              label = field.enumNames[index];
            options.unshift({ label: label, value: val });
          });
        }
        output = (
          <FormGroup>
            <Label>{field.title}</Label>
            <FormikReactSelect
              value={value}
              onChange={setFieldValue}
              onBlur={setFieldTouched}
              options={options}
              name={name}
              isMulti
            />
          </FormGroup>
        );
        break;
      case "radio":
        if (field.enum) {
          field.enum.map((item, index) => {
            let val = item;
            let label = item;
            if (field.enumNames && field.enumNames[index])
              label = field.enumNames[index];
            options.unshift({ label: label, value: val });
          });
        }
        output = (
          <FormGroup className="error-l-175">
            <Label className="d-block">{field.title}</Label>
            <FormikCustomRadioGroup
              inline
              name={name}
              label="انتخاب کنید..."
              value={field.value}
              onChange={setFieldValue}
              onBlur={setFieldTouched}
              options={options}
            />
          </FormGroup>
        );
        break;
      case "checkbox":
        if (field.enum) {
          field.enum.map((item, index) => {
            let val = item;
            let label = item;
            if (field.enumNames && field.enumNames[index])
              label = field.enumNames[index];
            options.unshift({ label: label, value: val });
          });
        }
        output = (
          <FormGroup className="error-l-150 ">
            <Label className="d-block">{field.title}</Label>
            <FormikCheckboxGroup
              inline
              name={name}
              label="انتخاب کنید..."
              value={value ?? []}
              onChange={setFieldValue}
              onBlur={setFieldTouched}
              options={options}
            />
          </FormGroup>
        );
        break;
      case "transaction":
        output = (
          <FormGroup className="error-l-150 ">
            <Label className="d-block">{field.title}</Label>
            <TransactionSelect name={name} />
          </FormGroup>
        );
        break;
      case "land_type":
        output = (
          <FormGroup className="error-l-150 ">
            <Label className="d-block">{field.title}</Label>
            <LandTypeSelect name={name} />
          </FormGroup>
        );
        break;
      case "images":
        output = (
          <FormGroup>
            <Label>{field.title}</Label>
            <MediaLibraryButton
              multiple={true}
              onSubmit={(values) => setFieldValue(name, values)}
              onChange={(values) => setFieldValue(name, values)}
              defaultFileIdz={values[name]}
              defaultFileUrls={fileUrls ? fileUrls : []}
              setDefaultFileUrls={setDefaultFileUrls}
            />
          </FormGroup>
        );
        break;
      case "geo":
        output = <AdvertisementPosition name={name} value={value} />;
        break;
    }
  }
  return output;
};

const AdvertisementProperty = ({ property, allProperties, key, fileUrls,setDefaultFileUrls }) => {
  let field = allProperties[property.property_id];
  let { values } = useFormikContext();
  let value = values[property.property_id];

  return (
    <p className={property.class ?? "col-12"} key={key}>
      <CustomField
        name={property.property_id}
        field={field}
        fileUrls={fileUrls}
        setDefaultFileUrls={setDefaultFileUrls}
      />
      {property.dependency &&
        property.dependency.map((dependency) => (
          <>
            {(dependency.dependency_type == "eq" &&
              value == dependency.dependency_limit) ||
            (dependency.dependency_type == "gt" &&
              value > dependency.dependency_limit) ||
            (dependency.dependency_type == "lt" &&
              value < dependency.dependency_limit) ? (
              <>
                {dependency.items &&
                  dependency.items.map((item, index) => (
                    <AdvertisementProperty
                      key={index}
                      allProperties={allProperties}
                      property={item}
                    />
                  ))}
              </>
            ) : (
              ""
            )}
          </>
        ))}
    </p>
  );
};

const AdvertisementSection = ({ wrapper, properties, fileUrls,setDefaultFileUrls }) => {
  const [show, setShow] = useState(true);

  return (
    <Card className="mb-4">
      <CardHeader className="d-flex align-items-center justify-content-between bg-primary py-2">
        <CardTitle className="mb-0">{wrapper.title}</CardTitle>
        <Button color="light" size="sm" onClick={() => setShow(!show)}>
          {show ? (
            <span className="fa fa-angle-up text-primary" />
          ) : (
            <span className="fa fa-angle-down text-primary" />
          )}
        </Button>
      </CardHeader>
      <CardBody className={show ? "d-flex flex-wrap" : "d-none"}>
        {wrapper.layout &&
          wrapper.layout.map((item, index) => (
            <AdvertisementProperty
              key={index}
              property={item}
              allProperties={properties}
              fileUrls={fileUrls}
              setDefaultFileUrls={setDefaultFileUrls}
            />
          ))}
      </CardBody>
    </Card>
  );
};

export default AdvertisementSection;
