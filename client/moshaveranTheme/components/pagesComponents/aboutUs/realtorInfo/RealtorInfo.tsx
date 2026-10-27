import { BiMessageDetail } from "@react-icons/all-files/bi/BiMessageDetail";
import { BiPhone } from "@react-icons/all-files/bi/BiPhone";
import Image from "next/image";
import { useRouter } from "next/router";
import { paths } from "../../../../utils";
import styles from "./realtorInfo.module.scss";

interface IProps {
  user_default_profile: string;
  info: {
    image?: any;
    name: string;
    mobile: string;
  };
}

const RealtorInfo = (props: IProps) => {
  const router = useRouter();

  return (
    <div
      className={styles["realtor-box"]}
      // onClick={() => {
      //   router.push(`${paths.realtors}/${props.info.name}`);
      // }}
    >
      <Image
        src={
          props.info?.image
            ? `${props.info?.image}`
            : `${props.user_default_profile}`
        }
        width={100}
        height={100}
        alt="عکس پیشفرض"
      />
      <span className={styles.name}>{props.info.name}</span>
      <div className={styles["contact-box"]}>
        <a
          href={`tel:${props.info.mobile}`}
          className={styles.tel}
          onClick={(e) => e.stopPropagation()}
        >
          <BiPhone />
          <span>تماس</span>
        </a>
        <a
          href={`sms:${props.info.mobile}`}
          className={styles.sms}
          onClick={(e) => e.stopPropagation()}
        >
          <BiMessageDetail /> <span>پیام</span>
        </a>
      </div>
    </div>
  );
};

export default RealtorInfo;
