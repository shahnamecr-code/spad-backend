import { isNumber } from "../../../../utils";
import styles from "./loginInput.module.scss";

interface IProps {
  value?: string | number;
  onChange?: (value: string | number) => void;
  validationMessage?: string;
  placeholder?: string;
  type?: "string" | "number" | "password";
}

const LoginInput = (props: IProps) => {
  return (
    <div className={styles.container}>
      <input
        value={props.value || ""}
        onChange={(e) => {
          if (props.type === "number" && !isNumber(e.target.value)) {
            e.preventDefault();
            return;
          }
          props.onChange && props.onChange(e.target.value);
        }}
        className={styles.input}
        placeholder={props.placeholder}
        type={props.type}
      />
      {props.validationMessage && (
        <div className={styles["validation-message"]}>
          {props.validationMessage}
        </div>
      )}
    </div>
  );
};

export default LoginInput;
