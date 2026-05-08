import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    CalendarDays,
    ClipboardList,
    FileBarChart,
    FileSearch,
    Inbox,
    KeyRound,
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
import DepartmentController from '@/actions/App/Http/Controllers/Admin/DepartmentController';
import RegionController from '@/actions/App/Http/Controllers/Admin/RegionController';
import RoleController from '@/actions/App/Http/Controllers/Admin/RoleController';
import StaffUserController from '@/actions/App/Http/Controllers/Admin/StaffUserController';
import StateController from '@/actions/App/Http/Controllers/Admin/StateController';
import ApprovalPolicyController from '@/actions/App/Http/Controllers/ApprovalPolicies/ApprovalPolicyController';
import BudgetController from '@/actions/App/Http/Controllers/Budgets/BudgetController';
import ExpenseConceptController from '@/actions/App/Http/Controllers/ExpenseConcepts/ExpenseConceptController';
import ExpenseReportController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseReportController';
import ExpenseRequestApprovalController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestApprovalController';
import ExpenseRequestController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestController';
import ExpenseRequestPaymentController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestPaymentController';
import ExpenseRequestSettlementController from '@/actions/App/Http/Controllers/ExpenseRequests/ExpenseRequestSettlementController';
import ExpenseAnalyticsController from '@/actions/App/Http/Controllers/Reports/ExpenseAnalyticsController';
import VacationRequestApprovalController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestApprovalController';
import VacationRequestController from '@/actions/App/Http/Controllers/VacationRequests/VacationRequestController';
import VacationRuleController from '@/actions/App/Http/Controllers/VacationRules/VacationRuleController';
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

export function AppSidebar() {
    const { auth } = usePage().props;
    const permissions = auth.user?.permissions ?? [];
    const has = (slug: string) => permissions.includes(slug);

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

        if (has('expense_request.approve') || has('expense_request.reject')) {
            gastos.push({
                title: 'Pendientes de aprobar',
                href: ExpenseRequestApprovalController.pending.url(),
                icon: Inbox,
            });
        }

        if (has('expense_request.record_payment') || has('payment.create')) {
            gastos.push({
                title: 'Pagos pendientes',
                href: ExpenseRequestPaymentController.pending.url(),
                icon: Wallet,
            });
        }

        if (has('expense_report.review')) {
            gastos.push({
                title: 'Comprobaciones por revisar',
                href: ExpenseReportController.pendingReview.url(),
                icon: FileSearch,
            });
        }

        if (has('expense_request.view_pending_balances')) {
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

        if (has('vacation_request.approve') || has('vacation_request.reject')) {
            vacaciones.push({
                title: 'Vacaciones por aprobar',
                href: VacationRequestApprovalController.pending.url(),
                icon: ListChecks,
            });
        }

        const reportes: NavItem[] = [];

        if (has('report.expenses.view')) {
            reportes.push({
                title: 'Reportes de gastos',
                href: ExpenseAnalyticsController.index.url(),
                icon: FileBarChart,
            });
        }

        const administracion: NavItem[] = [];

        if (has('budget.manage') || has('budget.view_any')) {
            administracion.push({
                title: 'Presupuestos',
                href: BudgetController.index.url(),
                icon: PiggyBank,
            });
        }

        if (has('expense_concept.manage')) {
            administracion.push({
                title: 'Conceptos de gasto',
                href: ExpenseConceptController.index.url(),
                icon: Layers3,
            });
        }

        if (has('vacation_rule.manage')) {
            administracion.push({
                title: 'Reglas de vacaciones',
                href: VacationRuleController.index.url(),
                icon: Palmtree,
            });
        }

        if (has('approval_policy.view_any') || has('approval_policy.manage')) {
            administracion.push({
                title: 'Políticas de aprobación',
                href: ApprovalPolicyController.index.url(),
                icon: ShieldCheck,
            });
        }

        if (has('admin.users.manage') || has('admin.users.view')) {
            administracion.push({
                title: 'Usuarios',
                href: StaffUserController.index.url(),
                icon: Users,
            });
        }

        if (has('admin.roles.manage')) {
            administracion.push({
                title: 'Roles',
                href: RoleController.index.url(),
                icon: KeyRound,
            });
        }

        if (has('admin.departments.manage')) {
            administracion.push({
                title: 'Departamentos',
                href: DepartmentController.index.url(),
                icon: Building2,
            });
        }

        if (has('admin.regions.manage')) {
            administracion.push({
                title: 'Regiones',
                href: RegionController.index.url(),
                icon: MapPinned,
            });
        }

        if (has('admin.states.manage')) {
            administracion.push({
                title: 'Estados',
                href: StateController.index.url(),
                icon: Map,
            });
        }

        return [
            { label: 'Inicio', items: inicio },
            { label: 'Gastos', items: gastos },
            { label: 'Vacaciones', items: vacaciones },
            ...(reportes.length > 0 ? [{ label: 'Reportes', items: reportes }] : []),
            { label: 'Administración', items: administracion },
        ];
    }, [permissions.join(',')]);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            asChild
                            tooltip="IDHYAL — Control de gastos"
                        >
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
