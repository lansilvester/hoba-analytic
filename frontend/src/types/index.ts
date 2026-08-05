export interface User {
  id: number;
  name: string;
  email: string;
  role: string | null;
  role_id: number | null;
  tenant_id: number | null;
  tenant?: { id: number; name: string } | null;
  is_active?: boolean;
  created_at?: string;
}

export interface Role {
  id: number;
  name: string;
}

export interface Tenant {
  id: number;
  name: string;
}

export interface LoginResponse {
  token: string;
  user: User;
}

export interface Source {
  id: number;
  name: string;
  base_url?: string;
  type: string;
}

export interface Keyword {
  id: number;
  keyword: string;
  is_active: boolean;
  created_at?: string;
}

export interface Project {
  id: number;
  name: string;
  description: string | null;
  keywords: string[];
  sources: Source[];
  article_count: number;
  created_at: string;
}

export interface Article {
  id: number;
  title: string;
  source: Source | null;
  sentiment: string | null;
  confidence: number | null;
  published_at: string | null;
  url: string;
  project?: { id: number; name: string } | null;
}

export interface ArticleDetail extends Omit<Article, "sentiment"> {
  content: string | null;
  sentiment: { label: string; confidence: number } | null;
  topic: string | null;
  entities: Array<{ type: string; text: string }>;
  created_at: string;
}

export interface TrendData {
  labels: string[];
  series: Array<{ name: string; data: number[] }>;
}

export interface Paginated<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
  };
}

export interface Report {
  id: number;
  title: string;
  project: { id: number; name: string } | null;
  status: string;
  file_path: string | null;
  download_url: string | null;
  created_at: string;
}
