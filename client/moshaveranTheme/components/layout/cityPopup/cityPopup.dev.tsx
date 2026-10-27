import { useState, useEffect } from "react";
import {
  getProvinces,
  getCitiesOfProvince,
} from "../../../services/PositionServices";
import { ILocation } from "../../../types/createAd.types";
import {
  deepCopy,
  getStore,
  paths,
  removeStore,
  setStore,
} from "../../../utils";
import { useAppContext } from "../../../utils/context";
import { LOCATION, REGION } from "../../../utils/stores";
import { IPopupProps } from "../../general/popup/Popup";

export interface ISearchLocation extends ILocation {
  show: boolean;
  selectAll?: boolean;
}

export interface ILocationStore {
  province?: ILocation;
  cities?: ILocation[];
}

export const useCityPopupDev = (props: IPopupProps) => {
  const context = useAppContext();
  const [provinces, setProvinces] = useState<ISearchLocation[]>(
    context.provinces || []
  );
  const [cities, setCities] = useState<ISearchLocation[]>([]);
  const [position, setPosition] = useState<{
    province?: ILocation;
    cities?: ILocation[];
  }>({});
  const [q, setQ] = useState("");
  const [loading, setLoading] = useState(false);
  const [selectAll, setSelectAll] = useState(false);
  const [first, setFirst] = useState(true);

  useEffect(() => {
    setPosition(JSON.parse(getStore(LOCATION) || "{}"));
    if (context.provinces.length === 0) {
      setLoading(true);
      getProvinces()
        .then((res) => {
          context.setProvinces(
            res.data.map((x) => {
              return { ...x, show: true };
            })
          );
        })
        .finally(() => setLoading(false));
    }
  }, [getStore(LOCATION)]);

  useEffect(() => {
    setProvinces(context.provinces);
  }, [context.provinces]);

  useEffect(() => {
    if (position?.province?.id && props.visible) {
      setLoading(true);
      getCitiesOfProvince(position.province.id || 0)
        .then((res) => {
          const data: ISearchLocation[] = [
            {
              id: 0,
              show: true,
              name: `همه ی شهر های ${position.province?.name}`,
              selectAll: true,
            },
            ...res.data.map((x) => {
              return { ...x, show: true };
            }),
          ];
          setCities(data);
          const location = JSON.parse(
            getStore(LOCATION) || "{}"
          ) as ILocationStore;
          if (
            first &&
            location.province &&
            location.province.id === position.province?.id &&
            !location.cities
          ) {
            setSelectAll(true);
            setPosition({ ...position, cities: data });
            setFirst(false);
          }
        })
        .finally(() => setLoading(false));
    }
  }, [position?.province?.id, props.visible]);

  useEffect(() => {
    if (q.length >= 1) {
      if (position.province) searchCities();
      else searchProvince();
    } else if (q.length === 0) {
      if (position.province) setInitialCities();
      else setInitialProvinces();
    }
  }, [q]);

  const setInitialProvinces = () => {
    setProvinces(
      provinces.map((x) => {
        return { ...x, show: true };
      })
    );
  };

  const setInitialCities = () => {
    setCities(
      cities.map((x) => {
        return { ...x, show: true };
      })
    );
  };

  const searchProvince = () => {
    let arr = deepCopy(provinces);
    arr.forEach((x) => {
      if (!x.name?.includes(q)) x.show = false;
    });
    setProvinces(arr);
  };

  const searchCities = () => {
    let arr = deepCopy(cities);
    arr.forEach((x) => {
      if (!x.name?.includes(q)) x.show = false;
    });
    setCities(arr);
  };

  const cityOnClick = (item: ILocation) => {
    let pos = deepCopy(position);
    let cityIndex = pos.cities?.findIndex((x) => x.id === item.id);
    if (cityIndex === -1) {
      pos.cities?.push(item);
      if (pos.cities?.length === cities.length) setSelectAll(true);
    } else if (cityIndex !== undefined) {
      pos.cities?.splice(cityIndex, 1);
      if (selectAll) setSelectAll(!selectAll);
    }
    setPosition(pos);
  };

  const submitPopup = () => {
    const location = {
      province: position.province || {},
      cities: selectAll ? undefined : position.cities?.filter((x) => x),
    };
    if (window.location.pathname !== paths.search) setStore(LOCATION, location);
    removeStore(REGION);
    context.setRegions([]);
    context.setLocation(location);
  };

  const onCancell = () => {
    setPosition(JSON.parse(getStore(LOCATION) || "{}"));
  };

  return {
    cities,
    provinces,
    setPosition,
    q,
    setQ,
    searchProvince,
    searchCities,
    position,
    setCities,
    cityOnClick,
    loading,
    selectAll,
    setSelectAll,
    setInitialCities,
    setInitialProvinces,
    submitPopup,
    onCancell,
  };
};
