/* eslint-disable @next/next/no-img-element */
import Button from "../../../components/general/button/Button";
import RenderLinks from "../../../components/general/renderLinks/RenderLinks";
import MainLayout from "../../../components/layout/mainLayout/MainLayout";
import styles from "../single.module.scss";
import { useEffect, useState } from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import { Navigation } from "swiper";
import ImageSlider from "../../../components/general/imageSlider/ImageSlider";
import ContactManagementPopup from "../../../components/pagesComponents/single/contactManagementPopup/ContactManagementPopup";
//icons
import { BiPhone } from "@react-icons/all-files/bi/BiPhone";
import { BiShareAlt } from "@react-icons/all-files/bi/BiShareAlt";
import { BiPrinter } from "@react-icons/all-files/bi/BiPrinter";
import { BiExpand } from "@react-icons/all-files/bi/BiExpand";
import { getAdById } from "../../../services/AdsServices";
import { ISingleAd } from "../../../types/ads.types";
import SinglePageWidgets from "../../../components/pagesComponents/single/widgets";
import RealEstatevisitRequestPopup from "../../../components/pagesComponents/single/realEstatevisitRequestPopup";
import RealtorInfoExperts from "../../../components/pagesComponents/single/realtorInfo/RealtorInfoExperts";
import Popup from "../../../components/general/popup/Popup";
import ShareAd from "../../../components/general/shareAd/ShareAd";
import { useRouter } from "next/router";
import { useAppContext, useLoadingContext } from "../../../utils/context";
import { urls } from "../../../utils";

const PreviewSingle = () => {
  const [showShare, setShowShare] = useState(false);
  const [adInfo, setAdInfo] = useState<ISingleAd>();
  const [showSlider, setShowSlider] = useState(false);
  const [ShowVisit, setShowVisit] = useState(false);
  const [showContactPopup, setShowContactPopup] = useState(false);
  const loadingContext = useLoadingContext();
  const router = useRouter();
  const context = useAppContext();

  useEffect(() => {
    if (router.query.id) {
      loadingContext.setLoading(true);
      getAdById(`${router.query.id}`, urls.singleAdAuth)
        .then((res) => setAdInfo(res.data))
        .finally(() => loadingContext.setLoading(false));
    }
  }, [router.query]);

  return (
    <MainLayout title={adInfo?.title || ""}>
      {adInfo ? (
        <>
          <div className={styles["links-container"]}>
            <RenderLinks links={[{ title: adInfo?.title || "" }]} />
            <Button width={180} type="outlined" color="gray">
              ثبت تخلف و مشکل آگهی
            </Button>
          </div>
          <div className={styles.container}>
            <div className={styles.right}>
              <div className={styles["swiper-container"]}>
                <div className={styles["btns-container"]}>
                  <Button color="white" onClick={() => setShowSlider(true)}>
                    <BiExpand fontSize={18} />
                    <span className="xs-hide">تصاویر بیشتر</span>
                  </Button>
                </div>
                <Swiper
                  navigation={true}
                  modules={[Navigation]}
                  preloadImages={false}
                >
                  {adInfo?.gallery.map((img, i) => (
                    <SwiperSlide key={i}>
                      <img
                        src={img}
                        className="object-fit-cover"
                        style={{ borderRadius: 14, height: 350 }}
                        alt={adInfo.title}
                      />
                    </SwiperSlide>
                  ))}
                </Swiper>
              </div>
              <div className={styles["main-info-container"]}>
                <div className={styles["btns-main-container"]}>
                  <div className={styles["btns-container"]}>
                    <Button
                      width={140}
                      type="outlined"
                      color="gray"
                      onClick={() => setShowShare(true)}
                      className={styles["btn-share"]}
                    >
                      <BiShareAlt className={styles.icon} />
                      اشتراک گذاری
                    </Button>
                    <Button
                      width={140}
                      type="outlined"
                      color="gray"
                      onClick={() => window.print()}
                      className={styles["btn-print"]}
                    >
                      <BiPrinter className={styles.icon} />
                      پرینت اطلاعات
                    </Button>
                  </div>
                  <div className={styles["btns-container"]}>
                    {adInfo?.fast && <span className={styles.fast}>فوری</span>}
                    {adInfo?.special && (
                      <span className={styles.lux}>ویژه</span>
                    )}
                    {/* <span className={styles.netting}>معاوضه</span>  */}
                  </div>
                </div>
                <div
                  className={`w-100 flex-between ${styles["title-container"]}`}
                >
                  <div className={styles["main-info"]}>
                    {/* <span className={styles.date}>{singleAd.updated_at}</span> */}
                    <h1 className={styles.title}>{adInfo?.title}</h1>

                    {adInfo?.widgets
                      .filter((x) => x.widget_type == "horizontal")[0]
                      ?.items?.map((item, i) => (
                        <div key={i}>
                          <span className={styles.name}>
                            {item.info?.title}:{" "}
                          </span>
                          <span className={styles.value}>
                            {item.info?.value}
                          </span>
                        </div>
                      ))}
                  </div>
                  <div className={styles["title-btns-container"]}>
                    <div className={styles["btn-item "]}>
                      <Button
                        color="gray"
                        width={190}
                        onClick={() => setShowContactPopup(true)}
                      >
                        <BiPhone className={styles.icon} />
                        تماس با مدیریت
                      </Button>
                    </div>
                    {/* <div className={styles["btn-item"]}>
                  <Button
                    color="primary"
                    width={190}
                    onClick={() => setShowVisit(true)}
                  >
                    <IoMdEye className={styles.icon} />
                    درخواست بازدید ملک
                  </Button>
                </div> */}
                  </div>
                </div>
              </div>

              {/* render Widgets */}
              {adInfo?.widgets.map((item, i) => (
                <SinglePageWidgets item={item} key={i} />
              ))}
              {adInfo?.important_places?.length && (
                <SinglePageWidgets
                  item={{
                    title: "فاصله از مراکز مهم",
                    widget_type: "group",
                    front_layout: "layout1",
                    items: adInfo?.important_places?.map((x) => {
                      return {
                        info: {
                          icon: x.place?.icon,
                          title: x.place?.label,
                          value: x.distance + " دقیقه",
                        },
                      };
                    }),
                  }}
                />
              )}
            </div>
            <div className={styles.left}>
              <span className={styles.title}>مشاوران شما</span>
              <div className="dark-box">
                جهت کسب اطلاعات بیشتر در خصوص آگهی با
                <span className="primary">مشاورین</span> تماس حاصل فرمایید.
              </div>
              <div className="dark-box">
                به هنگام تماس با کارشناسان {context.options.general_site_name}،
                کُد آگهی
                <span className="primary">{adInfo?.id}</span> را به خاطر
                بسپارید.
              </div>
              {adInfo?.admins.experts.map((item, i) => (
                <RealtorInfoExperts item={item} key={i} />
              ))}
            </div>
          </div>
          <Popup
            width={350}
            visible={showShare}
            onHiding={() => setShowShare(false)}
            title="اشتراک گذاری ملک"
          >
            <ShareAd
              adInfo={{
                ...adInfo,
                id: adInfo?.id || 0,
                title: adInfo?.title || "",
                main_props:
                  adInfo?.widgets
                    .filter((x) => x.front_layout == "layout1")[0]
                    ?.items?.map((item) => {
                      return {
                        title: item.info?.title,
                        value: item.info?.value,
                        unit: item.info?.unit,
                      };
                    }) || [],
                region: adInfo?.location || "",
              }}
            />
          </Popup>
          <ImageSlider
            visible={showSlider}
            onHiding={() => setShowSlider(false)}
            images={adInfo?.gallery || []}
          />
          <RealEstatevisitRequestPopup
            advId={adInfo?.id || 0}
            visible={ShowVisit}
            onHiding={() => setShowVisit(false)}
          />

          <ContactManagementPopup
            visible={showContactPopup}
            onHiding={() => setShowContactPopup(false)}
            managers_list={adInfo?.managers_list || []}
          />
        </>
      ) : null}
    </MainLayout>
  );
};

export default PreviewSingle;
