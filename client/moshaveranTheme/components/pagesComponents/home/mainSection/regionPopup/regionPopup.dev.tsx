import { useState, useEffect } from "react";
import { getRegionsOfCityWithPage } from "../../../../../services/PositionServices";
import { ILocation } from "../../../../../types/createAd.types";
import { deepCopy, getStore, setStore } from "../../../../../utils";
import { LOCATION, REGION } from "../../../../../utils/stores";
import { ILocationStore } from "../../../../layout/cityPopup/cityPopup.dev";

export interface ISearchLocation extends ILocation {
  show: boolean;
  selectAll?: boolean;
}

export interface IRegionStore {
  selectAll?: boolean;
  regions?: (number | undefined)[];
}

export const useRegionPopupDev = () => {
  const [regions, setRegions] = useState<ISearchLocation[]>([]);
  const [region, setRegion] = useState<{
    selectAll?: boolean;
    regions?: ISearchLocation[];
    next_page_url?: string;
  }>({ regions: [] });
  const [q, setQ] = useState<string>();
  const [loading, setLoading] = useState(false);

  const fetchData = (paging?: boolean, q?: string) => {
    setLoading(true);
    const location = JSON.parse(getStore(LOCATION) || "{}") as ILocationStore;

    getRegionsOfCityWithPage({
      citiesId: location.cities?.map((x) => x.id || 0) || [],
      next_page_url: q ? "" : region.next_page_url,
      q,
    })
      .then((res) => {
        const data = [
          {
            id: 0,
            show: true,
            name: "همه ی مناطق",
            selectAll: true,
          },
          ...(q ? [] : regions),
          ...res.data.data.map((x) => {
            return { ...x, show: true };
          }),
        ];
        if (paging) data.splice(0, 1);

        const region = JSON.parse(getStore(REGION) || "{}") as IRegionStore;
        if (region.selectAll && !region.regions) {
          setRegion({
            ...region,
            selectAll: true,
            regions: data,
            next_page_url: res.data.next_page_url,
          });
        } else {
          setRegion({
            ...region,
            selectAll: false,
            regions: region.regions?.map(
              (x) => data.find((item) => item.id == x) || { show: true }
            ),
            next_page_url: res.data.next_page_url,
          });
        }
        setRegions(data);
      })
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    if ((q?.length || 0) >= 3) {
      searchRegions();
    } else if (q !== undefined && q?.length === 0) {
      fetchData(false, "");
    }
  }, [q]);

  const setInitialCities = () => {
    setRegions(
      regions.map((x) => {
        return { ...x, show: true };
      })
    );
  };

  const searchRegions = () => {
    // let arr = deepCopy(regions);
    // arr.forEach((x) => {
    //   if (!x.Title?.includes(q)) x.show = false;
    // });
    // setRegions(arr);
    fetchData(false, q);
  };

  const regionOnClick = (item: ISearchLocation) => {
    let pos = deepCopy(region);
    let regionIndex = pos.regions?.findIndex((x) => x.id === item.id);
    if (regionIndex === -1) {
      pos.regions?.push(item);
      if (pos.regions?.length === regions.length) pos.selectAll = true;
    } else if (regionIndex !== undefined) {
      pos.regions?.splice(regionIndex, 1);
      if (pos.selectAll) pos.selectAll = !pos.selectAll;
    }
    setRegion(pos);
  };

  const submitPopup = () => {
    setStore<IRegionStore>(REGION, {
      selectAll: region.selectAll,
      regions: region.selectAll
        ? undefined
        : region.regions?.filter((x) => x).map((x) => x.id),
    });
  };

  return {
    regions,
    setRegion,
    q,
    setQ,
    searchRegions,
    region,
    setRegions,
    regionOnClick,
    loading,
    setInitialCities,
    submitPopup,
    fetchData,
  };
};
