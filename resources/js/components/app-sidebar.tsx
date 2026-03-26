import { Link, usePage } from '@inertiajs/react';
import {
    CalendarDays,
    ClipboardList,
    FileBarChart,
    FileSearch,
    Inbox,
    LayoutGrid,
    ListChecks,
    Layers3,
    Map,
    MapPinned,
    Palmtree,
    PiggyBank,
    Scale,
    ShieldCheck,
    Users,
    Wallet,
} from 'lucide-react';
import { useMemo } from 'react';
import ApprovalPolicyController from '@/actions/App/Http/Controllers/ApprovalPolicies/ApprovalPolicyController';
import ExpenseAnalyticsController from '@/actions/App/Http/Controllers/Reports/ExpenseAnalyticsController';
import RegionController from '@/actions/App/Http/Controllers/Admin/RegionController';
import StateController from '@/actions/App/Http/Controllers/Admin/StateController';
import StaffUserController from '@/actions/App/Http/Controllers/Admin/StaffUserController';
import BudgetController from '@/actions/App/Http/Controllers/Budgets/BudgetController';
import ExpenseConceptController from '@/actions/App/Http/Controllers/ExpenseConcepts/ExpenseConceptController';
import ExpenseReportController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseReportController';
import ExpenseRequestApprovalController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestApprovalController';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import ExpenseRequestPaymentController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestPaymentController';
import ExpenseRequestSettlementController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestSettlementController';
import VacationRuleController from '@/actions/App/Http/Controllers/VacationRules/VacationRuleController';
import VacationRequestApprovalController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestApprovalController';
import VacationRequestController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestController';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavGroup } from '@/types';

const EXPENSE_APPROVER_SLUGS = new Set([
    'coord_regional',
    'contabilidad',
    'secretario_general',
    'super_admin',
]);

const VACATION_APPROVER_SLUGS = new Set([
    'secretario_general',
    'super_admin',
]);

export function AppSidebar() {
    const { auth } = usePage().props;
    const roleSlug = auth.user?.role?.slug;

    const mainNavGroups = useMemo((): NavGroup[] => {
        const inicio: NavItem[] = [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
        ];

        const gastos: NavItem[] = [
            {
                title: 'Solicitudes de gasto',
                href: ExpenseRequestController.index.url(),
                icon: ClipboardList,
            },
        ];

        if (roleSlug && EXPENSE_APPROVER_SLUGS.has(roleSlug)) {
            gastos.push({
                title: 'Pendientes de aprobar',
                href: ExpenseRequestApprovalController.pending.url(),
                icon: Inbox,
            });
        }

        if (roleSlug === 'contabilidad') {
            gastos.push(
                {
                    title: 'Pagos pendientes',
                    href: ExpenseRequestPaymentController.pending.url(),
                    icon: Wallet,
                },
                {
                    title: 'Comprobaciones por revisar',
                    href: ExpenseReportController.pendingReview.url(),
                    icon: FileSearch,
                },
            );
        }

        if (
            auth.user?.has_expense_request_oversight ||
            roleSlug === 'super_admin'
        ) {
            gastos.push({
                title: 'Balances pendientes',
                href: ExpenseRequestSettlementController.pendingBalances.url(),
                icon: Scale,
            });
        }

        const vacaciones: NavItem[] = [
            {
                title: 'Vacaciones',
                href: VacationRequestController.index.url(),
                icon: CalendarDays,
            },
        ];

        if (roleSlug && VACATION_APPROVER_SLUGS.has(roleSlug)) {
            vacaciones.push({
                title: 'Vacaciones por aprobar',
                href: VacationRequestApprovalController.pending.url(),
                icon: ListChecks,
            });
        }

        const reportes: NavItem[] = [];

        if (auth.user?.can_view_reports) {
            reportes.push({
                title: 'Reportes de gastos',
                href: ExpenseAnalyticsController.index.url(),
                icon: FileBarChart,
            });
        }

        const administracion: NavItem[] = [];

        if (auth.user?.can_manage_budgets) {
            administracion.push(
                {
                    title: 'Conceptos de gasto',
                    href: ExpenseConceptController.index.url(),
                    icon: Layers3,
                },
                {
                    title: 'Presupuestos',
                    href: BudgetController.index.url(),
                    icon: PiggyBank,
                },
            );
        }

        if (auth.user?.can_manage_vacation_rules) {
            administracion.push({
                title: 'Reglas de vacaciones',
                href: VacationRuleController.index.url(),
                icon: Palmtree,
            });
        }

        if (auth.user?.can_manage_approval_policies) {
            administracion.push({
                title: 'Políticas de aprobación',
                href: ApprovalPolicyController.index.url(),
                icon: ShieldCheck,
            });
        }

        if (roleSlug === 'super_admin') {
            administracion.push(
                {
                    title: 'Regiones',
                    href: RegionController.index.url(),
                    icon: MapPinned,
                },
                {
                    title: 'Estados',
                    href: StateController.index.url(),
                    icon: Map,
                },
                {
                    title: 'Usuarios',
                    href: StaffUserController.index.url(),
                    icon: Users,
                },
            );
        }

        return [
            { label: 'Inicio', items: inicio },
            { label: 'Gastos', items: gastos },
            { label: 'Vacaciones', items: vacaciones },
            ...(reportes.length > 0 ? [{ label: 'Reportes', items: reportes }] : []),
            { label: 'Administración', items: administracion },
        ];
    }, [
        auth.user?.can_manage_approval_policies,
        auth.user?.can_manage_budgets,
        auth.user?.can_manage_vacation_rules,
        auth.user?.has_expense_request_oversight,
        roleSlug,
    ]);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain groups={mainNavGroups} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
