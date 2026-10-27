/* eslint-disable @next/next/no-img-element */
import React from "react";
import { ISingleAd } from "../../../../types/ads.types";
import styles from "./widgets.module.scss";
import { MdCheckCircle } from "@react-icons/all-files/md/MdCheckCircle";

interface IProps {
  item: ISingleAd["widgets"][number];
}

const SinglePageWidgets = ({ item }: IProps) => {
  switch (item.widget_type) {
    case "default":
      let splitedArray: string[] = [];
      let answ = item.info?.value?.split("\n");
      answ?.forEach(function (obj) {
        splitedArray.push(obj);
      });
      if (!item.info?.value) return <></>;
      return (
        <div>
          <div className={styles["default-widgets__container"]}>
            <div className={styles.divider} />
            <h2 className={styles["sub-title"]}>{item.info?.title}</h2>
            {splitedArray.length ? (
              splitedArray.map((row, i) => <p key={i}>{row}</p>)
            ) : (
              <p>{item.info?.value}</p>
            )}
          </div>
        </div>
      );
    case "group":
      if (item.front_layout == "layout1") {
        return (
          <div>
            <div className={styles.divider} />
            <h2 className={styles["sub-title"]}>{item.title}</h2>
            <div className={styles["ad-info-container"]}>
              {item.items?.map((item, i) => {
                if (item.info) {
                  return (
                    <div key={i} className={styles.item}>
                      <span className={styles.name}>
                        {item.info?.icon && (
                          <img
                            src={item.info?.icon}
                            alt={item.info.title}
                            width={24}
                            height={24}
                          />
                        )}
                        {item.info?.title}
                      </span>
                      <span className={styles.value}>{item.info?.value}</span>
                    </div>
                  );
                }
              })}
            </div>
          </div>
        );
      } else if (item.front_layout == "layout2") {
        return (
          <div>
            <div className={styles.divider} />
            <h2 className={styles["sub-title"]}>{item.title}</h2>
            <div className={styles["facilities-container"]}>
              {item.items?.map((item, i) => (
                <div key={i} className={styles.item}>
                  {item.info?.icon ? (
                    <img src={item.info?.icon} alt={item.info.title} width={24} height={24} />
                  ) : (
                    <MdCheckCircle fontSize={16} />
                  )}
                  <span>{item.info?.title}</span>
                </div>
              ))}
            </div>
          </div>
        );
      }
    case "vertical":
      return (
        <div>
          <div className={styles.divider} />
          <div className={styles["vertical-widgets__container"]}>
            {item.items?.map((item, i) => (
              <div key={i} className={styles["vertical-widgets__item"]}>
                <h4>{item.info?.title}</h4>
                <p>{item.info?.value}</p>
              </div>
            ))}
          </div>
        </div>
      );
  }

  return null;
};

export default SinglePageWidgets;
