/* eslint-disable @next/next/no-img-element */
import { useEffect, useState } from "react";
import { useAppContext } from "../../../utils/context";
import styles from "./newLoading.module.scss";

interface IProps {
  isVisible?: boolean;
}

const NewLoading = (props: IProps) => {
  const context = useAppContext();

  const [visible, setVisible] = useState(false);

  useEffect(() => {
    if (props.isVisible) {
      setTimeout(() => {
        setVisible(true);
      }, 800);
    } else setVisible(false);
  }, [props.isVisible]);

  return (
    <div
      className={`${styles.container} 
      ${props.isVisible ? "" : styles["d-none"]}
      `}
    >
      <div
        className={`${!props.isVisible ? styles["loader-toggle-top"] : ""} ${
          styles["screen-loader-top"]
        }`}
      ></div>
      {visible && (
        <div
          className={`${styles["items-container"]} 
      ${props.isVisible ? "" : styles["d-none"]}
      `}
        >
          {context.options.general_logo && (
            <img
              src={context.options.general_logo}
              alt={context.options.general_site_name}
              className={styles.logo_container}
              width={220}
            />
          )}

          <div className={styles["progress"]}>
            <div className={styles["color"]}></div>
          </div>
        </div>
      )}
      <div
        className={`${!props.isVisible ? styles["loader-toggle-bottom"] : ""} ${
          styles["screen-loader-bottom"]
        }`}
      ></div>
    </div>
  );
};

export default NewLoading;
