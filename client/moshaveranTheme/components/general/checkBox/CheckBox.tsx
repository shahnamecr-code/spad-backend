import React from "react";
import styles from "./checkBox.module.scss";

interface IProps {
  checked?: boolean;
  onChange?: (event: React.ChangeEvent<HTMLInputElement>) => void;
  label?: string;
  color?: string;
  backgroundColor?: string;
  readOnly?: boolean;
  disabled?: boolean;
  className?: string;
}

const CheckBox = (props: IProps) => {
  return (
    <div
      className={`${styles.container} ${props.className}`}
      style={{ opacity: props.disabled ? 0.5 : 1 }}
    >
      <input
        type="checkbox"
        className={`${styles['check-box']}`}
        id={props.label}
        checked={props.checked}
        onChange={props.onChange}
        readOnly={props.readOnly}
        disabled={props.disabled}
      />
      <label htmlFor={props.label} className={styles.label}>
        {props.label}
      </label>
    </div>
  );
};

export default CheckBox;
