export type AuthRole = {
    id: number;
    slug: string;
    name: string;
};

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    role?: AuthRole | null;
    has_expense_request_oversight?: boolean;
    can_manage_budgets?: boolean;
    can_manage_approval_policies?: boolean;
    can_manage_vacation_rules?: boolean;
    can_view_reports?: boolean;
    unread_notifications_count?: number;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
