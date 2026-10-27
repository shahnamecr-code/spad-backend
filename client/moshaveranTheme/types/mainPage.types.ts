import { IAd } from "./ads.types";
import { INews } from "./news.types";

export interface IMainPageData {
  fast: IAd[];
  latest: IAd[];
  special: IAd[];
  news: INews[];
  filters_background?: string;
  banner_1_image?: string;
  banner_2_image?: string;
  banner_3_image?: string;
  banner_1_link?: string;
  banner_2_link?: string;
  banner_3_link?: string;
  home_seo_title?: string;
  home_meta_description?: string;
}

export interface IFilterData {
  name: string;
  slug: string;
  icon: string;
  layout: IFilterLayout[];
  customer_children: ICategory[];
}

export interface IFilterLayout {
  title: string;
  filters: IFilter[];
}

export interface IFilter {
  label: string;
  code: string;
  search_type: IPropertyType;
  range_search: boolean;
  property_type: IPropertyType;
  search_operator: TDependency;
  options?: { label: string; value: string }[];
  separator: boolean;
  unit: string;
  auto_complete: boolean;
}

export type IPropertyType =
  | "select"
  | "text"
  | "textarea"
  | "boolean"
  | "switch"
  | "integer"
  | "multiselect"
  | "multi-select"
  | "radio"
  | "checkbox"
  | "transaction"
  | "land_type"
  | "images"
  | "geo";

export type TDependency = "gt" | "lt" | "eq";

export interface ICategory {
  id?: number;
  name?: string;
  parent?: number | null;
  slug?: string;
  icon?: any;
  customer_children?: ICategory[];
}

export interface IOptions {
  general_site_name?: string;
  general_site_description?: string;
  header_phone?: string;
  footer_description?: string;
  copy_right?: string;
  developer_text?: string;
  telegram_account?: string;
  instagram_account?: string;
  youtube_account?: string;
  twitter_account?: string;
  facebook_account?: string;
  direct_app_download?: string;
  ios_app_download?: string;
  android_app_download?: string;
  whatsapp?: string;
  kasbokar?: string;
  enamad?: string;
  samandehi?: string;
  address?: string;
  email?: string;
  search_ad_by_code?: string | null;
  general_logo?: string;
  user_default_profile?: string;
  footer_phone?: string;
  general_favicon?: string;
}
