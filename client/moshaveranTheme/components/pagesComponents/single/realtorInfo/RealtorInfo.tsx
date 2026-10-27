import { BiMessageDetail } from "@react-icons/all-files/bi/BiMessageDetail";
import { BiPhone } from "@react-icons/all-files/bi/BiPhone";
import Image from "next/image";
import { ISingleAd } from "../../../../types/ads.types";
import styles from "./realtorInfo.module.scss";

interface IProps {
  item: ISingleAd["managers_list"][number];
}

const RealtorInfo = (props: IProps) => {

  return (
    <div className={styles["realtor-box"]}>
      <div className={styles["realtor-box-info"]}>
        <Image src={props.item.image[0].url} width={70} height={70} alt="عکس پیشفرض" />
        <div className={styles["realtor-info"]}>
          <div className={styles["name-and-job__container"]}>
            {props.item && props.item?.job && (
              <p className={styles.job}>
                {props?.item?.job} {" : "}{" "}
              </p>
            )}
            <p className={styles.name}>{props.item.title}</p>
          </div>
          <span className={styles.mobile}>تلفن همراه: {props.item.phone}</span>
          <span className={styles.mobile}>داخلی : {props.item.inner_code}</span>
        </div>
      </div>
      <div className={styles["contact-box"]}>
        <a href={`sms:${props.item.phone}`} className={styles.sms}>
          <BiMessageDetail />
        </a>
        <a href={`tel:${props.item.phone}`} className={styles.tel}>
          <BiPhone />
        </a>
      </div>
    </div>
  );
};

export default RealtorInfo;
