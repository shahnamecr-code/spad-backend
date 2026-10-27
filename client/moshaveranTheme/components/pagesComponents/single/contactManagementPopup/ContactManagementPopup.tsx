import { ISingleAd } from "../../../../types/ads.types";
import Popup, { IPopupProps } from "../../../general/popup/Popup";
import RealtorInfo from "../realtorInfo/RealtorInfo";
import styles from "./contactManagementPopup.module.scss";

interface IProps extends IPopupProps {
  managers_list: ISingleAd["managers_list"]
}

const ContactManagementPopup = (props: IProps) => {
  return (
    <Popup width={400} {...props} title="تماس با هیئت مدیره">
      <div className={styles.container}>
        <div className="dark-box">
          جهت کسب اطلاعات بیشتر در خصوص آگهی با
          <span className="primary">مشاورین</span> تماس حاصل فرمایید.
        </div>
        {props.managers_list?.length > 0 ? (
          props.managers_list.map((item, i) => (
            <RealtorInfo item={item} key={i} />
          ))
        ) : (
          <p className={styles["not-exist"]}>سرپرستی وجود ندارد</p>
        )}
      </div>
    </Popup>
  );
};

export default ContactManagementPopup;
