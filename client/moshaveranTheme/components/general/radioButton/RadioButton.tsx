import styles from "./radioButton.module.scss";

interface IRadioButton {
  checked?: boolean;
  onChange?: (event: React.ChangeEvent<HTMLInputElement>) => void;
  label?: string;
  color?: string;
  backgroundColor?: string;
  readOnly?: boolean;
  disabled?: boolean;
  className?: string;
}

export const RadioButton: React.FC<IRadioButton> = (props) => {
  return (
    <div
      className={`${styles.container} ${props.className}`}
      style={{ opacity: props.disabled ? 0.5 : 1 }}
    >
      <input
        type="radio"
        className={`${styles.radio}`}
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
