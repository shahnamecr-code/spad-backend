/* eslint-disable @next/next/no-img-element */
import MainLayout from "../../components/layout/mainLayout/MainLayout";
import { img } from "../../utils";
import styles from "./about-us.module.scss";
import { FaRegListAlt } from "@react-icons/all-files/fa/FaRegListAlt";
import { FaUserFriends } from "@react-icons/all-files/fa/FaUserFriends";
import { FaUserSecret } from "@react-icons/all-files/fa/FaUserSecret";
import { FaCity } from "@react-icons/all-files/fa/FaCity";
import RealtorInfo from "../../components/pagesComponents/aboutUs/realtorInfo/RealtorInfo";
import { useEffect, useState } from "react";
import { getAboutUs } from "../../services/generalServices";
import { IAboutResData } from "../../types/about.types";
import { useAppContext } from "../../utils/context";

const AboutUsApp = ({ aboutData }: { aboutData: IAboutResData }) => {
  const [aboutContent, setAboutContent] = useState<IAboutResData>();
  const context = useAppContext();
  useEffect(() => {
    setAboutContent(aboutData);
  }, []);

  const statistics = [
    {
      icon: <FaRegListAlt />,
      title: "تعداد آگهی ها",
      value: aboutContent?.ads_count,
    },
    {
      icon: <FaUserFriends />,
      title: "تعداد باشگاه مشتریان",
      value: aboutContent?.users_count,
    },
    {
      icon: <FaUserSecret />,
      title: "تعداد کارشناسان",
      value: aboutContent?.experts_count,
    },
    {
      icon: <FaCity />,
      title: "تعداد مناطق",
      value: aboutContent?.regions_count,
    },
  ];

  return (
    <MainLayout title="درباره ما" hideLayout>
      <div className={styles.container}>
        <div className={styles["info-container"]}>
          <h1 className={styles.title}>
            درباره {context.options.general_site_name || ""}
          </h1>
          <p
            className={styles.description}
            dangerouslySetInnerHTML={{
              __html: aboutContent?.about_us ? aboutContent?.about_us : "",
            }}
          />
          <h2 className={styles["sub-title"]}>
            آمار {context.options.general_site_name || ""}
          </h2>
          <div className={styles["statistics-container"]}>
            {statistics.map((item, i) => (
              <div key={i} className={styles["statistics-item"]}>
                {item.icon}
                <span>{item.title}</span>
                <span className={styles.value}>{item.value}</span>
              </div>
            ))}
          </div>
          <h2 className={styles["sub-title"]}>
            مدیران {context.options.general_site_name || ""}
          </h2>
          <div className={styles["realtors-container"]}>
            {aboutContent?.about_experts_list.map((item, i) => (
              <RealtorInfo
                info={item}
                key={i}
                user_default_profile={aboutContent.user_default_profile}
              />
            ))}
          </div>
        </div>
        <div className={`${styles["img-container"]} xs-hide`}>
          <img
            src={
              aboutContent?.about_image
                ? `${aboutContent?.about_image}`
                : img.about_us.default.src
            }
            style={{ width: "100%" }}
            alt="عکس درباره ما"
          />
        </div>
      </div>
    </MainLayout>
  );
};

export default AboutUsApp;

export async function getServerSideProps(context: any) {
  // Fetch data from external API
  const aboutData = await getAboutUs().then((res) => res.data);
  // Pass data to the page via props
  return { props: { aboutData } };
}
