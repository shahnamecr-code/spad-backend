/* eslint-disable react-hooks/exhaustive-deps */
/* eslint-disable @next/next/no-img-element */
import Link from "next/link";
import styles from "./header.module.scss";
import Button from "../../general/button/Button";
import { AiOutlinePhone } from "@react-icons/all-files/ai/AiOutlinePhone";
import { AiOutlineUser } from "@react-icons/all-files/ai/AiOutlineUser";
import { AiOutlineHeart } from "@react-icons/all-files/ai/AiOutlineHeart";
import { FaTelegramPlane } from "@react-icons/all-files/fa/FaTelegramPlane";
import { FaInstagram } from "@react-icons/all-files/fa/FaInstagram";
import { FaFacebook } from "@react-icons/all-files/fa/FaFacebook";
import { FaWhatsapp } from "@react-icons/all-files/fa/FaWhatsapp";
import { FaRegCalendarAlt } from "@react-icons/all-files/fa/FaRegCalendarAlt";
import { HiLocationMarker } from "@react-icons/all-files/hi/HiLocationMarker";
import { IoIosMenu } from "@react-icons/all-files/io/IoIosMenu";
import { useEffect, useState } from "react";
import { addCountryCode, getStore, paths } from "../../../utils";
import { useRouter } from "next/router";
import { LOCATION } from "../../../utils/stores";
import { ILocationStore } from "../cityPopup/cityPopup.dev";
import { token } from "../../../services/sevices";
import LoginPopup from "../../pagesComponents/single/loginPopup/LoginPopup";
import { DropDown } from "../../general/dropdown";
import { profileNavItems } from "../../pagesComponents/profile/profileBox/ProfileBox";
import { useAppContext } from "../../../utils/context";

var moment = require("jalali-moment");

export const HeaderNavItems = [
  { title: "صفحه نخست", url: "/" },
  // { title: "مشاورین املاک", url: paths.realtors },
  { title: "آرشیو املاک", url: paths.search },
  { title: "آرشیو اخبار", url: paths.news },
  { title: "درباره ما", url: paths["about-us"] },
  { title: "تماس با ما", url: paths["contact-us"] },
  { title: "قوانین و مقررات", url: paths.rules },
  { title: "استخدام", url: paths.recruitment },
];

const mobileNavItems = [
  { title: "علاقه مندی ها", url: paths.favorites },
  { title: "تقاضای ملک", url: token() ? paths.request : paths.login },
  { title: "ثبت آگهی", url: token() ? paths.create : paths.login },
  {
    title: "کارشناسی ملک",
    url: token() ? paths["ad-expertise-request"] : paths.login,
  },
];

interface IProps {
  showCityPopup: boolean;
  setShowCityPopup: (value: boolean) => void;
  title: string;
}

const Header = (props: IProps) => {
  var m = moment();
  const [showMenu, setShowMenu] = useState(false);
  const router = useRouter();
  const [cityBtnLabel, setCityBtnLabel] = useState("انتخاب شهر");
  const [showLoginPopup, setShowLoginPopup] = useState(false);
  const context = useAppContext();

  useEffect(() => {
    if (!props.showCityPopup) {
      const location = Object.keys(context.location).length
        ? context.location
        : (JSON.parse(getStore(LOCATION) || "{}") as ILocationStore);
      if (location.province && !location.cities)
        setCityBtnLabel(location.province.name || "");
      else if (location.cities?.length === 1)
        setCityBtnLabel(location.cities[0].name || "");
      else if ((location.cities?.length || 0) > 1)
        setCityBtnLabel(`${location.cities?.length} شهر`);
    }
  }, [props.showCityPopup, getStore(LOCATION)]);

  return (
    <header className={styles.header}>
      <div className="container">
        <div className={styles["top-section"]}>
          <div className="flex-c">
            <a
              href={`tel:${context.options.header_phone}`}
              onClick={(e) => e.stopPropagation()}
              className={`${styles["icon-title"]} ${styles.phone}`}
            >
              <AiOutlinePhone />
              <span>{context.options.header_phone}</span>
            </a>
            <div className={`${styles["show-in-md"]} ${styles.divider}`} />
            <div className={`${styles.middle}`}>
              {context.options.telegram_account && (
                <Link href={`https://t.me/${context.options.telegram_account}`}>
                  <a className="flex" target="_blank">
                    <FaTelegramPlane />
                  </a>
                </Link>
              )}
              {context.options.instagram_account && (
                <Link
                  href={`https://www.instagram.com/${context.options.instagram_account}`}
                >
                  <a className="flex" target="_blank">
                    <FaInstagram />
                  </a>
                </Link>
              )}
              {context.options.facebook_account && (
                <Link
                  href={`https://www.facebook.com/${context.options.facebook_account}`}
                >
                  <a className="flex" target="_blank">
                    <FaFacebook />
                  </a>
                </Link>
              )}
              {context.options.whatsapp && (
                <Link
                  href={`https://web.whatsapp.com/send?phone=${addCountryCode(
                    context.options.whatsapp
                  )}&text=`}
                >
                  <a className="flex" target="_blank">
                    <FaWhatsapp />
                  </a>
                </Link>
              )}
            </div>
          </div>
          <div className={styles.end}>
            <Link href={paths.favorites}>
              <a className={`${styles["show-in-md"]} ${styles["icon-title"]}`}>
                <AiOutlineHeart />
                <span>علاقه مندی ها</span>
              </a>
            </Link>
            <div className={`${styles["show-in-md"]} ${styles.divider}`} />
            <div className={`${styles.calendar}`}>
              <div className={styles["icon-title"]}>
                <span>
                  {m.locale("fa").format("ddd") +
                    " " +
                    m.format("DD MMMM YYYY")}
                </span>
                <FaRegCalendarAlt />
              </div>
            </div>
            <div className={`${styles["show-in-md"]} ${styles.divider}`} />
            <Button
              type="outlined"
              color="white"
              className={styles["city-btn"]}
              onClick={() => props.setShowCityPopup(true)}
            >
              <HiLocationMarker />
              <span>{cityBtnLabel}</span>
            </Button>
            <div className={styles.divider} />
            {context.user?.full_name ? (
              <DropDown
                componentItem="title"
                items={profileNavItems.map((item) => {
                  return {
                    id: item.tab,
                    title: item.title,
                    link: `${paths.profile}?active_tab=${item.tab}`,
                    icon: item.icon,
                  };
                })}
                containerClassName={styles.dropdown_container}
                BtnComponent={
                  <>
                    <AiOutlineUser />
                    <p className={"xs-hide"}>{context.user.full_name}</p>
                  </>
                }
              />
            ) : (
              <Link href={paths.login}>
                <a className={styles["icon-title"]}>
                  <AiOutlineUser />
                  <span className={styles["login-span"]}>ورود / ثبت نام</span>
                </a>
              </Link>
            )}
          </div>
          <div className={styles.nav}>
            <div className="flex-c">
              {HeaderNavItems.map((item, i) => (
                <Link key={i} href={item.url}>
                  <a
                    className={`${styles.item} ${
                      router.pathname === item.url ? styles.active : ""
                    }`}
                  >
                    {item.title}
                  </a>
                </Link>
              ))}
            </div>
            <div className={`${styles.middle}`}>
              <Button
                color="gray"
                type="outlined"
                onClick={() =>
                  token()
                    ? router.push(paths["ad-expertise-request"])
                    : setShowLoginPopup(true)
                }
              >
                کارشناسی ملک
              </Button>
              <Button
                type="outlined"
                onClick={() =>
                  token() ? router.push(paths.request) : setShowLoginPopup(true)
                }
              >
                درخواست ملک
              </Button>
              <Button
                onClick={() =>
                  token() ? router.push(paths.create) : setShowLoginPopup(true)
                }
              >
                ثبت آگهی
              </Button>
            </div>

            <div
              className={`lg-hide ${styles.menu}`}
              onClick={() => setShowMenu((prev) => !prev)}
            >
              <IoIosMenu />
              <span>منو</span>
              <div
                className={styles["mobile-menu"]}
                style={showMenu ? { display: "flex" } : { display: "none" }}
              >
                {HeaderNavItems.concat(mobileNavItems).map((item, i) => (
                  <Link key={i} href={item.url}>
                    <a
                      className={`${styles["menu-item"]} ${
                        router.pathname === item.url ? styles.active : ""
                      }`}
                    >
                      {item.title}
                    </a>
                  </Link>
                ))}
              </div>
            </div>
            <Link href="/">
              <a style={{ height: 65 }} className="flex-c">
                <img
                  src={context.options.general_logo}
                  alt={props.title}
                  style={{ maxHeight: "85%", paddingInlineEnd: 24 }}
                />
              </a>
            </Link>
          </div>
        </div>
      </div>
      <LoginPopup
        visible={showLoginPopup}
        onHiding={() => setShowLoginPopup(false)}
        loginReason="ثبت آگهی"
      />
    </header>
  );
};

export default Header;
