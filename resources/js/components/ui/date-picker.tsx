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
};

export function DatePicker({
    value,
    onChange,
    placeholder = 'Seleccionar fecha',
    disabled = false,
    className,
    id,
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
                    autoFocus
                />
            </PopoverContent>
        </Popover>
    );
}
