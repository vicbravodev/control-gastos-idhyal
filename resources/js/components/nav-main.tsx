import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import type { NavGroup } from '@/types';

const ACTIVE_ITEM_CLASSES =
    'data-[active=true]:bg-[var(--brand-blue-50)] data-[active=true]:text-[var(--brand-blue-700)] data-[active=true]:shadow-[inset_0_0_0_1px_var(--brand-blue-100)] data-[active=true]:[&_svg]:text-[var(--brand-blue-600)] data-[active=true]:hover:bg-[var(--brand-blue-50)] data-[active=true]:hover:text-[var(--brand-blue-700)]';

export function NavMain({ groups = [] }: { groups: NavGroup[] }) {
    const { isCurrentUrl } = useCurrentUrl();

    const visibleGroups = groups.filter((group) => group.items.length > 0);

    return (
        <>
            {visibleGroups.map((group) => (
                <SidebarGroup key={group.label} className="px-2 py-0">
                    <SidebarGroupLabel className="text-[11px] font-semibold tracking-wider text-[var(--subtle-fg)] uppercase">
                        {group.label}
                    </SidebarGroupLabel>
                    <SidebarMenu>
                        {group.items.map((item) => {
                            const isActive = isCurrentUrl(item.href);

                            return (
                                <SidebarMenuItem key={item.title}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={isActive}
                                        tooltip={{ children: item.title }}
                                        className={ACTIVE_ITEM_CLASSES}
                                    >
                                        <Link
                                            href={item.href}
                                            prefetch
                                            className="flex w-full min-w-0 items-center gap-2"
                                        >
                                            {item.icon && <item.icon />}
                                            <span className="truncate">
                                                {item.title}
                                            </span>
                                            {item.badgeCount != null &&
                                            item.badgeCount > 0 ? (
                                                <Badge
                                                    className={cn(
                                                        'ml-auto shrink-0 border-transparent px-1.5 text-xs tabular-nums',
                                                        isActive
                                                            ? 'bg-[var(--brand-blue-600)] text-white'
                                                            : 'bg-[var(--brand-blue-500)] text-white',
                                                    )}
                                                >
                                                    {item.badgeCount > 99
                                                        ? '99+'
                                                        : item.badgeCount}
                                                </Badge>
                                            ) : null}
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            );
                        })}
                    </SidebarMenu>
                </SidebarGroup>
            ))}
        </>
    );
}
