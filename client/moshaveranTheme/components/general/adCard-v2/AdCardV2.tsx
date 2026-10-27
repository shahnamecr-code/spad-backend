/* eslint-disable @next/next/no-img-element */
import { useRouter } from "next/router";
import React, { useEffect, useState } from "react";
import { Lazy, Navigation } from "swiper";
import { Swiper, SwiperSlide } from "swiper/react";
import Button from "../button/Button";
import Popup from "../popup/Popup";
import Skeleton from "react-loading-skeleton";
import "react-loading-skeleton/dist/skeleton.css";
import { HiHeart } from "@react-icons/all-files/hi/HiHeart";
import { HiOutlineHeart } from "@react-icons/all-files/hi/HiOutlineHeart";
import { HiShare } from "@react-icons/all-files/hi/HiShare";
import { IAd } from "../../../types/ads.types";
import styles from "./adCard-v2.module.scss";
import { useFavoriteDev } from "../adCard/favorite.dev";
import ShareAd from "../shareAd/ShareAd";

interface IProps {
  adInfo?: IAd;
}

const AdCardV2 = (props: IProps) => {
  const router = useRouter();
  const dev = useFavoriteDev(props.adInfo?.id || 0);
  const [showHover, setShowHover] = useState(false);
  const [showShare, setShowShare] = useState(false);

  useEffect(() => {
    dev.checkIsFavorite();
  }, [showHover]);

  return (
    <div className={styles.space}>
      <div className={styles.container}>
        {props.adInfo?.gallery ? (
          <div
            onMouseEnter={() => setShowHover(true)}
            onMouseLeave={() => setShowHover(false)}
            className={styles["gallery-section"]}
          >
            <div
              className={styles["hover-container"]}
              style={{
                marginRight: showHover ? "45%" : "100%",
                visibility: showHover ? "visible" : "hidden",
                transition: "0.4s all",
              }}
            >
              <div className={styles["hover-btns"]}>
                <Button type="text" onClick={dev.toggleFavorite}>
                  {dev.favorite ? <HiHeart /> : <HiOutlineHeart />}
                </Button>
                <Button type="text" onClick={() => setShowShare(true)}>
                  <HiShare />
                </Button>
              </div>
            </div>
            <div className={styles["action-container-mobile"]}>
              <div className={styles["hover-btns"]}>
                <Button type="text" onClick={dev.toggleFavorite}>
                  {dev.favorite ? <HiHeart /> : <HiOutlineHeart />}
                </Button>
                <Button type="text" onClick={() => setShowShare(true)}>
                  <HiShare />
                </Button>
              </div>
            </div>
            <div
              className={styles["gallery-container"]}
              style={{
                filter: showHover ? " brightness(0.8)" : " brightness(1)",
              }}
            >
              <Swiper
                navigation={true}
                modules={[Navigation, Lazy]}
                lazy={{
                  enabled: true,
                }}
                preloadImages={false}
                style={{ height: "100%" }}
              >
                {props.adInfo?.gallery.map((img, i) => (
                  <SwiperSlide style={{ borderRadius: 14 }} key={i}>
                    <img
                      src=""
                      data-src={img}
                      style={{ width: "100%" }}
                      className="object-fit-cover swiper-lazy"
                      alt={props.adInfo?.title}
                    />
                    <div className="swiper-lazy-preloader swiper-lazy-preloader-white"></div>
                  </SwiperSlide>
                ))}
              </Swiper>
              <div className={styles.code}>کد: {props.adInfo?.id}</div>
              {props.adInfo?.fast && <div className={styles.fast}>فوری</div>}
              {props.adInfo?.special && <div className={styles.lux}>ویژه</div>}
              {props?.adInfo?.netting && (
                <div className={styles.netting}>معاوضه</div>
              )}
            </div>
          </div>
        ) : (
          <Skeleton height={180} width="100%" borderRadius={16} />
        )}

        <div
          className={styles["info-container"]}
          onClick={() => router.push(`/ads/${props.adInfo?.id}`)}
        >
          {props.adInfo ? (
            <h4 className={styles.title}>{props.adInfo?.title}</h4>
          ) : (
            <Skeleton height={10} />
          )}
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
          </div>

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
          {props.adInfo ? (
            props.adInfo.secondary_props?.map((prop, i) => (
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
        <Popup
          width={350}
          visible={showShare}
          onHiding={() => setShowShare(false)}
          title="اشتراک گذاری ملک"
        >
          <ShareAd adInfo={props.adInfo} />
        </Popup>
      </div>
    </div>
  );
};

export default AdCardV2;
