import styles from "./field.module.scss";
import { Select } from "../../../general/select/Select";
import Switch from "../../../general/switch/Switch";
import { deepCopy, getFromOjectByKey } from "../../../../utils";
import { useFormikContext } from "formik";
import { IFilter } from "../../../../types/mainPage.types";
import CheckBox from "../../../general/checkBox/CheckBox";
import { RadioButton } from "../../../general/radioButton/RadioButton";
import Input from "../../../general/input/Input";
import AdLocation from "../../create/adLocation/adLocation";
import TextArea from "../../../general/textArea/TextArea";
import { Autocomplete } from "../../../general/creatableSelect/CreatableSelect";

interface IProps {
  info: IFilter;
  className?: string;
  errMsgs?: { [key: string]: string[] };
}

export const fieldLabel = (info: IFilter, value: any) => {
  return `${info.label} ${
    info.unit || info.separator
      ? `(${[
          info.separator && value
            ? info.range_search
              ? `از ${[
                  value.from ? Number(value.from).toLocaleString("en") : "-",
                  value.to ? Number(value.to).toLocaleString("en") : "-",
                ]
                  .filter((x) => x)
                  .join(" تا ")}`
              : Number(value).toLocaleString("en")
            : "",
          info.unit,
        ]
          .filter((x) => x)
          .join(" ")})`
      : ""
  }`;
};

const Field = (props: IProps) => {
  let { values, setFieldValue } = useFormikContext<any>();
  const name = props.info.code;
  const value: any = getFromOjectByKey(values, name);

  const fieldProps = {
    value: value,
    onChange: (e: any) => setFieldValue(name, e),
    label: props.info.label ? fieldLabel(props.info, value) : "",
    validationMessage: props.errMsgs ? props.errMsgs[name]?.join(" - ") : "",
  };

  const autoCompleteItems =
    props.info.options?.find((option) => option.value == value) || !value
      ? props.info.options
      : [...(props.info.options || []), { label: value, value: value }];

  const renderField = () => {
    switch (props.info.search_type) {
      case "integer":
        return (
          <Input
            placeHolder={props.info.range_search ? props.info.label : ""}
            label={props.info.range_search ? "" : fieldLabel(props.info, value)}
            value={(getFromOjectByKey(values, name) as any) || ""}
            onChange={(e) => {
              setFieldValue(name, e);
            }}
            type="number"
            validationMessage={
              props.errMsgs ? props.errMsgs[name]?.join(" - ") : ""
            }
          />
        );
      case "text":
        return (
          <Input
            {...fieldProps}
            placeHolder={props.info.range_search ? props.info.label : ""}
            label={props.info.range_search ? "" : fieldLabel(props.info, value)}
          />
        );
      case "boolean":
      case "switch":
        return (
          <Switch
            label={props.info.label}
            value={value}
            onChange={(value) => setFieldValue(name, value ? value : undefined)}
          />
        );
      case "checkbox":
        return (
          <div
            className={
              props.info.range_search
                ? styles["items-container-search"]
                : styles["items-container"]
            }
          >
            <span
              className={
                props.info.range_search ? styles["label-search"] : styles.label
              }
            >
              {fieldLabel(props.info, value)}
            </span>{" "}
            <div
              className={
                props.info.range_search
                  ? styles["items-container"]
                  : styles["items-container-search"]
              }
            >
              {props.info.options?.map((item, i) => (
                <CheckBox
                  key={i}
                  label={item.label}
                  checked={value?.includes(item.value)}
                  onChange={() => {
                    let arr: string[] = deepCopy(value || []);
                    let index = arr.findIndex((x) => x === item.value);
                    if (index > -1) arr.splice(index, 1);
                    else arr.push(item.value);
                    setFieldValue(name, arr);
                  }}
                />
              ))}
            </div>
          </div>
        );
      case "select":
        return props.info.auto_complete ? (
          <Autocomplete
            label={props.info.range_search ? "" : fieldLabel(props.info, value)}
            placeholder={props.info.range_search ? props.info.label : ""}
            id={props.info.code}
            items={autoCompleteItems}
            value={autoCompleteItems?.find((option) => option.value == value)}
            onChange={(selected) => {
              if (!(selected instanceof Array))
                setFieldValue(
                  name,
                  typeof selected === "string" ? selected : selected?.value
                );
            }}
          />
        ) : (
          <Select
            label={props.info.range_search ? "" : fieldLabel(props.info, value)}
            placeholder={props.info.range_search ? props.info.label : ""}
            id={props.info.code}
            items={props.info.options}
            value={props.info.options?.find((x) => x.value == value)}
            onChange={(newValue) => {
              if (!(newValue instanceof Array))
                setFieldValue(name, newValue?.value);
            }}
            validationMessage={
              props.errMsgs ? props.errMsgs[name]?.join(" - ") : ""
            }
          />
        );
      case "multiselect":
      case "multi-select":
        return (
          <Select
            label={
              props.info.range_search
                ? ""
                : props.info.label +
                  (props.info.unit ? ` (${props.info.unit})` : "")
            }
            placeholder={props.info.range_search ? props.info.label : ""}
            id={props.info.code}
            items={props.info.options}
            value={props.info.options?.filter((x) => value?.includes(x.value))}
            onChange={(newValue) => {
              if (newValue instanceof Array)
                setFieldValue(
                  name,
                  newValue.map((x) => x.value)
                );
            }}
            validationMessage={
              props.errMsgs ? props.errMsgs[name]?.join(" - ") : ""
            }
            isMulti
          />
        );
      case "radio":
        return (
          <div
            className={
              props.info.range_search
                ? styles["items-container-search"]
                : styles["items-container"]
            }
          >
            <span
              className={
                props.info.range_search ? styles["label-search"] : styles.label
              }
            >
              {fieldLabel(props.info, value)}
            </span>{" "}
            <div
              className={
                props.info.range_search
                  ? styles["items-container"]
                  : styles["items-container-search"]
              }
            >
              {props.info.options?.map((item, i) => (
                <RadioButton
                  key={i}
                  label={item.label}
                  checked={value == item.value}
                  onChange={() => setFieldValue(name, item.value)}
                />
              ))}
            </div>
          </div>
        );
      case "geo":
        return (
          <AdLocation
            values={values}
            setFieldValue={setFieldValue}
            hideAddress
            hideMap
            regionIsMulty
            showRegion
            errMsgs={props.errMsgs}
          />
        );
      case "textarea":
        return (
          <TextArea
            value={value}
            onChange={(e) => setFieldValue(name, e)}
            label={props.info.label}
            rows={1}
            validationMessage={
              props.errMsgs ? props.errMsgs[name]?.join(" - ") : ""
            }
          />
        );
      default:
        return null;
    }
  };

  return (
    <div className={`${styles.container} ${props.className}`}>
      {renderField()}
    </div>
  );
};

export default Field;
