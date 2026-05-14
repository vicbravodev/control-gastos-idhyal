import { format, parse } from 'date-fns';
import { es } from 'date-fns/locale';
import { CalendarDays } from 'lucide-react';
import { useCallback, useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

type DatePickerProps = {
    value: string;
    onChange: (iso: string) => void;
    placeholder?: string;
    disabled?: boolean;
    className?: string;
    id?: string;
    /** Earliest month/year selectable. Defaults to 100 years before today. */
    startMonth?: Date;
    /** Latest month/year selectable. Defaults to end of current year. */
    endMonth?: Date;
    /**
     * Forbid future dates. Sets `endMonth` to today and disables days after
     * today. Useful for birthdays, hire dates, payment dates.
     */
    disableFuture?: boolean;
    /**
     * Forbid past dates. Sets `startMonth` to today and disables days before
     * today. Useful for vacation start/end dates.
     */
    disablePast?: boolean;
};

function startOfDay(d: Date): Date {
    return new Date(d.getFullYear(), d.getMonth(), d.getDate());
}

export function DatePicker({
    value,
    onChange,
    placeholder = 'Seleccionar fecha',
    disabled = false,
    className,
    id,
    startMonth,
    endMonth,
    disableFuture = false,
    disablePast = false,
}: DatePickerProps) {
    const selected = useMemo(() => {
        if (!value) return undefined;
        const d = parse(value, 'yyyy-MM-dd', new Date());
        return isNaN(d.getTime()) ? undefined : d;
    }, [value]);

    const handleSelect = useCallback(
        (day: Date | undefined) => {
            if (day) {
                onChange(format(day, 'yyyy-MM-dd'));
            }
        },
        [onChange],
    );

    const { resolvedStartMonth, resolvedEndMonth, disabledMatcher } =
        useMemo(() => {
            const today = startOfDay(new Date());
            let resStart = startMonth;
            let resEnd = endMonth;

            if (disableFuture && resEnd == null) {
                resEnd = today;
            }
            if (disablePast && resStart == null) {
                resStart = today;
            }

            const matchers: Array<(date: Date) => boolean> = [];
            if (disableFuture) {
                matchers.push((date) => startOfDay(date) > today);
            }
            if (disablePast) {
                matchers.push((date) => startOfDay(date) < today);
            }

            return {
                resolvedStartMonth: resStart,
                resolvedEndMonth: resEnd,
                disabledMatcher:
                    matchers.length === 0
                        ? undefined
                        : (date: Date) => matchers.some((m) => m(date)),
            };
        }, [startMonth, endMonth, disableFuture, disablePast]);

    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    disabled={disabled}
                    className={cn(
                        'w-full justify-start text-left font-normal',
                        !value && 'text-muted-foreground',
                        className,
                    )}
                >
                    <CalendarDays className="mr-2 h-4 w-4 shrink-0" />
                    {selected
                        ? format(selected, "d 'de' MMMM 'de' yyyy", {
                              locale: es,
                          })
                        : placeholder}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                    mode="single"
                    selected={selected}
                    onSelect={handleSelect}
                    defaultMonth={selected}
                    startMonth={resolvedStartMonth}
                    endMonth={resolvedEndMonth}
                    disabled={disabledMatcher}
                    autoFocus
                />
            </PopoverContent>
        </Popover>
    );
}
