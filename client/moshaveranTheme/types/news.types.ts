export interface INewsCategory {
  name: string;
  slug: string;
}
export interface INews {
  author: string;
  categories: INewsCategory[]
  created_at: string;
  image: string;
  meta_description: string;
  meta_title: string;
  short_description: string;
  slug: string;
  title: string;
  description: string;
}


