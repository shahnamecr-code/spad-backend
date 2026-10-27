/* eslint-disable @next/next/no-img-element */
import { useRouter } from "next/router";
import React, { useEffect, useState } from "react";
import { Lazy, Navigation } from "swiper";
import { Swiper, SwiperSlide } from "swiper/react";
import Button from "../button/Button";
import { useFavoriteDev } from "./favorite.dev";
import Skeleton from "react-loading-skeleton";
import "react-loading-skeleton/dist/skeleton.css";
import { HiHeart } from "@react-icons/all-files/hi/HiHeart";
import { HiOutlineHeart } from "@react-icons/all-files/hi/HiOutlineHeart";
import { HiShare } from "@react-icons/all-files/hi/HiShare";
import { IAd } from "../../../types/ads.types";
import styles from "./adCard.module.scss";

interface IProps {
  adInfo?: IAd;
  showShareAd?: () => void;
}

const AdCard = (props: IProps) => {
  const router = useRouter();
  const dev = useFavoriteDev(props.adInfo?.id || 0);
  const [showHover, setShowHover] = useState(false);
  const [height, setHeight] = useState<number | undefined>(0);

  useEffect(() => {
    setHeight(props.adInfo ? undefined : 0);
  }, []);

  useEffect(() => {
    dev.checkIsFavorite();
  }, [showHover]);

  return (
    <div className={styles.space}>
      <div className={styles.container} style={{ minHeight: height }}>
        <section
          onMouseEnter={() => props.adInfo && setShowHover(true)}
          onMouseLeave={() => setShowHover(false)}
        >
          <div
            className={styles["hover-container"]}
            style={{ visibility: showHover ? "visible" : "hidden" }}
          >
            <div
              className={styles["hover-btns"]}
              style={{
                marginInlineStart: showHover ? 0 : 70,
              }}
            >
              <Button type="text" onClick={dev.toggleFavorite}>
                {dev.favorite ? <HiHeart /> : <HiOutlineHeart />}
              </Button>
              <Button type="text" onClick={props.showShareAd}>
                <HiShare />
              </Button>
            </div>
          </div>
          <div className={styles["action-container-mobile"]}>
            <div className={styles["hover-btns"]}>
              <Button type="text" onClick={dev.toggleFavorite}>
                {dev.favorite ? <HiHeart /> : <HiOutlineHeart />}
              </Button>
              <Button type="text" onClick={props.showShareAd}>
                <HiShare />
              </Button>
            </div>
          </div>
          <div
            className={`${styles["gallery-container"]}`}
            style={{
              filter: showHover ? " brightness(0.8)" : " brightness(1)",
            }}
          >
            {props.adInfo?.gallery ? (
              <Swiper
                navigation={true}
                modules={[Navigation, Lazy]}
                lazy={{
                  enabled: true,
                  elementClass: "swiper-lazy2",
                }}
                preloadImages={false}
              >
                {props.adInfo?.gallery.map((img, i) => (
                  <SwiperSlide
                    style={{ borderRadius: 14, transform: "none" }}
                    key={i}
                  >
                    <img
                      src=""
                      data-src={img}
                      style={{ height: 190, width: "100%" }}
                      className="object-fit-cover swiper-lazy2"
                      alt={props.adInfo?.title}
                    />
                    <div className="swiper-lazy-preloader swiper-lazy-preloader-white"></div>
                  </SwiperSlide>
                ))}
              </Swiper>
            ) : props.adInfo?.image ? (
              <>
                <img
                  src=""
                  data-src={props.adInfo?.image}
                  style={{ height: 190, width: "100%" }}
                  className="object-fit-cover swiper-lazy"
                  alt={props.adInfo?.title}
                />
                <div className="swiper-lazy-preloader swiper-lazy-preloader-white"></div>
              </>
            ) : (
              <Skeleton height={190} width="100%" />
            )}
            {props.adInfo && (
              <div className={styles.code}>کد: {props.adInfo?.id}</div>
            )}
            {props.adInfo?.fast && <div className={styles.fast}>فوری</div>}
            {props?.adInfo?.netting && (
              <div className={styles.netting}>معاوضه</div>
            )}
          </div>
        </section>

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
            <h4 className={styles.title}>{props.adInfo?.title}</h4>
          ) : (
            <Skeleton height={10} />
          )}
          <div className={styles["main-props-container"]}>
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
          <div className={styles["main-props-container"]}>
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
        </div>
      </div>
    </div>
  );
};

export default AdCard;
