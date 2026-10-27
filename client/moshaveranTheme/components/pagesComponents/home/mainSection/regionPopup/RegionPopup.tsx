import React, { useEffect } from "react";
import Button from "../../../../general/button/Button";
import Popup, { IPopupProps } from "../../../../general/popup/Popup";
import { useRegionPopupDev } from "./regionPopup.dev";
import styles from "./regionPopup.module.scss";
import { IoIosArrowForward } from "@react-icons/all-files/io/IoIosArrowForward";
import { MdCheckBoxOutlineBlank } from "@react-icons/all-files/md/MdCheckBoxOutlineBlank";
import { MdCheckBox } from "@react-icons/all-files/md/MdCheckBox";
import InfiniteScroll from "react-infinite-scroll-component";
import Image from "next/image";
import { loading } from "../../../../../assets/images/export";

const RegionPopup = (props: IPopupProps) => {
  const dev = useRegionPopupDev();

  useEffect(() => {
    dev.setQ(undefined);
    if (props.visible) dev.fetchData();
    else {
      dev.setRegion({});
      dev.setRegions([]);
    }
  }, [props.visible]);

  return (
    <Popup width={1000} {...props} title="انتخاب منطقه" hideCloseBtn>
      <div className={styles.container}>
        <form
          onSubmit={(e) => {
            e.preventDefault();
            dev.searchRegions();
          }}
          className={styles["input-container"]}
        >
          <input
            value={dev.q || ""}
            onChange={(e) => dev.setQ(e.target.value)}
            placeholder={`جستجو منطقه`}
          />
          <Button btnType="submit" width={120}>
            جستجو
          </Button>
        </form>
        {/* {dev.loading && <Loading />} */}{" "}
        <div className={styles["infinite-container"]} id="scrollableDiv">
          <InfiniteScroll
            dataLength={dev.regions.filter((x) => x.show).length || 0}
            next={() => {
              dev.fetchData(true);
            }}
            hasMore={dev.region.next_page_url ? true : false}
            loader={
              <div className="w-100 flex-c">
                <Image
                  src={loading}
                  width={50}
                  height={50}
                  style={{ alignSelf: "center" }}
                  alt="loading"
                />
              </div>
            }
            scrollableTarget="scrollableDiv"
          >
            <div className={styles["items-container"]}>
              <div className={styles["all-provinces-container"]}>
                <Button
                  onClick={() => {
                    dev.setRegions([]);
                    dev.setRegion({ selectAll: true });
                    dev.setQ("");
                  }}
                  type="text"
                >
                  <IoIosArrowForward />
                  همه ی مناطق
                </Button>
              </div>
              {dev.regions
                .filter((x) => x.show)
                .map((item, i) => (
                  <div
                    key={i}
                    onClick={() => {
                      if (item.selectAll) {
                        dev.setRegion({
                          ...dev.region,
                          regions: dev.region.selectAll ? [] : dev.regions,
                          selectAll: !dev.region.selectAll,
                        });
                      } else dev.regionOnClick(item);
                    }}
                    className={styles["province-item"]}
                    title={item.name}
                  >
                    {(
                      item.selectAll
                        ? dev.region.selectAll
                        : dev.region.regions &&
                          dev.region.regions.findIndex((x) => x.id == item.id) >
                            -1
                    ) ? (
                      <MdCheckBox className={styles.selected} />
                    ) : (
                      <MdCheckBoxOutlineBlank />
                    )}
                    <label>{item.name}</label>
                  </div>
                ))}
            </div>
          </InfiniteScroll>
        </div>
        <div className={styles["btns-container"]}>
          <Button
            width={150}
            onClick={() => {
              props.onHiding();
            }}
            type="outlined"
          >
            انصراف
          </Button>
          <Button
            disabled={!dev.region.regions || dev.region.regions?.length === 0}
            width={150}
            onClick={() => {
              dev.submitPopup();
              props.onHiding();
            }}
          >
            تأیید
          </Button>
        </div>
      </div>
    </Popup>
  );
};

export default RegionPopup;
