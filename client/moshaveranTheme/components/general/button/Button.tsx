import React, { ReactElement, ReactNode } from "react";
import styles from "./button.module.scss";

interface IProps {
  color?: "primary" | "white" | "gray" | "dark";
  type?: "text" | "outlined" | "contained";
  children?: ReactNode;
  className?: string;
  width?: string | number;
  height?: string | number;
  onClick?: React.MouseEventHandler<HTMLButtonElement>;
  style?: React.CSSProperties;
  btnType?: "button" | "reset" | "submit";
  disabled?: boolean;
  title?: string;
}

const Button = ({
  type = "contained",
  children,
  className,
  color = "primary",
  width,
  height,
  style,
  onClick,
  btnType = "button",
  disabled,
  title,
}: IProps) => {
  return (
    <button
      className={`${styles.button} ${styles[type + "-" + color]} ${className}`}
      style={{ width: width, height: height, ...style }}
      onClick={onClick}
      type={btnType}
      disabled={disabled}
      title={title}
    >
      {children}
    </button>
  );
};

export default Button;
