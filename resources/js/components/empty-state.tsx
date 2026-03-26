import type { LucideIcon } from 'lucide-react';
import { Inbox } from 'lucide-react';
import type { ReactNode } from 'react';

export function EmptyState({
    icon: Icon = Inbox,
    title,
    description,
    action,
}: {
    icon?: LucideIcon;
    title: string;
    description?: string;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center justify-center py-12 text-center animate-fade-in">
            <div className="mb-4 rounded-full bg-muted p-4">
                <Icon className="size-8 text-muted-foreground" strokeWidth={1.5} />
            </div>
            <h3 className="text-sm font-medium text-foreground">{title}</h3>
            {description && (
                <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                    {description}
                </p>
            )}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
