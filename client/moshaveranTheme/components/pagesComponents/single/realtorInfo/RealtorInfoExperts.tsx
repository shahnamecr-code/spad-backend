import { BiMessageDetail } from "@react-icons/all-files/bi/BiMessageDetail";
import { BiPhone } from "@react-icons/all-files/bi/BiPhone";
import Image from "next/image";
import { ISingleAd } from "../../../../types/ads.types";
import styles from "./realtorInfo.module.scss";

interface IProps {
  item: 
  ISingleAd["admins"]["experts"][number];
}

const RealtorInfoExperts = (props: IProps) => {
  return (
    <div className={styles["realtor-box"]}>
      {/* <Image src={process.env.NEXT_PUBLIC_STORAGE_URL +"/" +  props.item.image} width={70} height={70} /> */}
      <div className={styles["realtor-info"]}>
        <div className={styles["name-and-job__container"]}>
          <p className={styles.name}>{props.item.display_name}</p>
        </div>
        <span className={styles.mobile}>تلفن همراه: {props.item.mobile}</span>
      </div>
      <div className={styles["contact-box"]}>
        <a href={`sms:${props.item.mobile}`} className={styles.sms}>
          <BiMessageDetail />
        </a>
        <a href={`tel:${props.item.mobile}`} className={styles.tel}>
          <BiPhone />
        </a>
      </div>
    </div>
  );
};

export default RealtorInfoExperts;
