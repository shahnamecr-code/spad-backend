/* eslint-disable @next/next/no-img-element */
import Image from "next/image";
import { useRouter } from "next/router";
import React from "react";
import { Navigation } from "swiper";
import { Swiper, SwiperSlide } from "swiper/react";
import Skeleton from "react-loading-skeleton";
import "react-loading-skeleton/dist/skeleton.css";
import { IAd } from "../../../../../types/ads.types";
import styles from "./recentAdCard.module.scss";

interface IProps {
  adInfo?: IAd;
}

const RecentAdCard = (props: IProps) => {
  const router = useRouter();

  return (
    <div className={styles.space}>
      <div className={styles.container}>
        {props.adInfo?.gallery ? (
          <section>
            <div className={`${styles["gallery-container"]}`}>
              <h2 className={styles.code}>کد: {props.adInfo?.id}</h2>
              {props.adInfo?.fast && <div className={styles.fast}>فوری</div>}
              {props?.adInfo?.netting && (
                <div className={styles.netting}>معاوضه</div>
              )}
              <Swiper
                navigation={true}
                modules={[Navigation]}
                preloadImages={false}
              >
                {props.adInfo?.gallery.map((img, i) => (
                  <SwiperSlide
                    style={{ borderRadius: 14, transform: "none" }}
                    key={i}
                  >
                    <img
                      src={img}
                      style={{ width: "100%", height: 130 }}
                      className="object-fit-cover"
                      alt={props.adInfo?.title}
                    />
                  </SwiperSlide>
                ))}
              </Swiper>
            </div>
          </section>
        ) : (
          <Skeleton height={180} width="100%" borderRadius={16} />
        )}

        <div
          className={styles["info-container"]}
          onClick={() => router.push(`/ads/${props.adInfo?.id}`)}
        >
          <div className={styles["sub-title"]}>
            <span>
              {props.adInfo ? (
                `${[
                  props.adInfo?.date,
                  props.adInfo?.city,
                  props.adInfo?.region,
                ].join(" - ")}`
              ) : (
                <Skeleton width="50%" height={10} />
              )}
            </span>
            {props.adInfo?.special && <div className={styles.lux}>ویژه</div>}
          </div>
          {props.adInfo ? (
            <h3 className={styles.title}>{props.adInfo?.title}</h3>
          ) : (
            <Skeleton height={10} />
          )}
          {props.adInfo ? (
            props.adInfo.main_props?.map((prop, i) => (
              <div className={styles["main-prop"]} key={i}>
                <span key={i} className={styles.title}>
                  {prop.title}:
                </span>
                <div className="flex">
                  <span className={styles.value}>{` ${prop.value} `}</span>
                  <span className={styles.unit}>{prop.unit}</span>
                </div>
              </div>
            ))
          ) : (
            <div style={{ textAlign: "start" }}>
              <Skeleton width="50%" height={10} />
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default RecentAdCard;
