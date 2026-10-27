import styles from "./visitingRequestCard.module.scss";
import Button from "../../../../general/button/Button";
import { AiOutlineEye } from "@react-icons/all-files/ai/AiOutlineEye";
import { AiOutlineDelete } from "@react-icons/all-files/ai/AiOutlineDelete";
import { AiOutlineEdit } from "@react-icons/all-files/ai/AiOutlineEdit";
import { useRouter } from "next/router";
import {
  IVisitingRequest,
  IVisitingRequestStatus,
} from "../../../../../types/profile.types";

interface IProps {
  info: IVisitingRequest;
}

const VisitingRequestCard = (props: IProps) => {
  const router = useRouter();

  return (
    <div className={styles.container}>
      <div className={styles["times-container"]}>
        {props.info?.times?.map((item, i) => (
          <div key={i}>
            <span className={styles.primary}>زمان بازدید: </span>
            <span>{item.time_text} </span>
            {item.status && (
              <span className={styles.approved}>({item.time_text})</span>
            )}
          </div>
        ))}
      </div>
      <div className={styles["info-container"]}>
        <span className={styles.code}>
          کد آگهی:{" "}
          <span className={styles.primary}>{props.info?.advertisement.id}</span>
        </span>
        <div className="flex-c w-100">
          <span
            className={styles.title}
            onClick={() => router.push(`/ads/${props.info?.advertisement.id}`)}
          >
            {props.info?.advertisement.title}
          </span>
          <div className={styles["btn-container"]}>
            <Button
              type="text"
              title="مشاهده آکهی"
              onClick={() =>
                router.push(`/ads/${props.info?.advertisement.id}`)
              }
            >
              <AiOutlineEye />
            </Button>
            <Button type="text" title="حذف">
              <AiOutlineDelete />
            </Button>
            <Button type="text" title="ویرایش زمان بازدید">
              <AiOutlineEdit />
            </Button>
          </div>
        </div>
        <div
          className={`${styles.status} ${
            props.info.status === IVisitingRequestStatus.init
              ? styles["waiting-bg"]
              : props.info.status === IVisitingRequestStatus.done
              ? styles["approved-bg"]
              : props.info.status === IVisitingRequestStatus.userCanceled ||
                props.info.status === IVisitingRequestStatus.adminCanceled ||
                props.info.status === IVisitingRequestStatus.expired
              ? styles["rejected-bg"]
              : props.info.status === IVisitingRequestStatus.userChanded ||
                props.info.status === IVisitingRequestStatus.adminChanded
              ? styles["waiting-bg"]
              : ""
          }`}
        >
          <span>{props.info.status_text}</span>
        </div>
      </div>
    </div>
  );
};

export default VisitingRequestCard;
