import type { NextPage } from "next";
import Head from "next/head";
import { useEffect } from "react";
import MainLayout from "../components/layout/mainLayout/MainLayout";
import Baner from "../components/pagesComponents/home/baner/Baner";
import LatestSection from "../components/pagesComponents/home/latestSection/LatestSection";
import LinkSection from "../components/pagesComponents/home/linkSection/LinkSection";
import MainSection from "../components/pagesComponents/home/mainSection/MainSection";
import NewsSection from "../components/pagesComponents/home/newsSection/NewsSection";
import SwiperSection from "../components/pagesComponents/home/swiperSection/SwiperSection";
import { getServerSiteSettings } from "../services/generalServices";
import {
  getMainPageData,
  getMainPageFilters,
} from "../services/MainPageServices";
import { IServerSideSettings } from "../types/general.types";
import { IFilterData, IMainPageData } from "../types/mainPage.types";
import { paths, setStore } from "../utils";
import { useAppContext } from "../utils/context";
import { LOCATION } from "../utils/stores";

interface IProps {
  filters: IFilterData[];
  data: IMainPageData;
  serverSideSettings: IServerSideSettings;
}

const Home: NextPage<IProps> = (props) => {
  const context = useAppContext();

  useEffect(() => {
    let province = props.serverSideSettings.province;
    let city = props.serverSideSettings.city;
    if (province && city) {
      setStore(LOCATION, {
        cities: [city],
        province,
      });
    }
  }, []);

  return (
    <MainLayout
      title={props.data.home_seo_title || ""}
      location={props.serverSideSettings.province}
    >
      <Head>
        <meta name="description" content={props.data.home_meta_description} />
      </Head>
      <MainSection
        filters={props.filters}
        mainImage={props.data.filters_background}
        general_site_name={props.serverSideSettings.general_site_name || ""}
      />
      <div style={{ height: 100 }} className="sm-hide"></div>
      <div className="container">
        {/* <LinkSection /> */}
        <SwiperSection
          title={`فروشی های ویژه ${props.serverSideSettings.general_site_name}`}
          id="special"
          subTitle="بهترین و مناسب ترین پیشنهاد ها را در این قسمت دنبال کنید"
          adItems={props.data?.special}
          titleLink={`${paths.search}?special=true`}
        />
        {props.data.banner_1_image && (
          <Baner
            img={props.data.banner_1_image}
            link={props.data.banner_1_link}
          />
        )}{" "}
        <SwiperSection
          title={`فروشی های فوری ${props.serverSideSettings.general_site_name}`}
          id="fast"
          subTitle={`نمایش آگهی های فوری که در ${
            props.serverSideSettings.general_site_name || ""
          } ثبت شده اند`}
          adItems={props.data?.fast}
          titleLink={`${paths.search}?fast=true`}
        />
        {props.data.banner_2_image && (
          <Baner
            img={props.data.banner_2_image}
            link={props.data.banner_2_link}
          />
        )}
        {/* {props.data.banner_3_image && (
          <Baner
            img={props.data.banner_3_image}
            link={props.data.banner_3_link}
          />
        )} */}
        <LatestSection
          title="آخرین آگهی های ثبت شده"
          id="latest"
          subTitle={`نمایش آگهی های جدیدی که در ${
            props.serverSideSettings.general_site_name || ""
          } ثبت شده اند`}
          showAllTitle="نمایش بیشتر"
          adItems={props.data?.latest}
          titleLink={paths.search}
        />
        <NewsSection
          title="اخـبار و مقـالات ملک"
          id="news"
          subTitle="مقالات و خبرهای جدید در مورد ملک را در این قسمت بیابید"
          showAllTitle="مجله خبری"
          newsItems={props.data?.news}
          titleLink={paths.news}
        />
      </div>
    </MainLayout>
  );
};

export default Home;

export async function getServerSideProps() {
  // Fetch data from external API
  const filters = await getMainPageFilters().then((res) => res.data);
  const data = await getMainPageData().then((res) => res.data);
  const serverSideSettings = await getServerSiteSettings().then(
    (res) => res.data
  );
  // Pass data to the page via props
  return { props: { filters, data, serverSideSettings } };
}
