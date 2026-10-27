import { IFilter } from "../types/mainPage.types";
import { urls } from "../utils";
import http from "./sevices";

export const getRequestFilters = (categorySlug: string) => {
  return http
    .get<{
      searchItems: { items: IFilter[]; icon?: string; label?: string }[];
    }>(
      `${process.env.NEXT_PUBLIC_API_URL}${urls["request-fields"].replace(
        "{category}",
        categorySlug
      )}`
    )
    .then((response) => response);
};

export const postRequest = (categorySlug: string, values: any) => {
  return http
    .post(
      `${process.env.NEXT_PUBLIC_API_URL}${urls.request.replace(
        "{category}",
        categorySlug
      )}`,
      { req: values }
    )
    .then((response) => response);
};
