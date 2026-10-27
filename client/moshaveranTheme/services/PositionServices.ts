import { ILocation, IPagingCity } from "../types/createAd.types";
import { urls } from "../utils";
import http from "./sevices";

export const getProvinces = () => {
  return http
    .get<ILocation[]>(`${process.env.NEXT_PUBLIC_API_URL}${urls.provinces}`)
    .then((response) => response);
};

export const getCitiesOfProvince = (provinceId: number) => {
  return http
    .get<ILocation[]>(
      `${process.env.NEXT_PUBLIC_API_URL}${urls.cities.replace(
        "{province}",
        `${provinceId}`
      )}`
    )
    .then((response) => response);
};

export interface IPagingRegion {
  data: ILocation[];
  next_page_url: string;
}

export const getRegionsOfCityWithPage = ({
  next_page_url,
  q,
  page,
  provinceId,
  citiesId,
}: {
  provinceId?: number;
  citiesId?: number[] | number;
  next_page_url?: string;
  q?: string;
  page?: string;
}) => {
  return http
    .post<IPagingRegion>(
      next_page_url ||
        `${process.env.NEXT_PUBLIC_API_URL}${urls.SpecificCitiesRegions}`,
      {
        locations: {
          cities: citiesId,
          province: provinceId,
        },
      },
      { params: { search: q, page: page } }
    )
    .then((response) => response);
};

export const getCitiesWithPage = (page: number, search: string) => {
  return http
    .get<{
      hasMore: boolean;
      page: number;
      total: number;
      data: IPagingCity[];
    }>(`${process.env.NEXT_PUBLIC_API_URL}${urls.paginateCities}`, {
      params: { page: page, search: search },
    })
    .then((response) => response);
};

export interface IPagingRegion {
  data: ILocation[];
  next_page_url: string;
}

export const getRegionsOfCity = (cityId: number) => {
  return http
    .get<ILocation[]>(
      `${process.env.NEXT_PUBLIC_API_URL}${urls.regions.replace(
        "{cityId}",
        cityId.toString()
      )}`
    )
    .then((response) => response);
};
