import { IFilter } from "../../../../types/mainPage.types";
import { getFromOjectByKey } from "../../../../utils";
import Field, { fieldLabel } from "../field/Field";
import styles from "./fieldsBox.module.scss";

interface IProps {
  boxInfo: {
    items: IFilter[];
    icon?: string | undefined;
    label?: string | undefined;
  };
  values: any;
}

const FieldsBox = (props: IProps) => {
  return (
    <div className={styles.container}>
      <div className={styles["label-container"]}>
        <h2>{props.boxInfo.label}</h2>
        {/* {props.boxInfo.icon && (
          <img src={imgUrl(props.boxInfo.icon)} width={20} height={20} />
        )} */}
      </div>
      <div className="divider" />
      <div className={styles["items-container"]}>
        {props.boxInfo.items?.map((item, i) => {
          const value: any = getFromOjectByKey(props.values, item.code);
          if (item.range_search)
            return (
              <div
                className={`w-100 ${styles["input-main-container"]}`}
                key={i}
              >
                <span className={styles.label}>{fieldLabel(item, value)}</span>
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
    </div>
  );
};

export default FieldsBox;
