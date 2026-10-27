import { ImageLoaderProps } from "next/image";

export interface IAd {
  id?: number;
  category?: string;
  title?: string;
  image?: string;
  gallery?: string[];
  city?: string;
  region?: string;
  date?: string;
  main_props?: IAdProps[];
  secondary_props?: IAdProps[];
  status?: 1 | 2 | 3;
  status_text?: string;
  status_class?: "success";
  fast?: boolean;
  special?: boolean;
  netting?: boolean;
}

export interface IAdProps {
  title?: string;
  icon?: string;
  unit?: string;
  value?: string;
}

export type IFilterType = "select" | "year-from" | "number" | "on-off" | "text";

export interface IGlobalFilter {
  id: string;
  label: string;
  type: IFilterType;
  choices?: string[] | null;
  start?: string;
  end?: string;
  std?: "on" | "off";
}

export interface ILandType {
  term_id: number;
  term_name: string;
  related?: { [key: string]: IGlobalFilter };
  icon?: string;
}

export interface ITransaction extends ILandType {
  land_types?: ILandType[];
}

export interface IFilters {
  global_filters: { [key: string]: IGlobalFilter };
  transactions: { [key: string]: ITransaction };
}

export interface ISearchData {
  land_type: string;
  metas: { [key: string]: any };
  transaction: string;
}

// export interface ISingleAd {
//   main: {
//     postId: number;
//     name: string;
//     slug: string;
//     description: string;
//     author: string;
//     image: string;
//     status: string;
//     trash: string;
//     post_type: string;
//     created_at: string;
//     updated_at: string;
//     mainImg: string;
//   };
//   managers_list:{
//       job: string,
//       image: string,
//       phone: string,
//       title: string,
//       inner_code: string
//   }[]

//   experts: {
//     id: number;
//     name: string;
//     email: string;
//     phone: string;
//     profile: string;
//   }[];
//   created_at: string;
//   updated_at: string;
//   prices: {
//     [key: string]: {
//       value: string;
//       id: string;
//       type: TPropertyType;
//       label: string;
//       price: string;
//     };
//   };
//   image_gallery: string[];
//   metas: {
//     [key: string]: {
//       value: boolean | string;
//       id: string;
//       type?: TPropertyType;
//       label: string;
//     };
//   };
// }

type TPropertyType =
  | "transaction-select"
  | "number"
  | "on-off"
  | "select"
  | "year-from";

export interface ICreateAd {
  metas: { [key: string]: IMeta };
  terms: ITerm[];
}

export interface IMeta {
  label: string;
  position?: string;
  sort?: string;
  content: IContent[];
  id?: string;
}

export interface IContent {
  id?: string;
  label?: string;
  type: IContentType;
  choices?: (ITransaction | string)[];
  validate_rule?: {
    [key: string]: "required";
  };
  validate_message?: {
    [key: string]: string;
  };
  class?: string;
  description?: string;
  start?: string;
  end?: string;
  std?: "on" | "off";
}

export type IContentType =
  | IFilterType
  | "transaction-select"
  | "gallery"
  | "geo"
  | "textarea";

interface ITerm {
  label: "امکانات رفاهی";
  choices: { [key: string]: string };
}
interface IRealtor {
  display_name?: string;
  mobile?: string;
}

export interface IAdExpertiseRequest {
  category_id: number;
  province_id: number;
  city_id: number;
  region_id: number;
  address: string;
}

export interface ISingleAd {
  id: number;
  title: string;
  widgets: {
    widget_type: "horizontal" | "vertical" | "default" | "group";
    title?: string;
    front_layout?: "layout1" | "layout2";
    info?: {
      title: string;
      value: string;
    };
    items?: {
      info?: {
        icon?: string;
        title?: string;
        unit?: string;
        value?: string;
      };
    }[];
  }[];
  gallery: string[];
  admins: {
    experts: IRealtor[];
    supervisors: IRealtor[];
    managers: IRealtor[];
  };
  breadcrumb?: {
    id: number;
    name: string;
    slug: string;
  }[];
  category: string;
  location?: string;
  date: string;
  managers_list: {
    job?: string;
    image: {
      id: number;
      url: string;
      name: string;
      size: number;
      type: string;
      directory: {
        id: number;
        name: string;
        path: string;
        parent: number|null;
        admin_id: number;
      };
      created_at: string;
      updated_at: string;
      fileable_id: number;
      directory_id: number;
      fileable_type: string;
    }[];
    phone?: string;
    title?: string;
    inner_code?: string;
  }[];
  ads_report_reasons: { title: string }[];
  fast: boolean;
  special: boolean;
  important_places?: {
    place?: {
      icon?: string;
      label?: string;
    };
    distance?: string;
  }[];
}
