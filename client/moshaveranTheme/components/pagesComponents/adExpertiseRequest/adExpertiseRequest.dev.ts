import {
  getRegionsOfCity,
  getCitiesOfProvince,
} from "./../../../services/PositionServices";
import { useState, useEffect } from "react";
import { getCategoriesV3 } from "../../../services/AdsServices";
import {
  getAdExpertiseRequestSetting,
  postAdExpertiseRequest,
} from "../../../services/AdExpertiseRequestServices";
import { IAdExpertiseRequestSetting } from "../../../types/AdExpertiseRequest.types";
import { IAdExpertiseRequest } from "../../../types/ads.types";
import { toast } from "react-toastify";
import Router from "next/router";
import { ICategoryData, ILocation } from "../../../types/createAd.types";

export const useAdExpertiseRequestDev = () => {
  const [category, setCategory] = useState<ICategoryData>();
  const [cities, setCities] = useState<ILocation[]>();
  const [region, setRegion] = useState<ILocation[]>();
  const [adExpertiseRequestSettings, setAdExpertiseRequestSettings] =
    useState<IAdExpertiseRequestSetting>();

  useEffect(() => {
    getCategoriesV3().then((res) => {
      setCategory(res.data);
    });

    getAdExpertiseRequestSetting().then(
      ({ data }: { data: IAdExpertiseRequestSetting }) => {
        setAdExpertiseRequestSettings(data);
      }
    );
  }, []);

  const handleChangeProvince = (provinceId: number) => {
    if (provinceId) {
      getCitiesOfProvince(provinceId).then((res) => {
        setCities(res.data);
      });
    }
  };
  const handleChangeCity = (cityId: number) => {
    if (cityId) {
      getRegionsOfCity(cityId).then((res) => {
        setRegion(res.data);
      });
    }
  };

  const handleSubmit = (values: IAdExpertiseRequest) => {
    postAdExpertiseRequest(values).then((res) => {
      if (res.data.status) {
        toast.success(res.data.message);
        Router.push("/");
      }
    });
  };

  return {
    category,
    handleChangeProvince,
    cities,
    handleChangeCity,
    region,
    handleSubmit,
    adExpertiseRequestSettings,
  };
};
