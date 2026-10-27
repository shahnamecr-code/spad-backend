import React, { ReactElement, useEffect } from "react";
import { AiOutlineClose } from "@react-icons/all-files/ai/AiOutlineClose";
import Button from "../button/Button";
import styles from "./popup.module.scss";

export interface IPopupProps {
  visible?: boolean;
  onHiding: () => void;
  width?: string | number;
  height?: string | number;
  children?: ReactElement;
  title?: string;
  hideCloseBtn?: boolean;
  overflow?: string;
}

const Popup = (props: IPopupProps) => {
  useEffect(() => {
    if (props.visible) {
      document.body.style.overflowY = "hidden";
    } else document.body.style.overflowY = "auto";

    const els = document.getElementsByClassName("swiper-wrapper");
    Array.from(els).map((item) => {
      item.className = props.visible
        ? "swiper-wrapper popup-visible"
        : "swiper-wrapper";
    });
  }, [props.visible]);

  return props.visible ? (
    <div className={styles.container}>
      <div
        className={styles.main}
        style={{
          width: props.width || 200,
          height: props.height,
          overflow: props.overflow ? props.overflow : "auto",
        }}
      >
        <div className={styles["title-container"]}>
          {renderPopupTitle(props.title || "")}
          {!props.hideCloseBtn && (
            <Button type="text" onClick={props.onHiding}>
              <AiOutlineClose />
            </Button>
          )}
        </div>
        {props.children}
      </div>
    </div>
  ) : null;
};

export default Popup;

export const renderPopupTitle = (title: string) => (
  <h2 className={styles.title}>{title}</h2>
);
