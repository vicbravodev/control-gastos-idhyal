import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex h-10 w-auto max-w-[3.5rem] shrink-0 items-center justify-center rounded-lg bg-white p-1 shadow-sm ring-1 ring-black/10 dark:bg-white dark:ring-white/25 group-data-[collapsible=icon]:h-8 group-data-[collapsible=icon]:max-w-8 group-data-[collapsible=icon]:rounded-md group-data-[collapsible=icon]:p-0.5">
                <AppLogoIcon className="h-8 w-auto max-w-full object-contain object-center group-data-[collapsible=icon]:h-[1.65rem]" />
            </div>
            <div className="ml-1 grid min-w-0 flex-1 text-left text-sm group-data-[collapsible=icon]:hidden">
                <span className="mb-0.5 truncate leading-tight font-bold tracking-tight">
                    IDHYAL
                </span>
                <span className="truncate text-[10px] leading-none text-sidebar-foreground/60">
                    Control de gastos
                </span>
            </div>
        </>
    );
}
