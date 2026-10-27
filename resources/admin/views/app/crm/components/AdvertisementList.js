import classnames from "classnames";
import React, { useEffect, useState } from "react";
import { ContextMenu, ContextMenuTrigger, MenuItem } from "react-contextmenu";
import { NavLink } from "react-router-dom";
import { VirtuosoGrid } from "react-virtuoso";
import { Card, Row } from "reactstrap";
import { Colxx } from "../../../../components/common/CustomBootstrap";
import { adminPathApi, adminRoot } from "../../../../constants/defaultValues";
import UserCan from "../../../../helpers/UserCan";
import useMousetrap from "../../../../hooks/use-mousetrap";
import IconTooltip from "./IconTooltip";
import AdvertisementPreview from "../../advertisements/components/AdvertisementPreview";
import ShareAd from "../../../../components/shareAd/ShareAd";
import classes from "../../advertisements/components/css/advertismentList.module.css";
import axios from "axios";

function collect(props) {
  return { data: props.data };
}

const getIndex = (value, arr, prop) => {
  for (let i = 0; i < arr.length; i += 1) {
    if (arr[i][prop] === value) {
      return i;
    }
  }
  return -1;
};

const AdvertisementList = ({
  advertisements,
  setAdvertisements,
  link,
  setLink,
  selectedItems,
  setSelectedItems,
  lastChecked,
  setLastChecked,
}) => {
  const [showShareModal, setShowShareModal] = useState(false);
  const [selectedAdvers, setSelectedAdvers] = useState([]);
  const [showPreviewModal, setShowPreviewModal] = useState(false)
  const [advertisementToPreview, setAdvertisementToPreview] = useState(false)
  useEffect(() => {
    axios
      .get(link)
      .then((res) => res.data)
      .then((data) => {
        setAdvertisements(data.data);
      });
  }, []);

  const sendSms = () => {
    axios
      .post(`${adminPathApi}/crm/share-ads/${id}`, {
        ads: [idz],
      })
      .then((res) => res.data)
      .then((data) => {
        if (data.status) {
        }
      });
  };

  const share = (adsList) => {
    const advers = [];
    adsList.map((advId) => {
      const existingItem = advertisements.find((adv) => adv.id === advId);
      if (existingItem) advers.push(existingItem);
    });
    setSelectedAdvers(advers);
  };

  const ItemContainer = (props) => {
    return (
      <Colxx md={3} {...props}>
        {props.children}
      </Colxx>
    );
  };

  const onContextMenu = (e, data) => {
    const clickedProductId = data.data;
    if (!selectedItems.includes(clickedProductId)) {
      setSelectedItems([clickedProductId]);
    }

    return true;
  };

  const onContextMenuClick = (e, data) => {
    // console.log(data.data)
    switch (data.action) {
      case "share":
        setShowShareModal(true);
        share(selectedItems);
        break;
    }
  };
  const open_preview_modal = (ads) => {
    setAdvertisementToPreview(ads)
    setShowPreviewModal(true)
  }

  const onCheckItem = (event, ads) => {
    let id = ads.id;
    open_preview_modal(ads);
    if (
      event.target.tagName === "A" ||
      (event.target.parentElement && event.target.parentElement.tagName === "A")
    ) {
      return true;
    }
    if (lastChecked === null) {
      setLastChecked(id);
    }

    let selectedList = [...selectedItems];
    if (selectedList.includes(id)) {
      selectedList = selectedList.filter((x) => x !== id);
    } else {
      selectedList.push(id);
    }
    setSelectedItems(selectedList);

    if (event.shiftKey) {
      let newItems = [...advertisements];
      const start = getIndex(id, newItems, "id");
      const end = getIndex(lastChecked, newItems, "id");
      newItems = newItems.slice(Math.min(start, end), Math.max(start, end) + 1);
      selectedItems.push(
        ...newItems.map((item) => {
          return item.id;
        })
      );
      selectedList = Array.from(new Set(selectedItems));
      setSelectedItems(selectedList);
    }
    document.activeElement.blur();
    return false;
  };

  const handleChangeSelectAll = (isToggle) => {
    if (selectedItems.length >= advertisements.length) {
      if (isToggle) {
        setSelectedItems([]);
      }
    } else {
      setSelectedItems(advertisements.map((x) => x.id));
    }
    document.activeElement.blur();
    return false;
  };

  useMousetrap(["ctrl+a", "command+a"], () => {
    handleChangeSelectAll(false);
  });

  useMousetrap(["ctrl+d", "command+d"], () => {
    setSelectedItems([]);
    return false;
  });

  const ItemWrapper = ({ ads, isSelect, collect, ...props }) => {
    return (
      <ContextMenuTrigger id="menu_id" data={ads.id} collect={collect}>
        <Card
          key={ads.id}
          onClick={(event) => onCheckItem(event, ads)}
          className={classnames(
            "ads-card",
            { active: isSelect },
            classes.mahdi
          )}
        >
          <Row>
            <Colxx xs={12}>
              <div className={"position-relative"}>
                <div
                  className="d-flex"
                >
                  <img
                    alt={ads.title}
                    src={ads.image}
                    className="ads-card-thumbnail"
                  />
                </div>
                <div className={"ads-img-info justify-content-between bg-primary"}>
                  <p className={"ads-detail ml-2"}>
                    <span className={"simple-icon-clock"} />
                    <span className={"ml-1"}>{ads.date}</span>
                  </p>
                </div>
              </div>
            </Colxx>
            <Colxx xs={12}>
              <div
                className={`py-3 d-flex flex-column justify-content-center flex-grow-1 min-width-zero ${classes.px_2}`}
              >
                <div>
                  {ads.title}
                </div>
                <p
                  className={
                    "ads-detail mt-2 d-flex justify-content-between border-bottom"
                  }
                >
                  <span className="ads-detail-title">کد آگهی</span>
                  <span className="ads-detail-value">{ads.id}</span>
                </p>
                <p
                  className={
                    "ads-detail mt-2 d-flex justify-content-between border-bottom"
                  }
                >
                  <span className="ads-detail-title">موقعیت</span>
                  <span className="ads-detail-value">{`${ads.city} ${ads.region}`}</span>
                </p>
                <p
                  className={
                    "ads-detail mt-2 d-flex justify-content-between border-bottom"
                  }
                >
                  <span className="ads-detail-title">دسته بندی</span>
                  <span className={"ml-1"}>{ads.category}</span>
                </p>
                <p
                  className={
                    "ads-detail mt-2 d-flex justify-content-between border-bottom"
                  }
                >
                  <span className="ads-detail-title">وضعیت</span>
                  <span className={`ads-detail-value text-${ads.status_class}`}>
                    {ads.status_text}
                  </span>
                </p>
                {ads.reject_reason ? (
                  <p className={"ads-detail"}>
                    <span className="ads-detail-title">علت رد</span>
                    <span className={`ads-detail-value`}>
                      {ads.reject_reason}
                    </span>
                  </p>
                ) : (
                  ""
                )}
                <div className={"ads-main-props"}>
                  {ads.main_props &&
                    ads.main_props.map((prop, index) => (
                      <p className={"ads-detail"} key={index}>
                        <span className="ads-detail-title">{prop.title}</span>
                        <span className="ads-detail-value">{`${prop.value} ${
                          prop.unit ?? ""
                        }`}</span>
                      </p>
                    ))}
                </div>
                <div className={"ads-secondary-props"}>
                  {ads.secondary_props &&
                    ads.secondary_props.map((prop, index) => (
                      <p className={"ads-detail"} key={index}>
                        {prop.icon ? (
                          <IconTooltip
                            prop={prop}
                            id={`${ads.id}_secondary_${index}`}
                            placement={"bottom"}
                          />
                        ) : (
                          <span className="ads-detail-title">{prop.title}</span>
                        )}
                        <span className="ads-detail-value">{`${prop.value} ${
                          prop.unit ?? ""
                        }`}</span>
                      </p>
                    ))}
                </div>
              </div>
            </Colxx>
          </Row>
        </Card>
      </ContextMenuTrigger>
    );
  };

  return (
    <Colxx md={12} className={"ads-wrapper"}>
      <VirtuosoGrid
        style={{ height: 600 }}
        totalCount={advertisements.length}
        overscan={300}
        components={{
          Item: ItemContainer,
          List: React.forwardRef(({ children, ...props }, ref) => {
            return (
              <div
                ref={ref}
                style={{ margin: 0, padding: 0 }}
                {...props}
                className={"row ss"}
              >
                {children}
                <ContextMenu
                  id="menu_id"
                  onShow={(e) => {
                    onContextMenu(e, e.detail.data);
                  }}
                >
                  <UserCan to={"share.advertisement"}>
                    <MenuItem
                      onClick={onContextMenuClick}
                      data={{ action: "share" }}
                    >
                      <i className="simple-icon-docs" />{" "}
                      <span>اشتراک گذاری</span>
                    </MenuItem>
                    {/* <MenuItem
                      onClick={onContextMenuClick}
                      data={{ action: "sms" }}
                    >
                      <i className="simple-icon-sms" /> <span>ارسال پیامک</span>
                    </MenuItem> */}
                  </UserCan>
                </ContextMenu>
              </div>
            );
          }),
        }}
        itemContent={(index) => {
          let ads = advertisements[index];
          return (
            <ItemWrapper
              ads={ads}
              collect={collect}
              isSelect={selectedItems.includes(ads.id)}
            />
          );
        }}
      />

      <ShareAd
        advers={selectedAdvers}
        isOpen={showShareModal}
        toggleModal={() => setShowShareModal(!showShareModal)}
      />
       {showPreviewModal ? (
        <AdvertisementPreview
          isOpen={showPreviewModal}
          toggle={() => setShowPreviewModal(!showPreviewModal)}
          ads={advertisementToPreview}
        />
      ) : ''}
    </Colxx>
  );
};

export default AdvertisementList;
