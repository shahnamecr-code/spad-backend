import React from "react";
import styles from "./input.module.scss";

export interface IProps {
  placeHolder?: string;
  width?: string | number;
  height?: string | number;
  className?: string;
  inputClassName?: string;
  value?: string;
  label?: string;
  name?: string;
  type?: "number" | "string";
  id?: string;
  onChange?: (value: string) => void;
  validationMessage?: string;
}

const Input = (props: IProps) => {
  return (
    <div
      className={`${styles.container} ${props.className}`}
      style={{
        width: props.width,
      }}
    >
      {props.label && <span className={styles.label}>{props.label}</span>}{" "}
      <input
        id={props.id || "input"}
        name={props.name}
        className={props.inputClassName}
        type={props.type}
        placeholder={props.placeHolder}
        value={props.value || ""}
        onChange={(e) => {
          props.onChange && props.onChange(e.target.value);
        }}
      />
      {props.validationMessage && (
        <div className="validation-message">{props.validationMessage}</div>
      )}
    </div>
  );
};

export default Input;
