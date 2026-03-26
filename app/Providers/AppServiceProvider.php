<?php

namespace App\Providers;

use App\Enums\RoleSlug;
use App\Models\Budget;
use App\Models\ExpenseReport;
use App\Models\ExpenseRequest;
use App\Models\Payment;
use App\Models\Region;
use App\Models\Role;
use App\Models\Settlement;
use App\Models\State;
use App\Models\User;
use App\Models\VacationRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureMorphMap();
        $this->configureAuthorization();
        $this->configureDefaults();
    }

    /**
     * Status-gated workflow abilities always defer to their policy so that
     * SuperAdmin cannot bypass status checks (e.g. approving a request that
     * is in settlement_pending).  All other abilities still resolve to true
     * for SuperAdmin so they retain full read / admin access.
     */
    protected function configureAuthorization(): void
    {
        $statusGated = [
            'update',
            'cancel',
            'recordPayment',
            'saveExpenseReportDraft',
            'submitExpenseReport',
            'reviewExpenseReport',
            'recordSettlementLiquidation',
            'closeSettlement',
            'addSubmissionAttachments',
            'deleteSubmissionAttachment',
            'approveApproval',
            'rejectApproval',
            'approve',
            'reject',
        ];

        Gate::before(function (?User $user, string $ability) use ($statusGated): ?bool {
            if ($user === null || ! $user->hasRole(RoleSlug::SuperAdmin)) {
                return null;
            }

            if (in_array($ability, $statusGated, true)) {
                return null;
            }

            return true;
        });
    }

    /**
     * Canonical morph keys for polymorphic columns (data-dictionary-stage2).
     */
    protected function configureMorphMap(): void
    {
        Relation::enforceMorphMap([
            'budget' => Budget::class,
            'region' => Region::class,
            'state' => State::class,
            'user' => User::class,
            'role' => Role::class,
            'expense_request' => ExpenseRequest::class,
            'expense_report' => ExpenseReport::class,
            'payment' => Payment::class,
            'settlement' => Settlement::class,
            'vacation_request' => VacationRequest::class,
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
