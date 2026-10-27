import Link from "next/link";
import styles from "./swiperSection.module.scss";
import { IoIosArrowBack } from "@react-icons/all-files/io/IoIosArrowBack";
import { IoIosArrowForward } from "@react-icons/all-files/io/IoIosArrowForward";
import { Lazy, Navigation } from "swiper";
import { Swiper, SwiperSlide } from "swiper/react";
import AdCard from "../../../general/adCard/AdCard";
import Button from "../../../general/button/Button";
import { IAd } from "../../../../types/ads.types";
import SectionTitle from "../../../general/sectionTitle/SectionTitle";
import Popup from "../../../general/popup/Popup";
import ShareAd from "../../../general/shareAd/ShareAd";
import { useState } from "react";

interface IProps {
  title?: string;
  subTitle?: string;
  id: string;
  adItems?: IAd[];
  showAllTitle?: string;
  titleLink: string;
}

const SwiperSection = (props: IProps) => {
  const prevId = `prev-${props.id}`;
  const nextId = `next-${props.id}`;
  const [showShare, setShowShare] = useState(false);
  const [shareAd, setShareAd] = useState<IAd>();

  if (props.adItems && props.adItems?.length <= 0) return <></>;
  return (
    <section className={styles.container}>
      <SectionTitle {...props} />
      <div className={styles["swiper-container"]}>
        {props.adItems && (
          <>
            <Button className={`${nextId} slider-btn-next`}>
              <IoIosArrowBack />
            </Button>
            <Button className={`${prevId} slider-btn-prev`}>
              <IoIosArrowForward />
            </Button>
          </>
        )}
        <Swiper
          navigation={{
            prevEl: `.${prevId}`,
            nextEl: `.${nextId}`,
          }}
          breakpoints={{
            1: {
              slidesPerView: 1,
            },
            576: {
              slidesPerView: 2,
            },
            768: {
              slidesPerView: 3,
            },
            992: {
              slidesPerView: 4,
            },
          }}
          spaceBetween={8}
          modules={[Navigation, Lazy]}
          lazy={true}
          preloadImages={false}
          slidesPerGroup={1}
          style={{ position: "relative" }}
        >
          {props.adItems?.map((item, i) => (
            <SwiperSlide key={i}>
              <AdCard
                adInfo={item}
                showShareAd={() => {
                  setShareAd(item);
                  setShowShare(true);
                }}
              />
            </SwiperSlide>
          ))}
        </Swiper>
      </div>
      <Popup
        width={400}
        visible={showShare}
        onHiding={() => setShowShare(false)}
        title="اشتراک گذاری ملک"
      >
        <ShareAd adInfo={shareAd} />
      </Popup>
    </section>
  );
};

export default SwiperSection;
