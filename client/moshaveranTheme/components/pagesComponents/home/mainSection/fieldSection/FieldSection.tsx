import styles from "./fieldSection.module.scss";
import { getFromOjectByKey, useOutsideClick } from "../../../../../utils";
import React, { useState } from "react";
import { IoIosArrowDown } from "@react-icons/all-files/io/IoIosArrowDown";
import { IFilterLayout } from "../../../../../types/mainPage.types";
import { useFormikContext } from "formik";
import Field, { fieldLabel } from "../../../search/field/Field";

interface IProps {
  info: IFilterLayout;
  className?: string;
}

const FieldSection = (props: IProps) => {
  const [showField, setShowField] = useState(false);
  const ref: React.RefObject<HTMLDivElement> = React.createRef();
  const { values } = useFormikContext<{}>();

  useOutsideClick(ref, () => setShowField(false));

  return (
    <div className={`${styles.container} ${props.className}`} ref={ref}>
      <div className={styles.title} onClick={() => setShowField(!showField)}>
        <span>{props.info.title}</span>
        <IoIosArrowDown className={showField ? styles.active : ""} />
      </div>
      {showField && (
        <div className={styles["field-container"]}>
          {props.info.filters.map((item, i) => {
            const value: any = getFromOjectByKey(values, item.code);
            if (item.range_search)
              return (
                <div
                  className={`w-100 ${styles["input-main-container"]}`}
                  key={i}
                >
                  <span className={styles.label}>
                    {fieldLabel(item, value)}
                  </span>
                  <div className={styles["input-container"]}>
                    <Field
                      info={{
                        ...item,
                        label: "حداقل",
                        code: `${item.code}[from]`,
                      }}
                    />
                    <Field
                      info={{
                        ...item,
                        label: "حداکثر",
                        code: `${item.code}[to]`,
                      }}
                    />
                  </div>
                </div>
              );
            return <Field info={item} key={i} />;
          })}
        </div>
      )}
    </div>
  );
};

export default FieldSection;
