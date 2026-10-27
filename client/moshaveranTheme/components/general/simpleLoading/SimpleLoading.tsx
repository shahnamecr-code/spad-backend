import { useEffect } from "react";
import LoadingGif from "../loadingGif/LoadingGif";
import styles from "./simpleLoading.module.scss";

interface IProps {
  inContainer?: boolean;
}

const SimpleLoading = (props: IProps) => {
  return (
    <div
      className={styles.container}
      style={{ position: props.inContainer ? "absolute" : "fixed" }}
    >
      <LoadingGif />
    </div>
  );
};

export default SimpleLoading;
