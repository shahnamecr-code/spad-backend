import { useEffect, useState } from "react";
import { getMyAds } from "../../../../services/ProfileServices";
import { IAd } from "../../../../types/ads.types";
import { IPagingResponse } from "../../../../types/general.types";
import { useLoadingContext } from "../../../../utils/context";
import Paging from "../../../general/paging/Paging";
import ProfileAdCard from "../profileAdCard/ProfileAdCard";
import styles from "./myAds.module.scss";

const MyAds = () => {
  const loadingContext = useLoadingContext();
  const [myAds, setMyAds] = useState<IPagingResponse<IAd>>();

  useEffect(() => {
    getPagingAds();
  }, []);

  const getPagingAds = (url?: string) => {
    loadingContext.setLoading(true);

    getMyAds(url)
      .then((res) => {
        setMyAds(res.data);
      })
      .finally(() => loadingContext.setLoading(false));
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
            <ProfileAdCard key={i} adInfo={item} />
          ))}
        </div>
      )}
      <Paging
        data={myAds?.meta}
        onClickPage={(link) => {
          getPagingAds(link.url || "");
        }}
      />
    </div>
  );
};

export default MyAds;
