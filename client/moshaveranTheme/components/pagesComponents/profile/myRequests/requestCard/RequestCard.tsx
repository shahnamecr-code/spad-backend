/* eslint-disable @next/next/no-img-element */
import styles from "./requestCard.module.scss";
import Button from "../../../../general/button/Button";
import { IoMdMore } from "@react-icons/all-files/io/IoMdMore";
import Popup from "../../../../general/popup/Popup";
import { useState } from "react";
import {
  IMyRequest,
  IMyRequestProperty,
} from "../../../../../types/profile.types";

interface IProps {
  info: IMyRequest;
}

const RequestCard = (props: IProps) => {
  const [showDetail, setShowDetail] = useState(false);

  const renderValue = (item: IMyRequestProperty) => {
    switch (item.search_type) {
      case "boolean":
      case "switch":
        return item.value ? "دارد" : "ندارد";
      case "multi-select":
        return item.value instanceof Array
          ? item.value?.join(" - ")
          : typeof item.value === "string" && item.value;
      default:
        return typeof item.value === "string" && item.value;
    }
  };

  return (
    <div className={styles.container}>
      <span>{props.info.date} </span>
      <div className={styles["info-container"]}>
        <div className="flex-c w-100">
          {/* <span className={styles.title}>{props.info.title}</span> */}
          <span className={styles.code}>
            کد درخواست: <span className={styles.primary}>{props.info.id}</span>
          </span>
          <div className={styles["btn-container"]}>
            <Button
              type="text"
              title="جزئیات"
              color="gray"
              onClick={() => setShowDetail(true)}
            >
              <IoMdMore />
            </Button>
          </div>
        </div>
        <span className={styles.region}>
          {[props.info.city, ...props.info.regions].join(" ، ")}
        </span>
      </div>
      <Popup
        onHiding={() => setShowDetail(false)}
        title="جزئیات درخواست"
        visible={showDetail}
        width={500}
      >
        <div className={styles["items-container"]}>
          {!props.info.properties?.length ? (
            <span>(اطلاعاتی وجود ندارد)</span>
          ) : (
            props.info.properties?.map((item, i) => (
              <div className={styles["property-container"]} key={i}>
                <div className={styles["property-title-container"]}>
                  <span className={styles["property-title"]}>{item.label}</span>
                  {item.icon && (
                    <img
                      src={item.icon}
                      width={20}
                      height={20}
                      alt={item.label}
                    />
                  )}
                </div>
                {item.items?.map((item, i) => (
                  <div key={i} className={styles["item-container"]}>
                    <span className={styles["item-title"]}>{item.label}</span>
                    {item.range_search &&
                    !(item.value instanceof Array) &&
                    typeof item.value !== "string" &&
                    (item.value?.from || item.value?.to) ? (
                      <span className={styles["item-value"]}>
                        {renderValue({
                          ...item,
                          value: item.value?.from,
                        }) || " - "}
                        <span> الی </span>
                        {renderValue({
                          ...item,
                          value: item.value?.to,
                        }) || " - "}
                      </span>
                    ) : (
                      <span className={styles["item-value"]}>
                        {renderValue(item)}
                      </span>
                    )}
                  </div>
                ))}
              </div>
            ))
          )}
        </div>
      </Popup>
    </div>
  );
};

export default RequestCard;
