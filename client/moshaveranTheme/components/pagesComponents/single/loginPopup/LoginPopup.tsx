import Link from "next/link";
import { paths, urls } from "../../../../utils";
import Button from "../../../general/button/Button";
import Popup, { IPopupProps } from "../../../general/popup/Popup";
import styles from "./loginPopup.module.scss";

interface IProps extends IPopupProps {
  loginReason?: string;
}

const LoginPopup = (props: IProps) => {
  return (
    <Popup width={400} {...props} title="ثبت مشکل آگهی">
      <div className={styles.container}>
        <h3 className={styles["main-title"]}>وارد حساب کاربری خود شوید.</h3>
        <p className={styles.title}>
          برای {props.loginReason || "ثبت تخلف آگهی"} ابتدا باید وارد حساب
          کاربری خود شوید.
        </p>

        <div className="flex w-100">
          <Button
            onClick={props.onHiding}
            className={styles["cancel-btn"]}
            type="outlined"
          >
            انصراف
          </Button>
          <Link href={paths.login}>
            <Button className={styles["confirm-btn"]}>ورود / ثبت نام</Button>
          </Link>
        </div>
      </div>
    </Popup>
  );
};

export default LoginPopup;
