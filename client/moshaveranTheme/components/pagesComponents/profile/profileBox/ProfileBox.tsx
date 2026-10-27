import Image from "next/image";
import styles from "./profileBox.module.scss";
import { HiOutlineMail } from "@react-icons/all-files/hi/HiOutlineMail";
import { HiOutlinePhone } from "@react-icons/all-files/hi/HiOutlinePhone";
import { AiOutlineHome } from "@react-icons/all-files/ai/AiOutlineHome";
import { AiOutlineEye } from "@react-icons/all-files/ai/AiOutlineEye";
import { FaUserTie } from "@react-icons/all-files/fa/FaUserTie";
import { FiMail } from "@react-icons/all-files/fi/FiMail";
import { HiOutlineCalendar } from "@react-icons/all-files/hi/HiOutlineCalendar";
import Button from "../../../general/button/Button";
import Link from "next/link";
import { paths } from "../../../../utils";
import { useRouter } from "next/router";
import { useProfileDev } from "../profile.dev";
import { useAppContext } from "../../../../utils/context";
import { BiEdit } from "@react-icons/all-files/bi/BiEdit";

export const profileNavItems = [
  { icon: <AiOutlineHome />, title: "آگهی های من", tab: "ads" },
  { icon: <AiOutlineEye />, title: "بازدید های اخیر", tab: "recently" },
  { icon: <FiMail />, title: "درخواست های من", tab: "requests" },
  // {
  //   icon: <HiOutlineCalendar />,
  //   title: "درخواست های بازدید",
  //   tab: "visitingRequests",
  // },
  {
    icon: <FaUserTie />,
    title: "درخواست های کارشناسی ملک",
    tab: "expertiseRequests",
  },
];

interface IProps {
  showUpdateProfilePopup?: () => void;
  showUpdatePassPopup?: () => void;
}

const ProfileBox = (props: IProps) => {
  const router = useRouter();
  const dev = useProfileDev();
  const context = useAppContext();
  console.log(context.options);
  return (
    <div className={styles.container}>
      <div className={styles["profile-pic-container"]}>
        {context.options?.user_default_profile && (
          <Image
            className={styles["profile-pic"]}
            width={80}
            height={80}
            src={`${context.options?.user_default_profile}`}
            alt="عکس پیشفرض کاربر"
          />
        )}
        <Button
          type="text"
          onClick={props.showUpdateProfilePopup}
          className={styles["edit-btn"]}
          title="ویرایش اطلاعات"
        >
          <BiEdit />
        </Button>
      </div>
      {context.user?.full_name && (
        <h1 className={styles["user-name"]}>{context.user?.full_name}</h1>
      )}
      <div className="divider" />
      {context.user?.email && (
        <div className={styles["user-info-item"]}>
          <HiOutlineMail />
          <span>{context.user?.email}</span>
        </div>
      )}
      {context.user?.mobile && (
        <div className={styles["user-info-item"]}>
          <HiOutlinePhone /> <span>{context.user?.mobile}</span>
        </div>
      )}
      <div className="divider" />
      <div className={styles.nav}>
        {profileNavItems.map((item, i) => (
          <Link key={i} href={`${paths.profile}?active_tab=${item.tab}`}>
            <a
              className={`${styles["nav-item"]} ${
                router.query.active_tab === item.tab ? styles["active-tab"] : ""
              }`}
            >
              {item.icon}
              <h2>{item.title}</h2>
            </a>
          </Link>
        ))}
      </div>
      <div className="divider" />
      <div className="btn-container">
        <Button
          type="outlined"
          color="gray"
          onClick={props.showUpdatePassPopup}
        >
          ویرایش رمز عبور
        </Button>
        <Button onClick={dev.logoutOnClick}>خروج</Button>
      </div>
    </div>
  );
};

export default ProfileBox;
