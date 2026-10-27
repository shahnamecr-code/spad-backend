/* eslint-disable @next/next/no-img-element */
import { Form, Formik } from "formik";
import { useRouter } from "next/router";
import { useEffect, useState } from "react";
import InfiniteScroll from "react-infinite-scroll-component";
import AdCard from "../../components/general/adCard/AdCard";
import Popup from "../../components/general/popup/Popup";
import SearchBox from "../../components/general/searchBox/SearchBox";
import ShareAd from "../../components/general/shareAd/ShareAd";
import Switch from "../../components/general/switch/Switch";
import MainLayout from "../../components/layout/mainLayout/MainLayout";
import Filters from "../../components/pagesComponents/search/filter/Filters";
import { getAdList } from "../../services/AdsServices";
import { IAd } from "../../types/ads.types";
import { deepCopy, getStore, img, setStore } from "../../utils";
import { useAppContext } from "../../utils/context";
import { useSearchDev } from "../../components/pagesComponents/search/search.dev";
import styles from "./search.module.scss";
import { LOCATION, REGION } from "../../utils/stores";
import { ILocationStore } from "../../components/layout/cityPopup/cityPopup.dev";
import { ILocation } from "../../types/createAd.types";
import SimpleLoading from "../../components/general/simpleLoading/SimpleLoading";

const Search = () => {
  const [showShare, setShowShare] = useState(false);
  const [shareAd, setShareAd] = useState<IAd>();
  const [adList, setAdList] = useState<IAd[]>([]);
  const [page, setPage] = useState(0);
  const [adsCount, setAdsCount] = useState<number>();
  const [nextUrl, setNextUrl] = useState<string>();
  const [values, setValues] = useState<any>();
  const [loading, setLoading] = useState(false);
  const router = useRouter();
  const context = useAppContext();
  const dev = useSearchDev(context);
  const [adsLoading, setAdsLoading] = useState(false);

  useEffect(() => {
    page && fetchData(page);
  }, [page]);

  useEffect(() => {
    if (Object.keys(router.query).length === 0 || values) {
      setPage(0);
      fetchData(0);
    }
  }, [values]);

  useEffect(() => {
    if (
      Object.keys(context.location).length &&
      getStore(LOCATION) != JSON.stringify(context.location)
    ) {
      setStore(LOCATION, context.location);
      setPage(0);
      fetchData(0);
    }
  }, [context.location]);

  const fetchData = (page?: number, reseyRegion?: boolean) => {
    setAdsLoading(true);
    const filters = deepCopy(values || {});
    if (values) {
      filters.transaction = filters.transaction?.term_id;
      filters.land_type = filters.land_type?.term_id;
    }
    const region = Object.keys(context.regions).length
      ? context.regions
      : (JSON.parse(getStore(REGION) || "[]") as ILocation[]);
    const location = Object.keys(context.location).length
      ? context.location
      : (JSON.parse(getStore(LOCATION) || "{}") as ILocationStore);
    filters.position = {
      cities: location.cities?.map((x) => x.id),
      province: location?.province?.id,
      region: reseyRegion
        ? undefined
        : region.length
        ? region?.map((x) => x.id)
        : undefined,
    };
    getAdList(page === 0 ? "" : nextUrl, filters)
      .then((res) => {
        if (page === 0) {
          setAdList(res.data.data);
          setAdsCount(res.data.total);
        } else setAdList((prev) => [...(prev || []), ...(res.data.data || [])]);
        setNextUrl(res?.data?.links?.next || "");
      })
      .finally(() => setAdsLoading(false));
  };

  return (
    <MainLayout title="آرشیو املاک">
      <Formik
        initialValues={dev.filterInitialValue}
        onSubmit={dev.filtersOnSubmit}
      >
        {({ values, setFieldValue }) => (
          <Form style={{ width: "100%" }}>
            <div className={styles.container}>
              <div className={styles["filters-container"]}>
                {loading && <SimpleLoading />}
                <aside className={styles.filters}>
                  <Filters
                    categories={dev.categories}
                    labels={dev.labels}
                    onSearch={(values) => {
                      setValues(values);
                    }}
                    setLoading={(val) => setLoading(val)}
                  />
                </aside>
              </div>

              <div className={styles["ads-container"]}>
                <span className={styles["ads-count"]}>
                  {!adsLoading &&
                    (adsCount ? `${adsCount} ملک` : "آگهی وجود ندارد")}
                </span>
                <div className={styles["top-filters"]}>
                  <div>
                    <Switch
                      label="عکس دار باشد"
                      value={values.has_image}
                      onChange={(value) => {
                        dev.filtersOnSubmit({
                          ...values,
                          has_image: value === true ? true : undefined,
                        });
                      }}
                    />
                  </div>
                  <SearchBox
                    placeHolder="جستجو با عنوان یا کد آگهی"
                    value={values.name}
                    onChange={(e) => setFieldValue("name", e)}
                    onClickSearch={() => {
                      dev.filtersOnSubmit(values);
                    }}
                    width={300}
                  />
                </div>
                {page === 0 && adsLoading && (
                  <>
                    {Array(24)
                      .fill(null)
                      .map((_, i) => (
                        <div className={styles.ad} key={i}>
                          <AdCard />
                        </div>
                      ))}
                  </>
                )}
                {adsCount != 0 ? (
                  <InfiniteScroll
                    dataLength={adList?.length || 0}
                    next={() => {
                      setPage(page + 1);
                    }}
                    hasMore={
                      adsCount === undefined || adList?.length < adsCount
                    }
                    loader={
                      <>
                        {Array(24)
                          .fill(null)
                          .map((_, i) => (
                            <div className={styles.ad} key={i}>
                              <AdCard />
                            </div>
                          ))}
                      </>
                    }
                  >
                    {adList?.map((item, i) => (
                      <div className={styles.ad} key={i}>
                        <AdCard
                          showShareAd={() => {
                            setShowShare(true);
                            setShareAd(item);
                          }}
                          adInfo={item}
                        />
                      </div>
                    ))}
                  </InfiniteScroll>
                ) : (
                  !adsLoading && (
                    <div className={styles["not-exist-container"]}>
                      <img
                        src={img.no_data.default.src}
                        alt="آگهی وجود ندارد"
                      />
                      <span>آگهی وجود ندارد</span>
                    </div>
                  )
                )}
              </div>
            </div>
          </Form>
        )}
      </Formik>

      <Popup
        width={400}
        visible={showShare}
        onHiding={() => setShowShare(false)}
        title="اشتراک گذاری ملک"
      >
        <ShareAd adInfo={shareAd} />
      </Popup>
    </MainLayout>
  );
};

export default Search;
