/* eslint-disable @next/next/no-img-element */
import { getFromOjectByKey, img, paths } from "../../../../utils";
import Button from "../../../general/button/Button";
import styles from "./mainSection.module.scss";
import { Formik, Form } from "formik";
import { useHomeFiltersDev } from "./filters.dev";
import { Select } from "../../../general/select/Select";
import FieldSection from "./fieldSection/FieldSection";
import { IoSearch } from "@react-icons/all-files/io5/IoSearch";
import { Swiper, SwiperSlide } from "swiper/react";
import { Navigation } from "swiper";
import { IoIosArrowBack } from "@react-icons/all-files/io/IoIosArrowBack";
import { IoIosArrowForward } from "@react-icons/all-files/io/IoIosArrowForward";
import React, { useState } from "react";
import { useRouter } from "next/router";
import { useAppContext } from "../../../../utils/context";
import { IFilterData } from "../../../../types/mainPage.types";

interface IProps {
  filters: IFilterData[];
  mainImage?: string;
  general_site_name: string;
}

const MainSection = (props: IProps) => {
  const dev = useHomeFiltersDev(props.filters);
  const router = useRouter();
  const context = useAppContext();

  return (
    <section
      className={styles.container}
      style={{
        backgroundImage: `url(${props.mainImage || img.bg.default.src})`,
      }}
    >
      <div className={styles["search-container"]}>
        <h1 className={styles.title}>
          {`${props.general_site_name || ""} ، ${
            context.options.general_site_description || ""
          }`}
        </h1>
        <h2 className={styles.phone}>{context.options.footer_phone}</h2>
        <Formik
          initialValues={dev.filterInitialValue}
          onSubmit={dev.filtersOnSubmit}
        >
          {({ values, setFieldValue, resetForm }) => {
            const transaction: IFilterData | undefined = getFromOjectByKey(
              values,
              "transaction"
            ) as IFilterData;

            const applicationType: IFilterData | undefined = getFromOjectByKey(
              values,
              "application_type"
            ) as IFilterData;

            const landType: IFilterData | undefined = getFromOjectByKey(
              values,
              "land_type"
            ) as IFilterData;

            return (
              <Form style={{ width: "100%" }}>
                <div className={styles["transaction-container"]}>
                  <Button className="nextFilter slider-btn-next" type="text">
                    <IoIosArrowBack />
                  </Button>
                  <Button className="prevFilter slider-btn-prev" type="text">
                    <IoIosArrowForward />
                  </Button>
                  <Swiper
                    navigation={{
                      prevEl: ".prevFilter",
                      nextEl: ".nextFilter",
                    }}
                    breakpoints={{
                      1: {
                        slidesPerView: 4,
                      },
                      768: {
                        slidesPerView: 6,
                      },
                      1024: {
                        slidesPerView: 8,
                      },
                    }}
                    modules={[Navigation]}
                    centerInsufficientSlides
                  >
                    {props.filters.map((item, i) => (
                      <SwiperSlide
                        key={i}
                        style={{
                          backgroundColor: "transparent",
                        }}
                      >
                        <div
                          key={i}
                          className={`${styles.transaction} ${
                            transaction === item ? styles.active : ""
                          }`}
                          onClick={() => {
                            if (dev.searchByCode) dev.setSearchbyCode(false);
                            resetForm();
                            setFieldValue("transaction", item);
                            setFieldValue("land_type", undefined);
                          }}
                        >
                          <img
                            src={item.icon || "/"}
                            width={50}
                            height={50}
                            alt={item.name}
                          />
                          <span className={styles.label}>{item.name}</span>
                        </div>
                      </SwiperSlide>
                    ))}
                    <SwiperSlide
                      style={{
                        backgroundColor: "transparent",
                      }}
                    >
                      <div
                        className={`${styles.transaction} ${
                          dev.searchByCode ? styles.active : ""
                        }`}
                        onClick={() => {
                          resetForm();
                          dev.setSearchbyCode(true);
                          setFieldValue("transaction", undefined);
                          setFieldValue("land_type", undefined);
                        }}
                      >
                        {context.options?.search_ad_by_code ? (
                          <img
                            src={context.options.search_ad_by_code}
                            width={50}
                            height={50}
                            alt="جستجو با کد ملک"
                          />
                        ) : (
                          <img
                            src={img.searchByCode.default.src}
                            width={50}
                            height={50}
                            alt="جستجو با کد ملک"
                          />
                        )}
                        <span className={styles.label}>جستجو با کد ملک</span>
                      </div>
                    </SwiperSlide>
                  </Swiper>
                </div>
                <div className={styles["fields-container"]}>
                  {dev.searchByCode ? (
                    <>
                      <div className={styles["search-by-code"]}>
                        <IoSearch fontSize={20} />
                        <input
                          value={dev.code}
                          onChange={(e) => dev.setCode(e.target.value)}
                          className="flex-1 border-0"
                        />
                      </div>
                      <div className={styles.divider} />
                    </>
                  ) : (
                    transaction && (
                      <>
                        <Select
                          id="application_type"
                          items={transaction?.customer_children}
                          className={styles.field}
                          displayExpr="name"
                          valueKey="id"
                          value={applicationType}
                          onChange={(e) => setFieldValue("application_type", e)}
                          placeholder="نوع کاربری"
                          removeBorder
                        />
                        <div className={styles.divider} />
                        <Select
                          id="land_type"
                          items={applicationType?.customer_children}
                          className={styles.field}
                          displayExpr="name"
                          valueKey="id"
                          value={landType}
                          onChange={(e) => setFieldValue("land_type", e)}
                          placeholder="نوع ملک"
                          removeBorder
                        />
                        <div className={styles.divider} />
                        {transaction?.layout &&
                          transaction?.layout.map((item, i) => (
                            <React.Fragment key={i}>
                              <FieldSection
                                info={item}
                                className={styles.field}
                              />
                              <div className={styles.divider} />
                            </React.Fragment>
                          ))}
                      </>
                    )
                  )}
                  <Button width={120} btnType="submit">
                    جستجو
                    <IoSearch fontSize={20} color="white" />
                  </Button>
                </div>
              </Form>
            );
          }}
        </Formik>
      </div>
      <div className={`${styles["contact-us"]} container`}>
        <span className={styles["text-1"]}>
          هنوز ملک مورد نظرت رو پیدا نکردی ؟
          <span className={styles["text-2"]}>پس با ما تماس بگیر ... !</span>
        </span>
        <Button
          type="outlined"
          color="white"
          width={120}
          className={styles["contact-btn"]}
          onClick={() => router.push(paths["contact-us"])}
        >
          تماس با ما
        </Button>
      </div>
    </section>
  );
};

export default MainSection;
