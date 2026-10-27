import React, { useState } from "react";
import { NavLink } from "react-router-dom";
import { Button, Modal } from "reactstrap";
import { siteUrl } from "../../constants/defaultValues";
import { Separator } from "../common/CustomBootstrap";
import "./shareAd.scss";

const ShareAd = ({ isOpen, toggleModal, advers }) => {
  const [isCopyLink, setIsCopLink] = useState(false);
  const [isCopyTemplate, setIsCopTemplate] = useState(false);
  const pageUrls = [];

  advers.forEach((adver) => {
    pageUrls.push(`${siteUrl}/ads/${adver.id}`);
  });

  const joinUrls = () => pageUrls.join("\n");

  const Template = ({ adver, index }) => {
    return (
      <div className="d-flex flex-column align-items-center justify-content-center">
        <img src={adver.image} className="mt-3" width={"100%"} height={300} />
        <p className="col-12 mt-3" style={{ textAlign: "center" }}>
          <b>{adver.title}</b> را به دیگران معرفی کنید
        </p>
        <p>کُد آگهی:{adver.id}</p>
        {adver.main_props?.map((prop) => (
          <p key={prop.title}>
            {prop.title}: {prop.value} {prop.unit}
          </p>
        ))}
        <p>
          🌐 لینک آگهی:
          <a href={pageUrls[index]} target="_blank" className="mx-1">
            {pageUrls[index]}
          </a>
        </p>
      </div>
    );
  };

  const renderTemplateText = () => {
    const template = advers.map(
      (adver) =>
        `${adver.title}
        کُد آگهی: ${adver.id}
        ${
          Object.keys(adver.main_props).map(
            (key) =>
              `${adver.main_props[key].title}: ${adver.main_props[key].value} ${adver.main_props[key].unit}`
          ) || ""
        }
        🌐 لینک آگهی:
        ${`${siteUrl}/ads/${adver.id}`}
        \n
    `
    );
    return template;
  };
  const renderTemplateTextForWebShare = () => {
    const template = advers.map(
      (adver) => `${adver.title}%0Aکُد آگهی: ${adver.id}${Object.keys(
        adver.main_props
      ).map(
        (key) =>
          `%0A${adver.main_props[key].title}: ${adver.main_props[key].value} ${adver.main_props[key].unit}`
      )}
        %0A🌐 لینک آگهی:
        %0A${`${siteUrl}/ads/${adver.id}`}%0A%0A`
    );
    return template;
  };

  const shareLinks = [
    {
      title: "تلگرام",
      url: `https://telegram.me/share/url?url=${renderTemplateTextForWebShare()}`,
      icon: <i className="fab fa-telegram"></i>,
    },
    {
      title: "واتساپ",
      url: `whatsapp://send?text=${renderTemplateTextForWebShare()}`,
      icon: <i className="fab fa-whatsapp"></i>,
    },
    {
      title: "توییتر",
      url: `https://twitter.com/share?url=${renderTemplateTextForWebShare()}`,
      icon: <i className="fab fa-twitter"></i>,
    },
  ];
  return (
    <Modal
      isOpen={isOpen}
      toggle={() => toggleModal()}
      backdrop
      wrapClassName="modal-right"
    >
      <div className="p-3">
        {advers.map((adver, index) => {
          return (
            <React.Fragment key={index}>
              <Template key={adver.id} adver={adver} index={index} />
              <Separator />
            </React.Fragment>
          );
        })}
        <div style={{ bottom: 15 }}>
          <div className={"links-container"}>
            {shareLinks.map((link, i) => (
              <NavLink to={link.url} target="_blank" key={i}>
                {link.icon}
                <span>{link.title}</span>
              </NavLink>
            ))}
          </div>

          <div className="col-12 d-flex align-items-center justify-content-around">
            <Button
              onClick={() => {
                navigator.clipboard.writeText(joinUrls());
                setIsCopLink(true);
              }}
              type="text"
              className={"link"}
            >
              {/* <IoCopyOutline fontSize={18} /> */}
              <span style={{ marginTop: 4 }}>
                {isCopyLink ? "کپی شد!" : "کپی لینک"}
              </span>
            </Button>
            <Button
              onClick={() => {
                navigator.clipboard.writeText(renderTemplateText());
                setIsCopTemplate(true);
              }}
              type="text"
              className={"link"}
            >
              {/* <IoCopyOutline fontSize={18} /> */}
              <span style={{ marginTop: 4 }}>
                {isCopyTemplate ? "قالب کپی شد!" : "کپی قالب"}
              </span>
            </Button>
          </div>
        </div>
      </div>
    </Modal>
  );
};

export default ShareAd;
