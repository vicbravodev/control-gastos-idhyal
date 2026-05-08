import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white p-1 ring-1 ring-black/5 group-data-[collapsible=icon]:h-8 group-data-[collapsible=icon]:w-8 group-data-[collapsible=icon]:rounded-md group-data-[collapsible=icon]:p-0.5 dark:bg-white dark:ring-white/15">
                <AppLogoIcon className="h-full w-auto max-w-full object-contain object-center" />
            </div>
            <div className="ml-1 grid min-w-0 flex-1 text-left text-sm group-data-[collapsible=icon]:hidden">
                <span className="mb-0.5 truncate leading-tight font-bold tracking-tight text-sidebar-foreground">
                    IDHYAL
                </span>
                <span className="truncate text-[10px] leading-none text-[var(--muted-fg)]">
                    Control de gastos
                </span>
            </div>
        </>
    );
}
