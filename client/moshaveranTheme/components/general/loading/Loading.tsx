/* eslint-disable @next/next/no-img-element */
import { useEffect } from "react";
import { useAppContext } from "../../../utils/context";
import LoadingGif from "../loadingGif/LoadingGif";
import styles from "./loading.module.scss";

interface IProps {
  isVisible?: boolean;
}

const Loading = (props: IProps) => {
  const context = useAppContext();
  useEffect(() => {
    // document.body.style.overflowY = "hidden";
    return () => {
      document.body.style.overflowY = "auto";
    };
  }, []);

  return (
    <div className={`${props.isVisible ? styles.container : styles.z_index} `}>
      <div className={`flex ${styles.div}`}>
        <div
          className={`${styles.div} ${
            !props.isVisible ? styles.open_right : ""
          }`}
        ></div>
        <div
          className={`${styles.div} ${
            !props.isVisible ? styles.open_left : ""
          }`}
        ></div>
      </div>
      {props.isVisible && (
        <div className={`${styles.div} ${styles.image_container}`}>
          {context.options.general_logo && (
            <img
              src={context.options.general_logo}
              alt={context.options.general_site_name}
              className={styles.logo_container}
            />
          )}
          <LoadingGif />
        </div>
      )}
    </div>
  );
};

export default Loading;
