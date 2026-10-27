import dynamic from "next/dynamic";
import React from "react";
import { IPagingCity } from "../../../../types/createAd.types";
import { getFromOjectByKey } from "../../../../utils";
import CitySelect from "../../../general/citySelect/CitySelect";
import { Select } from "../../../general/select/Select";
import TextArea from "../../../general/textArea/TextArea";
import styles from "./adLocation.module.scss";
import { useLocationDev } from "./location.dev";

interface IProps {
  values: any;
  setFieldValue: (
    field: string,
    value: any,
    shouldValidate?: boolean | undefined
  ) => void;
  errMsgs?: { [key: string]: string[] };
  hideMap?: boolean;
  hideAddress?: boolean;
  regionIsMulty?: boolean;
  showRegion?: boolean;
}

const AdLocation = ({
  values,
  setFieldValue,
  errMsgs,
  hideAddress,
  hideMap,
  regionIsMulty,
  showRegion,
}: IProps) => {
  const dev = useLocationDev(values);

  const MapWithNoSSR = dynamic(() => import("../../../general/Map/Map"), {
    ssr: false,
  });

  return (
    <div className={styles.container}>
      <div className={styles["fields-container"]}>
        <CitySelect
          label="شهر"
          onChange={(e) => {
            setFieldValue("position.city", e);
            setFieldValue("position.region", undefined);
            setFieldValue("position.location", { lat: e?.lat, lng: e?.lng });
          }}
          className={"col-md-6 col-12"}
          validationMessage={
            errMsgs
              ? (errMsgs["position.city.id"] || errMsgs["position.city"])?.join(
                  " - "
                )
              : ""
          }
        />
        {(showRegion ||
          (values.position?.city as IPagingCity)?.regions_count > 0) && (
          <Select
            label="منطقه"
            items={dev.regions}
            onChange={(newValue) => {
              setFieldValue("position.region", newValue);
            }}
            valueKey="id"
            displayExpr="name"
            id="position.region"
            className={"col-md-6 col-12"}
            value={getFromOjectByKey(values, "position.region")}
            validationMessage={
              errMsgs
                ? (
                    errMsgs["position.region.id"] || errMsgs["position.region"]
                  )?.join(" - ")
                : ""
            }
            isMulti={regionIsMulty}
            disabled={
              ((values.position?.city as IPagingCity)?.regions_count || 0) < 1
            }
          />
        )}
        {!hideAddress && (
          <TextArea
            label="آدرس"
            value={values.position?.address}
            onChange={(e) => setFieldValue("position.address", e)}
            className={"col-12"}
            validationMessage={
              errMsgs ? errMsgs["position.address"]?.join(" - ") : ""
            }
          />
        )}
        {/* {!hideMap && (
          <div className={"col-12 z-0"}>
            <MapWithNoSSR
              lat={values.position?.location?.lat}
              lng={values.position?.location?.lng}
              onChange={(value) => {
                setFieldValue("position.location", value);
              }}
            />
          </div>
        )} */}
      </div>
    </div>
  );
};

export default AdLocation;
