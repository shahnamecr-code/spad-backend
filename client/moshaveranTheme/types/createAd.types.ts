export interface ICategory {
  id?: number;
  name?: string;
  parent?: number | null;
  slug?: string;
  icon?: any;
  customer_children?: ICategory[];
}

export interface ICategoryData {
  data: ICategory[];
  levels?: { label: string }[];
}

export interface IFieldData {
  layout: IFildsSection[];
  properties: IProperty;
  important_places?: {
    icon?: string;
    label?: string;
  }[];
}

export interface IFildsSection {
  id?: number;
  category_id?: number;
  title: string;
  layout: ILayout[];
}

export interface ILayout {
  class?: string;
  property_id: string;
  dependency?: IDependency[];
}

export interface IDependency {
  items: ILayout[];
  dependency_type: TDependency;
  dependency_limit: string;
}

export type TDependency = "gt" | "lt" | "eq";

export interface IProperty {
  [key: string]: IField;
}

export interface IField {
  id?: number;
  title?: string;
  slug?: string;
  type?: IPropertyType;
  options?: { label?: string; value?: string; icon?: string }[];
  icon?: any;
  unit?: string;
  unit_name?: string;
  separator?: boolean;
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
  | "geo"
  | "important_places";

export interface IPagingCity extends ILocation {
  regions_count: number;
}

export interface ILocation {
  id?: number;
  name?: string;
  lat?: number;
  lng?: number;
}
