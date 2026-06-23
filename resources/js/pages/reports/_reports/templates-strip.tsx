import {
    Bookmark,
    BookmarkPlus,
    CalendarCheck,
    FileCheck2,
    LayoutDashboard,
    Map,
    Plus,
    Presentation,
    ReceiptText,
    Scale,
    Trash2,
    XCircle,
    type LucideIcon,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

import type { Template } from './types';

const ICON_MAP: Record<string, LucideIcon> = {
    bookmark: Bookmark,
    'calendar-check': CalendarCheck,
    'receipt-text': ReceiptText,
    'file-check-2': FileCheck2,
    map: Map,
    'x-circle': XCircle,
    scale: Scale,
    presentation: Presentation,
};

type Props = {
    templates: Template[];
    activeId: number | null;
    onSelect: (templateId: number) => void;
    onClear: () => void;
    onCreate: () => void;
    onDelete: (templateId: number) => void;
};

export function TemplatesStrip({
    templates,
    activeId,
    onSelect,
    onClear,
    onCreate,
    onDelete,
}: Props) {
    return (
        <div className="rounded-xl border border-border bg-card p-3">
            <div className="flex items-center gap-3">
                <div className="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                    <Bookmark className="size-4 text-[var(--brand-blue-600)]" aria-hidden />
                    Plantillas
                </div>
                <div className="flex min-w-0 flex-1 flex-wrap items-center gap-2 overflow-x-auto">
                    {templates.map((tpl) => {
                        const Icon = ICON_MAP[tpl.icon] ?? LayoutDashboard;
                        const isActive = tpl.id === activeId;
                        return (
                            <button
                                key={tpl.id}
                                type="button"
                                title={tpl.description ?? tpl.name}
                                onClick={() =>
                                    isActive ? onClear() : onSelect(tpl.id)
                                }
                                className={cn(
                                    'group inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-medium transition-colors',
                                    isActive
                                        ? 'border-[var(--brand-blue-300)] bg-[var(--brand-blue-50)] text-[var(--brand-blue-800)]'
                                        : 'border-border bg-background text-muted-foreground hover:border-[var(--brand-blue-300)] hover:text-foreground',
                                )}
                            >
                                <Icon className="size-3.5" aria-hidden />
                                <span>{tpl.name}</span>
                                {tpl.is_owner && !tpl.is_built_in && (
                                    <span
                                        role="button"
                                        tabIndex={0}
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            onDelete(tpl.id);
                                        }}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter' || e.key === ' ') {
                                                e.stopPropagation();
                                                onDelete(tpl.id);
                                            }
                                        }}
                                        className="ml-0.5 inline-flex size-4 items-center justify-center rounded-full text-muted-foreground/60 opacity-0 transition-opacity hover:bg-destructive/10 hover:text-destructive group-hover:opacity-100"
                                        aria-label={`Eliminar plantilla ${tpl.name}`}
                                    >
                                        <Trash2 className="size-3" />
                                    </span>
                                )}
                            </button>
                        );
                    })}
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={onCreate}
                        className="h-7 gap-1.5 rounded-full text-xs"
                    >
                        <Plus className="size-3.5" />
                        Guardar vista
                    </Button>
                </div>
                <Button
                    variant="ghost"
                    size="sm"
                    onClick={onCreate}
                    className="hidden sm:inline-flex"
                >
                    <BookmarkPlus />
                    Nueva
                </Button>
            </div>
        </div>
    );
}
