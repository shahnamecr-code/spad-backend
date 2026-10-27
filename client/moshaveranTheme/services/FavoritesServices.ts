import { IAd } from "../types/ads.types";
import { IPagingResponse } from "../types/general.types";
import { urls } from "../utils";
import http from "./sevices";

export const getFavorites = (url?: string) => {
  return http
    .get<IPagingResponse<IAd>>(
      url || `${process.env.NEXT_PUBLIC_API_URL}${urls.favorites}`
    )
    .then((response) => response);
};

export const getAllFavorites = () => {
  return http
    .get<number[]>(`${process.env.NEXT_PUBLIC_API_URL}${urls["all-favorites"]}`)
    .then((response) => response);
};

export const addFavorites = (adIds: number[], token?: string) => {
  return http
    .post(
      `${process.env.NEXT_PUBLIC_API_URL}${urls["add-to-favorites"]}`,
      {
        adds: adIds,
      },
      token ? { headers: { Authorization: `Bearer ${token}` } } : {}
    )
    .then((response) => response);
};

export const removeFavorite = (adId: number) => {
  return http
    .delete(
      `${process.env.NEXT_PUBLIC_API_URL}${urls["remove-from-favorites"]}/${adId}`
    )
    .then((response) => response);
};

export const removeAllFavorite = () => {
  return http
    .delete(`${process.env.NEXT_PUBLIC_API_URL}${urls["remove-all-favorites"]}`)
    .then((response) => response);
};

export const getCustomAds = (url?: string, adIds?: number[]) => {
  return http
    .post<IPagingResponse<IAd>>(
      url || `${process.env.NEXT_PUBLIC_API_URL}${urls["custom-ads"]}`,
      { ids: adIds }
    )
    .then((response) => response);
};
