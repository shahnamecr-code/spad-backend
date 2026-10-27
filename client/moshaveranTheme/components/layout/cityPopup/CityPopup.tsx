import React, { useEffect } from "react";
import Button from "../../general/button/Button";
import Popup, { IPopupProps } from "../../general/popup/Popup";
import { useCityPopupDev } from "./cityPopup.dev";
import styles from "./cityPopup.module.scss";
import { IoIosArrowForward } from "@react-icons/all-files/io/IoIosArrowForward";
import { MdCheckBoxOutlineBlank } from "@react-icons/all-files/md/MdCheckBoxOutlineBlank";
import { MdCheckBox } from "@react-icons/all-files/md/MdCheckBox";
import SimpleLoading from "../../general/simpleLoading/SimpleLoading";
import { getStore } from "../../../utils";
import { LOCATION } from "../../../utils/stores";

const CityPopup = (props: IPopupProps) => {
  const dev = useCityPopupDev(props);

  useEffect(() => {
    dev.setQ("");
  }, [props.visible]);

  return (
    <Popup width={1000} {...props} title="انتخاب شهر" hideCloseBtn>
      <div className={styles.container}>
        <form
          onSubmit={(e) => {
            e.preventDefault();
            if (dev.position.province) dev.searchCities();
            else dev.searchProvince();
          }}
          className={styles["input-container"]}
        >
          <input
            value={dev.q}
            onChange={(e) => dev.setQ(e.target.value)}
            placeholder={`جستجو ${dev.position.province ? "شهر" : "استان"}`}
          />
          <Button btnType="submit" width={120}>
            جستجو
          </Button>
        </form>
        <div className={styles["items-container"]}>
          {dev.loading && <SimpleLoading />}
          {dev.position.province ? (
            <>
              <div className={styles["all-provinces-container"]}>
                <Button
                  onClick={() => {
                    dev.setCities([]);
                    dev.setPosition({});
                    dev.setSelectAll(false);
                    dev.setQ("");
                    dev.setInitialProvinces();
                  }}
                  type="text"
                >
                  <IoIosArrowForward />
                  همه ی استان ها
                </Button>
              </div>
              {dev.cities
                .filter((x) => x.show)
                .map((item, i) => (
                  <div
                    key={i}
                    onClick={() => {
                      if (item.selectAll) {
                        dev.setPosition({
                          ...dev.position,
                          cities: dev.selectAll ? [] : dev.cities,
                        });
                        dev.setSelectAll((prev) => !prev);
                      } else dev.cityOnClick(item);
                    }}
                    className={styles["province-item"]}
                    title={item.name}
                  >
                    {(
                      item.selectAll
                        ? dev.selectAll
                        : dev.position.cities &&
                          dev.position.cities.findIndex(
                            (x) => x.id === item.id
                          ) > -1
                    ) ? (
                      <MdCheckBox className={styles.selected} />
                    ) : (
                      <MdCheckBoxOutlineBlank />
                    )}
                    <label>{item.name}</label>
                  </div>
                ))}
            </>
          ) : (
            dev.provinces
              .filter((x) => x.show)
              .map((item, i) => (
                <div
                  key={i}
                  onClick={() => {
                    dev.setPosition({ province: item, cities: [] });
                    dev.setQ("");
                  }}
                  className={styles["province-item"]}
                  title={item.name}
                >
                  <label>{item.name}</label>
                </div>
              ))
          )}
        </div>
        <div className={styles["btns-container"]}>
          <Button
            width={150}
            onClick={() => {
              props.onHiding();
              dev.onCancell();
            }}
            type="outlined"
            disabled={
              !Object.keys(JSON.parse(getStore(LOCATION) || "{}")).length
            }
          >
            انصراف
          </Button>
          <Button
            disabled={!dev.position.cities || dev.position.cities?.length === 0}
            width={150}
            className={styles["confirm-btn"]}
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

export default CityPopup;
