import { ChevronDown, Clock } from 'lucide-react';
import { useState } from 'react';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';
import { type DocumentTimelineRow, formatDate } from './types';

export default function DocumentTimelineCard({
    timeline,
}: {
    timeline: DocumentTimelineRow[];
}) {
    const [open, setOpen] = useState(false);

    if (timeline.length === 0) return null;

    return (
        <Collapsible open={open} onOpenChange={setOpen}>
            <CollapsibleTrigger className="flex w-full items-center gap-2 text-left">
                <Clock className="size-4 text-muted-foreground" />
                <span className="text-base font-semibold">Bitácora</span>
                <span className="text-xs text-muted-foreground">
                    ({timeline.length} evento{timeline.length !== 1 && 's'})
                </span>
                <ChevronDown
                    className={cn(
                        'ml-auto size-4 text-muted-foreground transition-transform',
                        open && 'rotate-180',
                    )}
                />
            </CollapsibleTrigger>
            <CollapsibleContent className="mt-3">
                <div className="relative ml-3 border-l-2 border-muted pl-6">
                    {timeline.map((row) => (
                        <div
                            key={row.id}
                            className="relative pb-5 last:pb-0"
                        >
                            <div className="absolute -left-[31px] top-1 size-3 rounded-full border-2 border-background bg-primary" />
                            <div className="flex flex-wrap items-baseline justify-between gap-2">
                                <span className="text-sm font-medium">
                                    {row.label}
                                </span>
                                <span className="text-xs tabular-nums text-muted-foreground">
                                    {formatDate(row.occurred_at)}
                                </span>
                            </div>
                            <p className="mt-0.5 text-xs text-muted-foreground">
                                Por: {row.actor_name}
                            </p>
                            {row.note && (
                                <p className="mt-2 whitespace-pre-wrap rounded-md bg-muted/50 px-3 py-2 text-sm">
                                    {row.note}
                                </p>
                            )}
                        </div>
                    ))}
                </div>
            </CollapsibleContent>
        </Collapsible>
    );
}
