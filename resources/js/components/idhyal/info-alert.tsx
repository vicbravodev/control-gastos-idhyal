import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

type InfoAlertTone = 'info' | 'success' | 'warning' | 'danger';

type InfoAlertProps = {
    tone?: InfoAlertTone;
    icon?: ReactNode;
    title?: ReactNode;
    children: ReactNode;
    className?: string;
};

const TONE_CLASSES: Record<InfoAlertTone, string> = {
    info: 'bg-[var(--info-bg)] text-[var(--info-fg)]',
    success: 'bg-[var(--success-bg)] text-[var(--success-fg)]',
    warning: 'bg-[var(--warning-bg)] text-[var(--warning-fg)]',
    danger: 'bg-[var(--destructive-bg)] text-[var(--destructive-fg)]',
};

export function InfoAlert({
    tone = 'info',
    icon,
    title,
    children,
    className,
}: InfoAlertProps) {
    return (
        <div
            className={cn(
                'flex gap-2 rounded-md px-3 py-2 text-[13px] leading-snug',
                TONE_CLASSES[tone],
                className,
            )}
            role={tone === 'warning' || tone === 'danger' ? 'alert' : undefined}
        >
            {icon ? (
                <div className="mt-0.5 shrink-0 [&>svg]:size-4">{icon}</div>
            ) : null}
            <div className="min-w-0 flex-1">
                {title ? <div className="font-semibold">{title}</div> : null}
                <div>{children}</div>
            </div>
        </div>
    );
}
