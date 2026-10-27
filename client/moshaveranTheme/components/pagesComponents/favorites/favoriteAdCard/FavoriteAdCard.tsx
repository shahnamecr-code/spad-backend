/* eslint-disable @next/next/no-img-element */
import Image from "next/image";
import { useRouter } from "next/router";
import React, { useState } from "react";
import { Navigation } from "swiper";
import { Swiper, SwiperSlide } from "swiper/react";
import Skeleton from "react-loading-skeleton";
import "react-loading-skeleton/dist/skeleton.css";
import styles from "./favoriteAdCard.module.scss";
import { HiShare } from "@react-icons/all-files/hi/HiShare";
import { HiTrash } from "@react-icons/all-files/hi/HiTrash";
import { IAd } from "../../../../types/ads.types";
import ShareAd from "../../../general/shareAd/ShareAd";
import Popup from "../../../general/popup/Popup";
import Button from "../../../general/button/Button";
import MessageBox from "../../../general/messageBox/MessageBox";

interface IProps {
  adInfo?: IAd;
  onClickDelete: (adId: number) => void;
}

const FavoriteAdCard = (props: IProps) => {
  const router = useRouter();
  const [showShare, setShowShare] = useState(false);
  const [showDeleteMsgBox, setShowDeleteMsgBox] = useState(false);

  return (
    <div className={styles.space}>
      <div className={styles.container}>
        <section>
          <div className={`${styles["gallery-container"]}`}>
            <div className={styles.code}>کد: {props.adInfo?.id}</div>
            {props.adInfo?.fast && <div className={styles.fast}>فوری</div>}
            {props?.adInfo?.netting && (
              <div className={styles.netting}>معاوضه</div>
            )}
            {props.adInfo?.gallery ? (
              <>
                <Swiper
                  navigation={true}
                  modules={[Navigation]}
                  preloadImages={false}
                >
                  {props.adInfo?.gallery?.map((img, i) => (
                    <SwiperSlide
                      style={{ borderRadius: 14, transform: "none" }}
                      key={i}
                    >
                      <Image
                        src={img}
                        height={190}
                        style={{ width: "100%" }}
                        className="object-fit-cover"
                        alt={props.adInfo?.title}
                      />
                    </SwiperSlide>
                  ))}
                </Swiper>
              </>
            ) : props.adInfo?.image ? (
              <img
                src={props.adInfo?.image}
                style={{ height: 190, width: "100%" }}
                className="object-fit-cover"
                alt={props.adInfo?.title}
              />
            ) : (
              <Skeleton height={180} width="100%" borderRadius={16} />
            )}{" "}
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
            <h2 className={styles.title}>{props.adInfo?.title}</h2>
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
                </div>
              </div>
            ))
          ) : (
            <div style={{ textAlign: "start" }}>
              <Skeleton width="50%" height={10} />
            </div>
          )}
          <div className={styles["btns-container"]}>
            <Button
              type="outlined"
              color="gray"
              onClick={(e) => {
                e.stopPropagation();
                setShowShare(true);
              }}
            >
              <HiShare />
              اشتراک گذاری
            </Button>
            <Button
              color="gray"
              onClick={(e) => {
                e.stopPropagation();
                setShowDeleteMsgBox(true);
              }}
            >
              <HiTrash />
              حذف
            </Button>
          </div>
        </div>
      </div>
      {showDeleteMsgBox && (
        <MessageBox
          visible={showDeleteMsgBox}
          onConfirm={() => {
            props.onClickDelete(props.adInfo?.id || 0);
            setShowDeleteMsgBox(false);
          }}
          onHiding={() => setShowDeleteMsgBox(false)}
        />
      )}
      <Popup
        width={350}
        visible={showShare}
        onHiding={() => setShowShare(false)}
        title="اشتراک گذاری ملک"
      >
        <ShareAd adInfo={props.adInfo} />
      </Popup>
    </div>
  );
};

export default FavoriteAdCard;
