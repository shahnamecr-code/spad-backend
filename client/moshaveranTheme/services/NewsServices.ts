import { urls } from "../utils";
import http from "./sevices";

export const getNews = () => {
  return http
    .get(`${process.env.NEXT_PUBLIC_API_URL}${urls.news}`)
    .then((response) => response.data)
    .catch(err => err.response.data);
};

export const getNewsBySlug = (slug: string) => {
  return http
    .get(`${process.env.NEXT_PUBLIC_API_URL}${urls.newsSlug}/${encodeURIComponent(slug)}`)
    .then((response) => response.data)
    .catch(err => err.response);
};

export const getNewsByCategory = (slug: string) => {
  return http
    .get(`${process.env.NEXT_PUBLIC_API_URL}${urls.newsCategory}/${encodeURIComponent(slug)}/posts`)
    .then((response) => response.data)
    .catch(err => err.response.data);
};