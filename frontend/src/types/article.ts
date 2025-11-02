export interface CategoryTranslation {
  name: string;
  text?: string;
}

export interface Category {
  id: number;
  name: string;
  translations?: Record<string, CategoryTranslation>;
  // Optional styling fields coming from backend (various possible keys)
  color?: string;
  bgColor?: string;
  backgroundColor?: string;
  textColor?: string;
}

export interface Translation {
  locale: string;
  title: string;
  content?: string;
  short?: string;
}

export interface Article {
  id: number;
  categories: Category[];
  translations: Translation[];
  img: string | null;
  date: Date;
}