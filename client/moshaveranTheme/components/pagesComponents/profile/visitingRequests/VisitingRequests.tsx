import { useEffect, useState } from "react";
import { getMyVisitingRequests } from "../../../../services/ProfileServices";
import { IPagingResponse } from "../../../../types/general.types";
import { IVisitingRequest } from "../../../../types/profile.types";
import { useLoadingContext } from "../../../../utils/context";
import Paging from "../../../general/paging/Paging";
import VisitingRequestCard from "./visitingRequestCard/VisitingRequestCard";
import styles from "./visitingRequests.module.scss";

const VisitingRequests = () => {
  const loadingContext = useLoadingContext();
  const [requests, setRequests] = useState<IPagingResponse<IVisitingRequest>>();

  useEffect(() => {
    getPagingRequests();
  }, []);

  const getPagingRequests = (url?: string) => {
    loadingContext.setLoading(true);

    getMyVisitingRequests(url)
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
            <VisitingRequestCard key={i} info={item} />
          ))}
        </div>
      )}
      <Paging
        data={requests?.meta}
        onClickPage={(link) => {
          getMyVisitingRequests(link.url || "");
        }}
      />
    </div>
  );
};

export default VisitingRequests;
