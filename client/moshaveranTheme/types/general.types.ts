export interface IPagingResponse<T> {
  data: T[];
  meta?: IPagingMeta;
  links?: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  total?: number;
}

export interface IPagingMeta {
  current_page: number;
  from: number;
  last_page: number;
  links: IPagingLink[];
  path: string;
  per_page: number;
  to: number;
}

export interface IPagingLink {
  url: string | null;
  label: string;
  active: boolean;
}

export interface IRule {
  title: string;
  description: string;
}

export interface IContactData {
  name?: string;
  mobile?: string;
  subject?: string;
  content?: string;
}

export interface IServerSideSettings {
  general_site_name?: string;
  general_favicon?: string;
  city?: { id: number; name: string; province_id: number };
  province?: { id: number; name: string };
}
