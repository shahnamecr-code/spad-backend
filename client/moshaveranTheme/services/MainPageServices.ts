import { IFilterData, IMainPageData, IOptions } from "../types/mainPage.types";
import { urls } from "../utils";
import http from "./sevices";

export const getMainPageData = () => {
  return http
    .get(`${process.env.NEXT_PUBLIC_API_URL}${urls.homeLayout}`)
    .then((response) => response);
};

export const getLocationData = () => {
  return http
    .get<IMainPageData>(`${process.env.NEXT_PUBLIC_API_URL}${urls.provinces}`)
    .then((response) => response);
};

export const getMainPageFilters = () => {
  return http
    .get<IFilterData[]>(`${process.env.NEXT_PUBLIC_API_URL}${urls.homeFilters}`)
    .then((response) => response);
};

export const getOptions = () => {
  return http
    .get<IOptions>(`${process.env.NEXT_PUBLIC_API_URL}${urls.options}`)
    .then((response) => response);
};

export const checkAdExist = (id: string) => {
  return http
    .get<{ status: boolean }>(
      `${process.env.NEXT_PUBLIC_API_URL}${urls["check-ad-exist"]}/${id}`
    )
    .then((response) => response);
};
