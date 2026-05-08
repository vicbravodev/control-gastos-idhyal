export type ApprovalApproverType = 'role' | 'department' | 'user';

export type ApprovalApprover = {
    type: ApprovalApproverType;
    type_label: string;
    id: number;
    name: string;
};

export type ApprovalRow = {
    id: number;
    step_order: number;
    status: string;
    approver: ApprovalApprover;
    note: string | null;
    acted_at: string | null;
};

export type ApprovalProgress = {
    total_groups: number;
    remaining_groups: number;
    completed_groups: number;
};
