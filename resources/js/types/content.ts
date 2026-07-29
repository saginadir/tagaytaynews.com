export interface MediaItem {
    id: number;
    filename: string;
    disk_path: string;
    mime_type: string;
    size: number;
    alt: string | null;
    url: string;
    is_video: boolean;
    created_at: string;
}
