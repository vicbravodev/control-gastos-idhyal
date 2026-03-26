export type ApprovalRow = {
    id: number;
    step_order: number;
    status: string;
    role: { id: number; name: string; slug: string };
    note: string | null;
    acted_at: string | null;
};

export type ApprovalProgress = {
    total_groups: number;
    remaining_groups: number;
    completed_groups: number;
};
