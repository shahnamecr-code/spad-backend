import { useFormikContext } from "formik";
import styles from "./createAdField.module.scss";
import Switch from "../../../general/switch/Switch";
import { Select } from "../../../general/select/Select";
import FileUploader from "../../../general/fileUploader/FileUploader";
import Input from "../../../general/input/Input";
import { deepCopy, getFromOjectByKey } from "../../../../utils";
import TextArea from "../../../general/textArea/TextArea";
import { IField, ILayout } from "../../../../types/createAd.types";
import { RadioButton } from "../../../general/radioButton/RadioButton";
import CheckBox from "../../../general/checkBox/CheckBox";
import AdLocation from "../adLocation/adLocation";
import ImportantPlaces from "../importantPlaces/ImportantPlaces";

interface IProps {
  fieldInfo: ILayout;
  field: IField;
  errMsgs?: { [key: string]: string[] };
}

const CreateAdField = (props: IProps) => {
  let { values, setFieldValue } = useFormikContext<any>();
  const name = props.field.slug || "";
  const value: any = getFromOjectByKey(values, name);

  const fieldLabel = () => {
    return `${props.field.title} ${
      props.field.unit || props.field.separator
        ? `(${[
            props.field.separator && value
              ? Number(value).toLocaleString("en")
              : "",
            props.field.unit_name || props.field.unit,
          ]
            .filter((x) => x)
            .join(" ")})`
        : ""
    }`;
  };

  const fieldProps = {
    value: value,
    onChange: (e: any) => setFieldValue(name, e),
    label: fieldLabel(),
    validationMessage: props.errMsgs ? props.errMsgs[name]?.join(" - ") : "",
  };

  const renderFields = () => {
    switch (props.field?.type) {
      case "boolean":
      case "switch":
        return <Switch {...fieldProps} />;
      case "important_places":
        return (
          <ImportantPlaces
            values={values}
            setFieldValue={setFieldValue}
            errMsgs={props.errMsgs}
            options={props.field.options}
          />
        );
      case "geo":
        return (
          <AdLocation
            values={values}
            setFieldValue={setFieldValue}
            errMsgs={props.errMsgs}
          />
        );
      case "images":
        return renderUploader();
      case "integer":
        return <Input type="number" {...fieldProps} />;
      case "text":
        return <Input {...fieldProps} />;
      case "multiselect":
      case "multi-select":
        return (
          <Select
            label={
              props.field?.title +
              (props.field.unit
                ? ` (${props.field.unit_name || props.field.unit})`
                : "")
            }
            isMulti
            items={props.field.options}
            onChange={(newValue) => {
              if (newValue instanceof Array)
                setFieldValue(
                  name,
                  newValue?.map((x) => x.value)
                );
            }}
            id={name}
          />
        );
      case "checkbox":
        return (
          <div className={styles["items-container"]}>
            <span>{props.field.title}: </span>
            {props.field.options?.map((item, i) => (
              <CheckBox
                key={i}
                label={item.label}
                checked={value?.includes(item.value)}
                onChange={() => {
                  let arr: string[] = deepCopy(value || []);
                  let index = arr.findIndex((x) => x === item.value);
                  if (index > -1) arr.splice(index, 1);
                  else arr.push(item.value || "");
                  setFieldValue(name, arr);
                }}
              />
            ))}
          </div>
        );
      case "radio":
        return (
          <div className={styles["items-container"]}>
            <span>{props.field.title}: </span>
            {props.field.options?.map((item, i) => (
              <RadioButton
                key={i}
                label={item.label}
                checked={value === item.value}
                onChange={() => setFieldValue(name, item.value)}
              />
            ))}
          </div>
        );
      case "select":
        return (
          <Select
            label={fieldLabel()}
            items={props.field.options}
            onChange={(newValue) => {
              if (!(newValue instanceof Array))
                setFieldValue(name, newValue?.value);
            }}
            id={name}
          />
        );
      case "textarea":
        return (
          <TextArea
            value={value}
            onChange={(e) => setFieldValue(name, e)}
            placeHolder={props.field.title}
            rows={3}
            validationMessage={
              props.errMsgs ? props.errMsgs[name]?.join(" - ") : ""
            }
          />
        );
      default:
        return <></>;
    }
  };

  const renderUploader = () => (
    <div className={styles["uloader-container"]}>
      <p className={styles["uploader-title"]}>
        افزودنِ عکس بازدید آگهی شما را تا سه برابر افزایش می‌دهد.
      </p>
      <FileUploader
        onChange={(files) => {
          setFieldValue(
            name,
            files?.map((x) => x.id)
          );
        }}
        imagesAdress={value || []}
      />
    </div>
  );

  return (
    <>
      <div
        className={` ${
          props.field.type === "geo" || props.field.type === "important_places"
            ? "w-100"
            : props.field.type === "images" || props.field.type === "textarea"
            ? "col-md-12"
            : "col-md-6"
        } ${styles["fields-container"]}`}
      >
        {renderFields()}
      </div>
    </>
  );
};

export default CreateAdField;
