export type ArticleStatus = 'draft' | 'scheduled' | 'published';

export type StatusOption = { value: ArticleStatus; label: string };

export type CategoryOption = { id: number; name: string; slug?: string };

export type AuthorOption = { id: number; name: string };

export type ArticleRow = {
    id: number;
    title: string;
    slug: string;
    status: ArticleStatus;
    read_time_minutes: number;
    published_at: string | null;
    updated_at: string;
    author: { id: number; name: string } | null;
    category: { id: number; name: string } | null;
    public_url: string | null;
};

export type ArticleDetail = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    meta_title: string | null;
    meta_description: string | null;
    body: string | null;
    featured_image: string | null;
    featured_image_alt: string | null;
    featured_image_caption: string | null;
    category_id: number | null;
    author_id: number;
    tags: string[];
    status: ArticleStatus;
    publish_at: string | null;
    published_at: string | null;
    updated_at: string;
    is_published: boolean;
    public_url: string | null;
    preview_url: string;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
};
