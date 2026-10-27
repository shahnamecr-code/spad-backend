import { useState, useEffect } from "react";
import {
  getRegionsOfCity,
} from "../../../../services/PositionServices";
import { ILocation, IPagingCity } from "../../../../types/createAd.types";

export const useLocationDev = (values: any) => {
  const [regions, setRegions] = useState<ILocation[]>([]);

  useEffect(() => {
    if ((values.position?.city as IPagingCity)?.regions_count > 0)
      getRegionsOfCity(values.position.city.id).then((res) => {
        setRegions(res.data);
      });
    else setRegions([]);
  }, [values.position?.city]);

  return { regions, setRegions };
};
