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
import type { NavGroup } from '@/types';

export function NavMain({ groups = [] }: { groups: NavGroup[] }) {
    const { isCurrentUrl } = useCurrentUrl();

    const visibleGroups = groups.filter((group) => group.items.length > 0);

    return (
        <>
            {visibleGroups.map((group) => (
                <SidebarGroup key={group.label} className="px-2 py-0">
                    <SidebarGroupLabel>{group.label}</SidebarGroupLabel>
                    <SidebarMenu>
                        {group.items.map((item) => (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={isCurrentUrl(item.href)}
                                    tooltip={{ children: item.title }}
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
                                                variant="secondary"
                                                className="ml-auto shrink-0 px-1.5 text-xs tabular-nums"
                                            >
                                                {item.badgeCount > 99
                                                    ? '99+'
                                                    : item.badgeCount}
                                            </Badge>
                                        ) : null}
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        ))}
                    </SidebarMenu>
                </SidebarGroup>
            ))}
        </>
    );
}
