import { IoIosArrowBack } from "@react-icons/all-files/io/IoIosArrowBack";
import { IoIosArrowForward } from "@react-icons/all-files/io/IoIosArrowForward";
import { useState } from "react";
import { Lazy, Navigation } from "swiper";
import { Swiper, SwiperSlide } from "swiper/react";
import { IAd } from "../../../../types/ads.types";
import { INews } from "../../../../types/news.types";
import Button from "../../../general/button/Button";
import NewsCard from "../../../general/newsCard/NewsCard";
import SectionTitle from "../../../general/sectionTitle/SectionTitle";
import styles from "./newsSection.module.scss";

interface IProps {
  title?: string;
  subTitle?: string;
  showAllTitle?: string;
  newsItems?: INews[];
  titleLink: string;
  id?: string;
}

const NewsSection = (props: IProps) => {
  const prevId = `prev-${props.id}`;
  const nextId = `next-${props.id}`;

  if (props.newsItems && props.newsItems.length <= 0) return <></>;
  return (
    <section className={styles.container}>
      <SectionTitle {...props} />

      <div className={styles["swiper-container"]}>
        {props.newsItems && (
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
          {props.newsItems?.map((item, i) => (
            <SwiperSlide key={i}>
              <NewsCard newsInfo={item} key={i} className={styles.newsCard} />
            </SwiperSlide>
          ))}
        </Swiper>
      </div>

      {/* <div className={styles["news-container"]}>
        {props.newsItems?.map((item, i) => (
          <NewsCard newsInfo={item} key={i} />
        ))}
      </div> */}
    </section>
  );
};

export default NewsSection;
