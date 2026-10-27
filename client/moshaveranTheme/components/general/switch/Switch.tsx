import styles from "./switch.module.scss";

interface IProps {
  label: string;
  value?: boolean;
  onChange?: (value: boolean) => void;
}

const Switch = (props: IProps) => {
  return (
    <div className={styles.container}>
      <span className={styles.label}>{props.label}</span>
      <input
        className={styles.switch}
        type="checkbox"
        checked={props.value}
        onChange={() => props.onChange && props.onChange(!props.value)}
      />
    </div>
  );
};

export default Switch;
