import { IAd } from "./ads.types";

export interface IVisitingRequest {
  id: number;
  times: [
    {
      id: number;
      status: boolean;
      timestamp: number;
      time_text: string;
    }
  ];
  advertisement: IAd;
  status: IVisitingRequestStatus;
  status_text: string;
}

export enum IVisitingRequestStatus {
  init,
  done,
  adminCanceled,
  adminChanded,
  userCanceled,
  userChanded,
  expired,
}

export interface IExpertiseRequest {
  id: number;
  status: IExpertiseRequestStatus;
  status_text: string;
  address: string;
  transaction: string;
  applicationType: string;
  landType: string;
  province: string;
  city: string;
  region: string;
}

export enum IExpertiseRequestStatus {
  wainting,
  approved,
  rejected,
}

export interface IMyRequest {
  admin: boolean;
  category: string;
  city: string;
  title: string;
  id: number;
  regions: string[];
  date: string;
  properties: { icon?: string; label?: string; items?: IMyRequestProperty[] }[];
}

export interface IMyRequestProperty {
  label: string;
  value: string | string[] | { from: string; to: string };
  range_search: boolean;
  search_type: "multi-select" | "boolean" | "switch";
}
