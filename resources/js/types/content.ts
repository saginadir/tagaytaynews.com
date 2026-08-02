export interface MediaItem {
    id: number;
    filename: string;
    disk_path: string;
    mime_type: string;
    size: number;
    alt: string | null;
    credit: string | null;
    url: string;
    is_video: boolean;
    created_at: string;
}

export interface CategoryItem {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    articles_count?: number;
    created_at: string;
}

export interface SourceItem {
    id: number;
    name: string;
    url: string;
    feed_url: string | null;
    tier: number;
    is_active: boolean;
    last_fetched_at: string | null;
    notes: string | null;
    created_at: string;
}

export interface ArticleItem {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    body: string;
    category_id: number;
    source_id: number | null;
    source_url: string | null;
    featured_image_id: number | null;
    author: string;
    status: string;
    published_at: string | null;
    seo_title: string | null;
    seo_description: string | null;
    category?: CategoryItem;
    source?: SourceItem | null;
    featured_image?: MediaItem | null;
    created_at: string;
}

export interface NavCategory {
    name: string;
    slug: string;
}

export interface RidgeReportData {
    temperature: number;
    humidity: number;
    windKph: number;
    visibilityM: number;
    weatherLabel: string;
    fogLevel: 'none' | 'patches' | 'dense';
    sunrise: string;
    sunset: string;
    taalAlert: number;
    updatedAt: string;
}

export interface PollOptionData {
    id: number;
    label: string;
    votes: number;
}

export interface PollData {
    id: number;
    question: string;
    totalVotes: number;
    myOptionId: number | null;
    options: PollOptionData[];
}

export interface SeoData {
    title: string;
    description: string | null;
    canonical: string | null;
    ogImage: string;
    ogType: string;
    jsonLd: string | null;
}
