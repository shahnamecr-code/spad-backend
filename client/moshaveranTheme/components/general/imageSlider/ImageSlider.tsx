/* eslint-disable @next/next/no-img-element */
import { useState } from "react";
import { AiOutlineClose } from "@react-icons/all-files/ai/AiOutlineClose";
import { AiOutlineDownload } from "@react-icons/all-files/ai/AiOutlineDownload";
import { FreeMode, Navigation, Thumbs } from "swiper";
import { Swiper, SwiperSlide } from "swiper/react";
import { Swiper as SwiperClass } from "swiper/types/";
import Button from "../button/Button";
import styles from "./imageSlider.module.scss";

interface IProps {
  visible?: boolean;
  onHiding?: () => void;
  images: any[];
}

const ImageSlider = (props: IProps) => {
  const [swiper, setSwiper] = useState<SwiperClass>();
  const [activeIndex, setActiveIndex] = useState(0);

  return props.visible ? (
    <div className={styles.container}>
      <div className={styles["btns-container"]}>
        <div className="w-100 flex-c">
          <Button
            type="text"
            onClick={props.onHiding}
            className={styles["close-btn"]}
          >
            <AiOutlineClose />
          </Button>
          <a
            target="_blank"
            href={props.images[activeIndex]}
            className={styles["download-btn"]}
            rel="noreferrer"
          >
            <AiOutlineDownload />
          </a>

          <span className={styles["imgs-count"]}>
            {activeIndex + 1} / {props.images.length}
          </span>
        </div>
      </div>
      <div className={styles["swiper-container"]}>
        <Swiper
          spaceBetween={10}
          navigation={true}
          onSwiper={setSwiper}
          modules={[FreeMode, Navigation, Thumbs]}
          style={{ marginBottom: 24 }}
          centeredSlides={true}
          onActiveIndexChange={(swiper) => setActiveIndex(swiper.activeIndex)}
          autoHeight
        >
          {props.images.map((img, i) => (
            <SwiperSlide key={i}>
              <img className={styles["swiper-img"]} src={img} alt="" />
            </SwiperSlide>
          ))}
        </Swiper>
        <div className={styles["bottom-container"]}>
          {props.images.map((img, i) => (
            <div
              key={i}
              className={`${styles["img-container"]} ${
                activeIndex === i ? styles.active : ""
              }`}
              onClick={() => {
                if (swiper) swiper.slideTo(i);
              }}
            >
              <img src={img} className={styles.img} alt="" />
            </div>
          ))}
        </div>
      </div>
    </div>
  ) : null;
};

export default ImageSlider;
