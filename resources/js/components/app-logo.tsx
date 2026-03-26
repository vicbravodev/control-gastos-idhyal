import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex size-9 items-center justify-center">
                <AppLogoIcon className="size-9" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
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
