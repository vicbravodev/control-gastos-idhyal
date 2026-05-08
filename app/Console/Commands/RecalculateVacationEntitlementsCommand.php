<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\VacationEntitlement;
use App\Services\VacationRequests\VacationEntitlementBalanceResolver;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class RecalculateVacationEntitlementsCommand extends Command
{
    protected $signature = 'vacation:recalculate-entitlements
        {--year= : Calendar year to recalculate (defaults to current year).}
        {--user= : Restrict to a specific user id.}
        {--dry-run : Show diff without persisting any changes.}';

    protected $description = 'Recalcula days_allocated y vacation_rule_id de cada usuario para el año indicado, según las reglas vigentes y su antigüedad.';

    public function handle(VacationEntitlementBalanceResolver $resolver): int
    {
        $year = (int) ($this->option('year') ?: now()->year);
        $userId = $this->option('user');
        $dryRun = (bool) $this->option('dry-run');

        $users = User::query()
            ->whereNotNull('hire_date')
            ->when($userId !== null, fn ($q) => $q->whereKey((int) $userId))
            ->orderBy('id')
            ->get();

        if ($users->isEmpty()) {
            $this->warn('No hay usuarios con hire_date para procesar.');

            return self::SUCCESS;
        }

        $asOf = CarbonImmutable::create($year, 12, 31)->endOfDay();

        $changes = 0;
        $rows = [];

        foreach ($users as $user) {
            /** @var CarbonImmutable $hire */
            $hire = CarbonImmutable::parse($user->hire_date);
            $years = $resolver->serviceYears($hire, $asOf);
            $rule = $resolver->resolveRule($years);

            $current = VacationEntitlement::query()
                ->where('user_id', $user->id)
                ->where('calendar_year', $year)
                ->first();

            $newDays = $rule?->days_granted_per_year ?? 0;
            $newRuleId = $rule?->id;

            $oldDays = $current?->days_allocated ?? 0;
            $oldRuleId = $current?->vacation_rule_id;

            $changed = ($oldDays !== $newDays) || ($oldRuleId !== $newRuleId);

            if ($changed) {
                $changes++;
            }

            $rows[] = [
                $user->id,
                $user->name,
                number_format($years, 2),
                $rule?->code ?? '—',
                $oldDays,
                $newDays,
                $changed ? '✓' : '',
            ];

            if (! $dryRun && $rule !== null) {
                VacationEntitlement::query()->updateOrCreate(
                    ['user_id' => $user->id, 'calendar_year' => $year],
                    [
                        'days_allocated' => $newDays,
                        'vacation_rule_id' => $newRuleId,
                    ],
                );
            }
        }

        $this->table(
            ['User ID', 'Nombre', 'Años', 'Regla', 'Antes', 'Después', 'Cambia'],
            $rows,
        );

        if ($dryRun) {
            $this->info(sprintf('Dry-run: %d usuarios cambiarían (de %d revisados).', $changes, $users->count()));
        } else {
            $this->info(sprintf('%d usuarios actualizados (de %d revisados) para el año %d.', $changes, $users->count(), $year));
        }

        return self::SUCCESS;
    }
}
