/* eslint-disable react-hooks/exhaustive-deps */
/* eslint-disable @next/next/no-img-element */
import Button from "../../components/general/button/Button";
import RenderLinks from "../../components/general/renderLinks/RenderLinks";
import MainLayout from "../../components/layout/mainLayout/MainLayout";
import styles from "./single.module.scss";
import { useEffect, useState } from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import { Navigation } from "swiper";
import { useFavoriteDev } from "../../components/general/adCard/favorite.dev";
import ImageSlider from "../../components/general/imageSlider/ImageSlider";
import ViolationPopup from "../../components/pagesComponents/single/violationPopup/ViolationPopup";
import ContactManagementPopup from "../../components/pagesComponents/single/contactManagementPopup/ContactManagementPopup";
import { getAdById } from "../../services/AdsServices";
import { ISingleAd } from "../../types/ads.types";
import SinglePageWidgets from "../../components/pagesComponents/single/widgets";
import RealEstatevisitRequestPopup from "../../components/pagesComponents/single/realEstatevisitRequestPopup";
import RealtorInfoExperts from "../../components/pagesComponents/single/realtorInfo/RealtorInfoExperts";
import { getStore, setStore, urls } from "../../utils";
import { RECENT } from "../../utils/stores";
import Popup from "../../components/general/popup/Popup";
import ShareAd from "../../components/general/shareAd/ShareAd";
//icons
import { BiPhone } from "@react-icons/all-files/bi/BiPhone";
import { BiShareAlt } from "@react-icons/all-files/bi/BiShareAlt";
import { BiPrinter } from "@react-icons/all-files/bi/BiPrinter";
import { BsBookmark } from "@react-icons/all-files/bs/BsBookmark";
import { BiExpand } from "@react-icons/all-files/bi/BiExpand";
import { BsBookmarkFill } from "@react-icons/all-files/bs/BsBookmarkFill";
import { IoMdEye } from "@react-icons/all-files/io/IoMdEye";
import { useAppContext } from "../../utils/context";
import Head from "next/head";
import { getServerSiteSettings } from "../../services/generalServices";
import { IServerSideSettings } from "../../types/general.types";

const Single = (props: {
  adInfo: ISingleAd;
  serverSideSettings: IServerSideSettings;
}) => {
  const [showShare, setShowShare] = useState(false);
  const dev = useFavoriteDev(props.adInfo?.id || 0);
  const [showSlider, setShowSlider] = useState(false);
  const [ShowVisit, setShowVisit] = useState(false);
  const [showViolationPopup, setShowViolationPopup] = useState(false);
  const [showContactPopup, setShowContactPopup] = useState(false);
  const context = useAppContext();

  useEffect(() => {
    dev.checkIsFavorite();
    addToRecently();
  }, []);

  const addToRecently = () => {
    const id = props.adInfo?.id.toString();
    let arr = JSON.parse(getStore(RECENT) || "[]") as string[];
    if (arr.includes(id)) {
      arr = arr.filter((x) => x != id);
    }
    arr = [id, ...arr];
    if (arr.length > 32) arr.splice(arr.length - 1, 1);

    setStore(RECENT, arr);
  };

  console.log("props.adInfo", props.adInfo);

  return (
    <MainLayout title={props.adInfo?.title}>
      <Head>
        <meta name="description" content={props.adInfo?.title} />
      </Head>
      <div className={styles["links-container"]}>
        <RenderLinks links={[{ title: props.adInfo?.title }]} />
        <Button
          width={180}
          type="outlined"
          color="gray"
          onClick={() => setShowViolationPopup(true)}
        >
          ثبت تخلف و مشکل آگهی
        </Button>
      </div>
      <div className={styles.container}>
        <div className={styles.right}>
          <div className={styles["swiper-container"]}>
            <div className={styles["btns-container"]}>
              <Button color="white" onClick={dev.toggleFavorite}>
                {dev.favorite ? (
                  <BsBookmarkFill fontSize={18} />
                ) : (
                  <BsBookmark fontSize={18} />
                )}
                <span className="xs-hide">نشان کردن</span>
              </Button>
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
              {props.adInfo?.gallery.map((img, i) => (
                <SwiperSlide key={i}>
                  <img
                    src={img}
                    className="object-fit-cover"
                    style={{ borderRadius: 14, height: 350 }}
                    alt={props.adInfo.title}
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
                {props.adInfo?.fast && (
                  <span className={styles.fast}>فوری</span>
                )}
                {props.adInfo?.special && (
                  <span className={styles.lux}>ویژه</span>
                )}
                {/* <span className={styles.netting}>معاوضه</span>  */}
              </div>
            </div>
            <div className={`w-100 flex-between ${styles["title-container"]}`}>
              <div className={styles["main-info"]}>
                {/* <span className={styles.date}>{singleAd.updated_at}</span> */}
                <h1 className={styles.title}>{props.adInfo?.title}</h1>

                {props.adInfo?.widgets
                  .filter((x) => x.widget_type == "horizontal")[0]
                  ?.items?.map((item, i) => (
                    <div key={i}>
                      <span className={styles.name}>{item.info?.title}: </span>
                      <span className={styles.value}>{item.info?.value}</span>
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
                <div className={styles["btn-item"]}>
                  <Button
                    color="primary"
                    width={190}
                    onClick={() => setShowVisit(true)}
                  >
                    <IoMdEye className={styles.icon} />
                    درخواست بازدید ملک
                  </Button>
                </div>
              </div>
            </div>
          </div>

          {/* render Widgets */}
          {props.adInfo?.widgets.map((item, i) => (
            <SinglePageWidgets item={item} key={i} />
          ))}
          {props.adInfo?.important_places?.length ? (
            <SinglePageWidgets
              item={{
                title: "فاصله از مراکز مهم",
                widget_type: "group",
                front_layout: "layout1",
                items: props.adInfo?.important_places?.map((x) => {
                  if (x.distance) {
                    return {
                      info: {
                        icon: x.place?.icon,
                        title: x.place?.label,
                        value: x.distance + " دقیقه",
                      },
                    };
                  } else {
                    return {};
                  }
                }),
              }}
            />
          ) : (
            ""
          )}
        </div>
        <div className={styles.left}>
          <span className={styles.title}>مشاوران شما</span>
          <div className="dark-box">
            جهت کسب اطلاعات بیشتر در خصوص آگهی با
            <span className="primary">مشاورین</span> تماس حاصل فرمایید.
          </div>
          <div className="dark-box">
            به هنگام تماس با کارشناسان {props.serverSideSettings.general_site_name}، کُد
            آگهی
            <span className="primary">{props.adInfo?.id}</span> را به خاطر
            بسپارید.
          </div>
          {props.adInfo?.admins.experts.map((item, i) => (
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
            ...props.adInfo,
            id: props.adInfo?.id,
            title: props.adInfo?.title || "",
            main_props:
              props.adInfo?.widgets
                .filter((x) => x.front_layout == "layout1")[0]
                ?.items?.map((item) => {
                  return {
                    title: item.info?.title,
                    value: item.info?.value,
                    unit: item.info?.unit,
                  };
                }) || [],
            region: props.adInfo?.location || "",
          }}
        />
      </Popup>
      <ImageSlider
        visible={showSlider}
        onHiding={() => setShowSlider(false)}
        images={props.adInfo?.gallery}
      />
      <RealEstatevisitRequestPopup
        advId={props.adInfo?.id}
        visible={ShowVisit}
        onHiding={() => setShowVisit(false)}
      />
      <ViolationPopup
        visible={showViolationPopup}
        onHiding={() => setShowViolationPopup(false)}
        ads_report_reasons={props.adInfo?.ads_report_reasons}
        advId={String(props.adInfo?.id)}
      />
      <ContactManagementPopup
        visible={showContactPopup}
        onHiding={() => setShowContactPopup(false)}
        managers_list={props.adInfo?.managers_list}
      />
    </MainLayout>
  );
};

export default Single;

export async function getServerSideProps(context: any) {
  // Fetch data from external API
  const adInfo = await getAdById(context.params.id).then((res) => res.data);
  const serverSideSettings = await getServerSiteSettings().then(
    (res) => res.data
  );
  // Pass data to the page via props
  return { props: { adInfo, serverSideSettings } };
}
