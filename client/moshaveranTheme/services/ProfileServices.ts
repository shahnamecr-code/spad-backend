import { IAd } from "../types/ads.types";
import { IUpdatePass, IUpdateProfile, IUserInformation } from "../types/auth.types";
import { IPagingResponse } from "../types/general.types";
import {
  IExpertiseRequest,
  IMyRequest,
  IVisitingRequest,
} from "../types/profile.types";
import { urls } from "../utils";
import http from "./sevices";

export const updateProfile = (data: IUpdateProfile) => {
  return http
    .post(`${process.env.NEXT_PUBLIC_API_URL}${urls["update-profile"]}`, data)
    .then((res) => res);
};

export const updatePass = (data: IUpdatePass) => {
  return http
    .post(`${process.env.NEXT_PUBLIC_API_URL}${urls["update-pass"]}`, data)
    .then((res) => res);
};

export const getMyAds = (url?: string) => {
  return http
    .get<IPagingResponse<IAd>>(
      url || `${process.env.NEXT_PUBLIC_API_URL}${urls["my-ads"]}`
    )
    .then((response) => response);
};

export const getMyVisitingRequests = (url?: string) => {
  return http
    .get<IPagingResponse<IVisitingRequest>>(
      url || `${process.env.NEXT_PUBLIC_API_URL}${urls["visiting-requests"]}`
    )
    .then((response) => response);
};

export const getMyExpertiseRequests = (url?: string) => {
  return http
    .get<IPagingResponse<IExpertiseRequest>>(
      url ||
        `${process.env.NEXT_PUBLIC_API_URL}${urls["my-expretise-requests"]}`
    )
    .then((response) => response);
};

export const getMyRequests = (url?: string) => {
  return http
    .get<IPagingResponse<IMyRequest>>(
      url || `${process.env.NEXT_PUBLIC_API_URL}${urls["my-requests"]}`
    )
    .then((response) => response);
};

export const getUserInfo = () => {
  return http
    .get<IUserInformation["user"]>(
      `${process.env.NEXT_PUBLIC_API_URL}${urls.user}`
    )
    .then((response) => response);
};
