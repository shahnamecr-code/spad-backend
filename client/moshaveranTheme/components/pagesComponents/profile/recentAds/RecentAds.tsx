import styles from "./recentAds.module.scss";
import { useEffect, useState } from "react";
import { IAd } from "../../../../types/ads.types";
import { IPagingResponse } from "../../../../types/general.types";
import { useLoadingContext } from "../../../../utils/context";
import Paging from "../../../general/paging/Paging";
import RecentAdCard from "./recentAdCard/RecentAdCard";
import { getCustomAds } from "../../../../services/FavoritesServices";
import { getStore } from "../../../../utils";
import { RECENT } from "../../../../utils/stores";

const RecentAds = () => {
  const loadingContext = useLoadingContext();
  const [myAds, setMyAds] = useState<IPagingResponse<IAd>>();

  useEffect(() => {
    getPagingFavorites();
  }, []);

  const getPagingFavorites = (url?: string) => {
    loadingContext.setLoading(true);
    if (JSON.parse(getStore(RECENT) || "[]").length)
      getCustomAds(url, JSON.parse(getStore(RECENT) || "[]"))
        .then((res) => setMyAds(res.data))
        .finally(() => loadingContext.setLoading(false));
    else {
      setMyAds({ data: [] });
      loadingContext.setLoading(false);
    }
  };

  return (
    <div className={styles.container}>
      {myAds?.data.length === 0 ? (
        !loadingContext.loading && (
          <span className={styles["not-exist"]}>(اطلاعاتی وجود ندارد)</span>
        )
      ) : (
        <div className={styles["ads-container"]}>
          {myAds?.data?.map((item, i) => (
            <RecentAdCard key={i} adInfo={item} />
          ))}
        </div>
      )}
      <Paging
        data={myAds?.meta}
        onClickPage={(link) => {
          getPagingFavorites(link.url || "");
        }}
      />
    </div>
  );
};

export default RecentAds;
