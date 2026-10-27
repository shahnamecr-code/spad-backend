import { useEffect, useState } from "react";
import { IPagingResponse } from "../../../../types/general.types";
import Paging from "../../../general/paging/Paging";
import styles from "./expertiseRequests.module.scss";
import ExpertiseCard from "./expertiseCard/ExpertiseCard";
import { getMyExpertiseRequests } from "../../../../services/ProfileServices";
import { useLoadingContext } from "../../../../utils/context";
import { IExpertiseRequest } from "../../../../types/profile.types";

const ExpertiseRequests = () => {
  const [requests, setRequests] =
    useState<IPagingResponse<IExpertiseRequest>>();
  const loadingContext = useLoadingContext();

  useEffect(() => {
    getPagingRequests();
  }, []);

  const getPagingRequests = (url?: string) => {
    loadingContext.setLoading(true);

    getMyExpertiseRequests(url)
      .then((res) => setRequests(res.data))
      .finally(() => loadingContext.setLoading(false));
  };

  return (
    <div className={styles.container}>
      {requests?.data.length === 0 ? (
        !loadingContext.loading && (
          <span className={styles["not-exist"]}>(اطلاعاتی وجود ندارد)</span>
        )
      ) : (
        <div className={styles["items-container"]}>
          {requests?.data?.map((item, i) => (
            <ExpertiseCard key={i} info={item} />
          ))}
        </div>
      )}
      <Paging
        data={requests?.meta}
        onClickPage={(link) => {
          getPagingRequests(link.url || "");
        }}
      />
    </div>
  );
};

export default ExpertiseRequests;
