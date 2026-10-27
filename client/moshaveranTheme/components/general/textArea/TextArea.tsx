import React from "react";
import styles from "./textArea.module.scss";

export interface IProps {
  placeHolder?: string;
  icon?: any;
  width?: string | number;
  height?: string | number;
  className?: string;
  inputClassName?: string;
  value?: string;
  label?: string;
  name?: string;
  hideIconBg?: boolean;
  id?: string;
  onChange?: (value: string) => void;
  rows?: number;
  style?: React.CSSProperties;
  validationMessage?: string;
}

const TextArea = (props: IProps) => {
  return (
    <div
      className={`${styles.container} ${props.className}`}
      style={{
        width: props.width,
        ...props.style,
      }}
    >
      {props.label && <span className={styles.label}>{props.label}</span>}{" "}
      <textarea
        id={props.id || "input"}
        name={props.name}
        className={props.inputClassName}
        placeholder={props.placeHolder}
        value={props.value || ""}
        onChange={(e) => {
          props.onChange && props.onChange(e.target.value);
        }}
        rows={props.rows || 3}
      />
      {props.validationMessage && (
        <div className="validation-message">{props.validationMessage}</div>
      )}
    </div>
  );
};

export default TextArea;
