import { IFilter } from "../types/mainPage.types";
import { urls } from "../utils";
import http from "./sevices";

export const getSearchFilters = (categorySlug: string) => {
  return http
    .get<{
      searchItems: { items: IFilter[]; icon?: string; label?: string }[];
    }>(`${process.env.NEXT_PUBLIC_API_URL}${urls.searchFilters}`, {
      params: { category: categorySlug },
    })
    .then((response) => response);
};
