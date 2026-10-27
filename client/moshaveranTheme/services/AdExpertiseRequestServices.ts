import { IAdExpertiseRequest } from "../types/ads.types";
import { urls } from "../utils";
import http from "./sevices";

export const postAdExpertiseRequest = (data: IAdExpertiseRequest) => {
    return http
        .post(
            `${process.env.NEXT_PUBLIC_API_URL}${urls["expert-request"]}`,
            data)
        .then((response) => response);
}

export const getAdExpertiseRequestSetting = () => {
    return http
        .get(
            `${process.env.NEXT_PUBLIC_API_URL}${urls["expert-request-setting"]}`,
        )
        .then((response) => response);
}