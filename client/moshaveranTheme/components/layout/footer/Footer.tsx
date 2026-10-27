import styles from "./footer.module.scss";
import { FaTelegramPlane } from "@react-icons/all-files/fa/FaTelegramPlane";
import { FaInstagram } from "@react-icons/all-files/fa/FaInstagram";
import { FaFacebook } from "@react-icons/all-files/fa/FaFacebook";
import { FaWhatsapp } from "@react-icons/all-files/fa/FaWhatsapp";
import { HiOutlineLocationMarker } from "@react-icons/all-files/hi/HiOutlineLocationMarker";
import { HiOutlineMail } from "@react-icons/all-files/hi/HiOutlineMail";
import { HiOutlinePhone } from "@react-icons/all-files/hi/HiOutlinePhone";
import Link from "next/link";
import { AppContextType, useAppContext } from "../../../utils/context";
import { addCountryCode, renderHtml } from "../../../utils";
import { GrAndroid } from "@react-icons/all-files/gr/GrAndroid";
import { FaApple } from "@react-icons/all-files/fa/FaApple";
import { IoCloudDownloadSharp } from "@react-icons/all-files/io5/IoCloudDownloadSharp";

export const contactItems = (context: AppContextType) => [
  {
    icon: <HiOutlineLocationMarker />,
    text: context.options.address || "",
  },
  {
    icon: <HiOutlineMail />,
    text: context.options.email || "",
    url: `mailto:${context.options.email}`,
  },
  {
    icon: <HiOutlinePhone />,
    text: context.options.header_phone || "",
    url: `tel:${context.options.header_phone}`,
  },
];

export const downloadAppItems = (context: AppContextType) => [
  context.options.direct_app_download
    ? {
        url: context.options.direct_app_download,
        icon: <IoCloudDownloadSharp />,
        name: "دانلود مستقیم",
      }
    : undefined,
  context.options.android_app_download
    ? {
        url: context.options.android_app_download,
        icon: <GrAndroid />,
        name: "اپلیکیشن اندروید",
      }
    : undefined,
  context.options.ios_app_download
    ? {
        url: context.options.ios_app_download,
        icon: <FaApple />,
        name: "اپلیکیشن ios",
      }
    : undefined,
];

const Footer = () => {
  const context = useAppContext();

  return (
    <footer className={styles.footer}>
      <div className={`container ${styles["footer-container"]}`}>
        {context.options.enamad && (
          <div className={styles.credit}>
            <h3 className={styles.title}>
              اعتبارات {context.options.general_site_name || ""}
            </h3>
            <div
              dangerouslySetInnerHTML={{
                __html: context.options.enamad,
              }}
            />
          </div>
        )}
        <div className={styles.about}>
          <h3 className={styles.title}>
            درباره {context.options.general_site_name || ""}
          </h3>
          <p>{context.options.footer_description || ""}</p>
        </div>
        <div className={styles.contact}>
          <h3 className={styles.title}>تماس با ما</h3>
          <div className="flex-col">
            {contactItems(context).map((item, i) => (
              <div key={i} className="contact-item">
                {item.icon}
                <a href={item.url}>{item.text}</a>
              </div>
            ))}
          </div>
          <h3 className={styles.title}>شبکه های اجتماعی</h3>
          <div className={styles["social-media"]}>
            {context.options.telegram_account && (
              <Link href={`https://t.me/${context.options.telegram_account}`}>
                <a className={`flex-c ${styles.telegram}`} target="_blank">
                  <span className={styles["link-before"]}>تلگرام</span>
                  <FaTelegramPlane />
                </a>
              </Link>
            )}
            {context.options.instagram_account && (
              <Link
                href={`https://www.instagram.com/${context.options.instagram_account}`}
              >
                <a className="flex-c" target="_blank">
                  <span className={styles["link-before"]}>اینستاگرام</span>
                  <FaInstagram />
                </a>
              </Link>
            )}
            {context.options.facebook_account && (
              <Link
                href={`https://www.facebook.com/${context.options.facebook_account}`}
              >
                <a className="flex-c" target="_blank">
                  <span className={styles["link-before"]}>فیسبوک</span>
                  <FaFacebook />
                </a>
              </Link>
            )}
            {context.options.whatsapp && (
              <Link
                href={`https://web.whatsapp.com/send?phone=${addCountryCode(
                  context.options.whatsapp
                )}&text=`}
                target="_blank"
              >
                <a className="flex-c" target="_blank">
                  <span className={styles["link-before"]}>واتساپ</span>
                  <FaWhatsapp />
                </a>
              </Link>
            )}
          </div>
          {downloadAppItems(context).filter((x) => x).length > 0 && (
            <>
              <h3 className={styles.title}>دانلود اپلیکیشن</h3>
              <div className={`${styles["app-container"]}`}>
                {downloadAppItems(context).map(
                  (item, i) =>
                    item?.url && (
                      <Link key={i} href={item.url || ""}>
                        <a
                          target="_blank"
                          className={`flex-c ${styles["icon-link"]}`}
                          title={item.name}
                        >
                          {item.icon}
                        </a>
                      </Link>
                    )
                )}
              </div>
            </>
          )}
        </div>
      </div>
      <div className={styles.copyright}>
        <div
          dangerouslySetInnerHTML={{
            __html: renderHtml(context.options.copy_right || ""),
          }}
        />
        <div
          dangerouslySetInnerHTML={{
            __html: renderHtml(context.options.developer_text || ""),
          }}
        />
      </div>
    </footer>
  );
};

export default Footer;
