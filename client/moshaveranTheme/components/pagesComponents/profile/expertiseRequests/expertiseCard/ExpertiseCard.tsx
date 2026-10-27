import styles from "./expertiseCard.module.scss";
import {
  IExpertiseRequest,
  IExpertiseRequestStatus,
} from "../../../../../types/profile.types";

interface IProps {
  info: IExpertiseRequest;
}

const ExpertiseCard = (props: IProps) => {
  return (
    <div className={styles.container}>
      <div className={styles["info-container"]}>
        <span className={styles.title}>{`درخواست ${props.info.id}`}</span>
        <p style={{ opacity: 0.9 }}>
          در دسته بندی
          {props.info.transaction && <span> {props.info.transaction}، </span>}
          {props.info.applicationType && (
            <span> {props.info.applicationType}، </span>
          )}
          {props.info.landType && <span> {props.info.landType}، </span>}
          به آدرس {props.info.province && <span> {props.info.province}، </span>}
          {props.info.city && <span> {props.info.city}، </span>}
          {props.info.address && <span> {props.info.address}، </span>} با موفقیت
          ثبت شد.
        </p>
        {/* <div
          className={`${styles.status} ${
            props.info.status === IExpertiseRequestStatus.wainting
              ? styles["waiting-bg"]
              : props.info.status === IExpertiseRequestStatus.approved
              ? styles["approved-bg"]
              : props.info.status === IExpertiseRequestStatus.rejected
              ? styles["rejected-bg"]
              : ""
          }`}
        >
          <span>{props.info.status_text}</span>
        </div> */}
      </div>
    </div>
  );
};

export default ExpertiseCard;
