import { useFormikContext } from "formik";
import React from "react";
import {
  IDependency,
  IField,
  IFildsSection,
  IProperty,
} from "../../../../types/createAd.types";
import CreateAdField from "../createAdField/CreateAdField";
import styles from "./createAdSection.module.scss";

interface IProps {
  sectionData: IFildsSection;
  properties: IProperty;
  idPrefix?: string;
  errMsgs?: { [key: string]: string[] };
}

const CreateAdSection = (props: IProps) => {
  let { values } = useFormikContext<any>();

  const renderFieldDependency = (dependency?: IDependency) =>
    dependency?.items.map((item, i) => (
      <CreateAdField
        key={i}
        fieldInfo={item}
        field={props.properties[item.property_id]}
        errMsgs={props.errMsgs}
      />
    ));
  const shouldRenderDependency = (dependency: IDependency, field: IField) => {
    const value = values[field.slug || ""];
    let res = false;
    switch (dependency?.dependency_type) {
      case "lt":
        if (value < dependency.dependency_limit) res = true;
        break;
      case "gt":
        if (value > dependency.dependency_limit) res = true;
        break;
      case "eq":
        if (value === dependency.dependency_limit) res = true;
        break;
      default:
        break;
    }
    return res;
  };

  return (
    <div className={styles.container}>
      <h3 className={styles.title}>{props.sectionData.title}</h3>
      <div className={styles.divider} />
      <div className={styles["fields-container"]}>
        {props.sectionData.layout.map((field, i) => (
          <React.Fragment key={i}>
            <CreateAdField
              key={i}
              fieldInfo={field}
              field={props.properties[field.property_id]}
              errMsgs={props.errMsgs}
            />
            {field.dependency?.map((dependency, i) => (
              <React.Fragment key={i}>
                {shouldRenderDependency(
                  dependency,
                  props.properties[field.property_id]
                ) && renderFieldDependency(dependency)}
              </React.Fragment>
            ))}
          </React.Fragment>
        ))}
      </div>
    </div>
  );
};

export default CreateAdSection;
