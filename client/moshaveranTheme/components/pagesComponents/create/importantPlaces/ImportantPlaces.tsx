import { BsTrash } from "@react-icons/all-files/bs/BsTrash";
import React, { useState } from "react";
import { deepCopy, getFromOjectByKey } from "../../../../utils";
import Button from "../../../general/button/Button";
import Input from "../../../general/input/Input";
import { Select } from "../../../general/select/Select";
import styles from "./importantPlaces.module.scss";

interface IProps {
  values: any;
  setFieldValue: (
    field: string,
    value: any,
    shouldValidate?: boolean | undefined
  ) => void;
  errMsgs?: { [key: string]: string[] };
  options?: {
    label?: string | undefined;
    value?: string | undefined;
    icon?: string | undefined;
  }[];
}

const ImportantPlaces = (props: IProps) => {
  const [count, setCount] = useState(1);

  return (
    <div className={styles.container}>
      {Array.from({ length: count }).map((_, i) => (
        <div className={styles["place-container"]} key={i}>
          <div className={styles["input-container"]}>
            <Select
              id={`palce-${i}`}
              label="مرکز"
              onChange={(e) => {
                props.setFieldValue(`important_places[${i}].place`, e);
              }}
              value={
                getFromOjectByKey(
                  props.values,
                  `important_places[${i}].place`
                ) as any
              }
              valueKey="label"
              items={props.options}
              className={"col-md-6"}
              formatOptionLabel={(data) => (
                <div className={styles["select-item"]}>
                  {/* <img src={imgUrl(data.icon)} /> */}
                  <span>{data.label}</span>
                </div>
              )}
            />
            <Input
              label="فاصله (دقیقه)"
              value={
                getFromOjectByKey(
                  props.values,
                  `important_places[${i}].distance`
                ) as any
              }
              onChange={(e) =>
                props.setFieldValue(`important_places[${i}].distance`, e)
              }
              className={"col-md-6"}
              type="number"
            />
          </div>
          <Button
            type="text"
            onClick={() => {
              const important_places = deepCopy(
                props.values.important_places || []
              );
              important_places.splice(i, 1);
              props.setFieldValue("important_places", important_places);
              setCount((prev) => prev - 1);
            }}
          >
            <BsTrash fontSize={20} />
          </Button>
        </div>
      ))}
      <Button
        onClick={() => setCount((prev) => prev + 1)}
        type="outlined"
        width={80}
      >
        افزودن
      </Button>
      <div className="divider" />
    </div>
  );
};

export default ImportantPlaces;
