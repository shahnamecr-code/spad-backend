import { useEffect, useState } from "react";
import { getMyRequests } from "../../../../services/ProfileServices";
import { IPagingResponse } from "../../../../types/general.types";
import { IMyRequest } from "../../../../types/profile.types";
import { useLoadingContext } from "../../../../utils/context";
import Paging from "../../../general/paging/Paging";
import styles from "./myRequests.module.scss";
import RequestCard from "./requestCard/RequestCard";

const MyRequests = () => {
  const loadingContext = useLoadingContext();
  const [requests, setRequests] = useState<IPagingResponse<IMyRequest>>();

  useEffect(() => {
    getPagingRequests();
  }, []);

  const getPagingRequests = (url?: string) => {
    loadingContext.setLoading(true);

    getMyRequests(url)
      .then((res) => setRequests(res.data))
      .finally(() => loadingContext.setLoading(false));
  };

  return (
    <div className={styles.container}>
      {requests?.data.length === 0 ? (
        <span className={styles["not-exist"]}>(اطلاعاتی وجود ندارد)</span>
      ) : (
        <div className={styles["items-container"]}>
          {requests?.data?.map((item, i) => (
            <RequestCard key={i} info={item} />
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

export default MyRequests;
