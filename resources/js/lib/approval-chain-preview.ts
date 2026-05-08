/**
 * Builds a human-readable Spanish summary of an approval chain.
 * Mirrors `App\Support\Approvals\ApprovalChainHumanSummary` server-side.
 */

export type StepDraft = {
    approver_type: string;
    approver_id: string | number;
    step_mode: string;
};

export type ApproverLookup = (
    type: string,
    id: string | number,
) => string | undefined;

export function buildChainPreview(
    steps: StepDraft[],
    lookup: ApproverLookup,
): string {
    if (steps.length === 0) return '—';

    // Group consecutive non-sequential steps together; sequential closes a group.
    const groups: StepDraft[][] = [];
    let current: StepDraft[] = [steps[0]];
    for (let i = 0; i < steps.length - 1; i++) {
        const step = steps[i];
        const next = steps[i + 1];
        if (step.step_mode === 'sequential') {
            groups.push(current);
            current = [next];
        } else {
            current.push(next);
        }
    }
    groups.push(current);

    const parts: string[] = [];
    let first = true;

    groups.forEach((groupSteps) => {
        const labels = groupSteps.map(
            (s) =>
                lookup(s.approver_type, s.approver_id) ??
                fallbackLabel(s.approver_type),
        );

        let segment: string;
        if (groupSteps.length === 1) {
            segment = labels[0];
        } else {
            const isAllOf = groupSteps
                .slice(0, -1)
                .some((s) => s.step_mode === 'all_of');
            segment = isAllOf
                ? `todos: [${labels.join(', ')}]`
                : `cualquiera de [${labels.join(', ')}]`;
        }

        parts.push(
            first
                ? `Primero aprueba ${segment}`
                : `después aprueba ${segment}`,
        );
        first = false;
    });

    return parts.join(', ') + '.';
}

function fallbackLabel(type: string): string {
    switch (type) {
        case 'role':
            return 'rol sin asignar';
        case 'department':
            return 'departamento sin asignar';
        case 'user':
            return 'usuario sin asignar';
        default:
            return '—';
    }
}
