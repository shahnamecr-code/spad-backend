import { ISingleAd } from "../types/ads.types";
import { IAd, ICreateAd } from "../types/ads.types";
import { ICategoryData, IFieldData, ILocation } from "../types/createAd.types";
import { IPagingResponse } from "../types/general.types";
import { ICategory } from "../types/mainPage.types";
import { urls } from "../utils";
import http, { token } from "./sevices";

export const uploadFile = (
  file: File,
  onUploadProgress?: (progressEvent: any) => void
) => {
  let formData = new FormData();
  formData.append("file", file);
  return http.post<{
    file: { id: string; url: string };
    status: boolean;
  }>(`${process.env.NEXT_PUBLIC_API_URL}${urls.upload}`, formData, {
    headers: {
      "Content-Type": "multipart/form-data",
    },
    onUploadProgress,
  });
};

export const getAdById = (id: string, url?: string) => {
  return http
    .get<ISingleAd>(
      `${process.env.NEXT_PUBLIC_API_URL}${url || urls["singleAd"]}/${id}`
    )
    .then((response) => response);
};

export const createAd = (data: any) => {
  return http
    .post<any>(`${process.env.NEXT_PUBLIC_API_URL}${urls["create-ad"]}`, data)
    .then((response) => response);
};

export const getCreateAdFilters = () => {
  return http
    .get<ICreateAd>(
      `${process.env.NEXT_PUBLIC_API_URL}${urls["create-ad-filters"]}`
    )
    .then((response) => response);
};

export interface IMeeting {
  data: { description: string; times: number[] };
}
export const postMeeting = (id: string, data: IMeeting) => {
  return http
    .post<IMeeting>(
      `${process.env.NEXT_PUBLIC_API_URL}${urls["advMeeting"].replace(
        "{id}",
        id
      )}`,
      data
    )
    .then((response) => response);
};

export interface IAdReport {
  reason: string;
  description: string;
}

export const postAdReport = (id: string, data: IAdReport) => {
  return http
    .post(`${process.env.NEXT_PUBLIC_API_URL}${urls["advReport"]}/${id}`, data)
    .then((response) => response);
};

export const getCategories = () => {
  return http
    .get<ICategory[]>(`${process.env.NEXT_PUBLIC_API_URL}${urls.categories}`)
    .then((response) => response);
};

export const getCategoryLayout = (categorId: number) => {
  return http
    .get<IFieldData>(
      `${process.env.NEXT_PUBLIC_API_URL}${urls.categoryLayout}/${categorId}`
    )
    .then((response) => response);
};

export const getAdList = (url?: string, filter?: any) => {
  return http
    .get<IPagingResponse<IAd>>(
      url || `${process.env.NEXT_PUBLIC_API_URL}${urls["search-ads"]}`,
      { params: url ? {} : filter }
    )
    .then((response) => response);
};

export const getCategoriesV3 = () => {
  return http
    .get<ICategoryData>(
      `${process.env.NEXT_PUBLIC_API_URL}${urls.categoriesForSearch}`
    )
    .then((response) => response);
};
