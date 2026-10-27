import { IRecruitmentData } from "../pages/recruitment";
import {
  IContactData,
  IRule,
  IServerSideSettings,
} from "../types/general.types";
import { urls } from "../utils";
import http from "./sevices";

export const getAboutUs = () => {
  return http
    .get(`${process.env.NEXT_PUBLIC_API_URL}${urls.about}`)
    .then((response) => response)
    .catch((err) => err.response.data);
};

export const postContactUs = (data: IContactData) => {
  return http
    .post(`${process.env.NEXT_PUBLIC_API_URL}${urls.contact}`, {
      ...data,
    })
    .then((response) => response);
};
export const getContactUsOtption = () => {
  return http
    .get(`${process.env.NEXT_PUBLIC_API_URL}${urls["contact-options"]}`)
    .then((response) => response);
};

export const getRules = () => {
  return http
    .get<{ rules: IRule[] }>(`${process.env.NEXT_PUBLIC_API_URL}${urls.rules}`)
    .then((response) => response);
};

export const recruitmentPost = (data: IRecruitmentData) => {
  return http
    .post<any>(`${process.env.NEXT_PUBLIC_API_URL}${urls.recruitment}`, data)
    .then((response) => response);
};

export const getServerSiteSettings = () => {
  return http
    .get<IServerSideSettings>(
      `${process.env.NEXT_PUBLIC_API_URL}${urls.serverSiteSettings}`
    )
    .then((response) => response);
};
