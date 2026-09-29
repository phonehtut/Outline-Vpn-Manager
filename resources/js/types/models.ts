export type Server = {
    id: number;
    name: string;
    api_url: string;
    cert_sha256: string | null;
    access_keys_count?: number;
    sellers?: Seller[];
    created_at: string;
    updated_at: string;
};

export type Seller = {
    id: number;
    name: string;
    email: string;
    servers?: Server[];
    created_at: string;
    updated_at: string;
};

export type AccessKey = {
    id: number;
    server_id: number;
    created_by: number;
    outline_key_id: string;
    name: string;
    access_url: string | null;
    data_limit_bytes: number | null;
    usage_bytes: number | null;
    last_active_at: string | null;
    peak_device_count: number | null;
    peak_device_at: string | null;
    detailed_metrics_supported: boolean | null;
    expires_at: string | null;
    created_at: string;
    updated_at: string;
    creator?: { id: number; name: string; email?: string };
    server?: { id: number; name: string };
};

export type KeyStats = {
    total: number;
    active: number;
    expired: number;
};

export type AdminStats = {
    seller_count: number;
    server_count: number;
    key_count: number;
    expired_key_count: number;
};
