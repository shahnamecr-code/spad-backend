import Link from "next/link";
import { FiInstagram } from "@react-icons/all-files/fi/FiInstagram";
import { ImWhatsapp } from "@react-icons/all-files/im/ImWhatsapp";
import { FaTelegramPlane } from "@react-icons/all-files/fa/FaTelegramPlane";
import { FiTwitter } from "@react-icons/all-files/fi/FiTwitter";
import { IoCopyOutline } from "@react-icons/all-files/io5/IoCopyOutline";
import { useState } from "react";
import Button from "../button/Button";
import { IAd } from "../../../types/ads.types";
import styels from "./shareAd.module.scss";

interface IProps {
  adInfo?: IAd;
}

const ShareAd = (props: IProps) => {
  const [isCopyLink, setIsCopLink] = useState(false);
  const [isCopyTemplate, setIsCopTemplate] = useState(false);

  const pageUrl = `${window.location.origin}/ads/${props.adInfo?.id}`;

  const template = `${props.adInfo?.title}
  ${props.adInfo?.region || ""}
  کُد آگهی: ${props.adInfo?.id}
  ${
    props.adInfo?.main_props?.map(
      (prop) =>
        `${prop.title ? prop.title : ""}: ${prop.value ? prop.value : ""} ${
          prop.unit ? prop.unit : ""
        }`
    ) || ""
  }
  🌐 لینک آگهی: 
  ${pageUrl}`;

  const shareLinks = [
    {
      title: "تلگرام",
      url: `https://telegram.me/share/url?url=${pageUrl}?ref=telegram`,
      icon: <FaTelegramPlane fontSize={20} />,
    },
    {
      title: "اینستاگرام",
      url: `https://www.instagram.com/?url=${pageUrl}`,
      icon: <FiInstagram fontSize={20} />,
    },
    {
      title: "واتساپ",
      url: `whatsapp://send?text=${template}`,
      icon: <ImWhatsapp fontSize={18} />,
    },
    {
      title: "توییتر",
      url: `https://twitter.com/share?url=${pageUrl}?ref=twitter&text=${props.adInfo?.title}`,
      icon: <FiTwitter fontSize={18} />,
    },
  ];
  return (
    <div className={styels.container}>
      <span className={styels["sub-title"]}>
        این ملک را به دیگران معرفی کنید
      </span>
      <div className={styels["links-container"]}>
        {shareLinks.map((link, i) => (
          <Link href={link.url} key={i}>
            <a
              href={link.url}
              target="_blank"
              key={i}
              className={styels.link}
              rel="noreferrer"
            >
              {link.icon}
              <span>{link.title}</span>
            </a>
          </Link>
        ))}
        <Button
          onClick={() => {
            navigator.clipboard.writeText(pageUrl);
            setIsCopLink(true);
          }}
          type="text"
          className={styels.link}
        >
          <IoCopyOutline fontSize={18} />
          <span style={{ marginTop: 4 }}>
            {isCopyLink ? "کپی شد!" : "کپی لینک"}
          </span>
        </Button>
        <Button
          onClick={() => {
            navigator.clipboard.writeText(template);
            setIsCopTemplate(true);
          }}
          type="text"
          className={styels.link}
        >
          <IoCopyOutline fontSize={18} />
          <span style={{ marginTop: 4 }}>
            {isCopyTemplate ? "قالب کپی شد!" : "کپی قالب"}
          </span>
        </Button>
      </div>
    </div>
  );
};

export default ShareAd;
