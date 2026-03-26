import { ChevronLeft, ChevronRight } from 'lucide-react';
import * as React from 'react';
import { DayPicker } from 'react-day-picker';
import { es } from 'react-day-picker/locale';
import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export type CalendarProps = React.ComponentProps<typeof DayPicker>;

function Calendar({
    className,
    classNames,
    showOutsideDays = true,
    ...props
}: CalendarProps) {
    return (
        <DayPicker
            locale={es}
            showOutsideDays={showOutsideDays}
            className={cn('p-3', className)}
            classNames={{
                months: 'flex flex-col sm:flex-row space-y-4 sm:space-y-0 relative',
                month: 'space-y-4',
                month_caption: 'flex justify-center pt-1 relative items-center',
                caption_label: 'text-sm font-medium capitalize',
                nav: 'flex items-center justify-between absolute inset-x-0',
                button_previous: cn(
                    buttonVariants({ variant: 'outline' }),
                    'h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100 z-10',
                ),
                button_next: cn(
                    buttonVariants({ variant: 'outline' }),
                    'h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100 z-10',
                ),
                month_grid: 'w-full border-collapse space-y-1',
                weekdays: 'flex',
                weekday:
                    'text-muted-foreground rounded-md w-9 font-normal text-[0.8rem] capitalize',
                weeks: 'w-full border-collapse',
                week: 'flex w-full mt-2',
                day: 'h-9 w-9 text-center text-sm p-0 relative focus-within:relative focus-within:z-20',
                day_button: cn(
                    buttonVariants({ variant: 'ghost' }),
                    'h-9 w-9 p-0 font-normal aria-selected:opacity-100',
                ),
                range_end: 'day-range-end rounded-l-none',
                range_start: 'day-range-start rounded-r-none',
                range_middle:
                    'aria-selected:bg-accent dark:bg-accent/40 rounded-none aria-selected:text-accent-foreground',
                selected:
                    'bg-primary text-primary-foreground hover:bg-primary hover:text-primary-foreground focus:bg-primary focus:text-primary-foreground',
                today: 'bg-accent text-accent-foreground dark:bg-accent/20',
                outside:
                    'day-outside text-muted-foreground opacity-50 aria-selected:bg-accent/50 aria-selected:text-muted-foreground aria-selected:opacity-30',
                disabled: 'text-muted-foreground opacity-50',
                hidden: 'invisible',
                ...classNames,
            }}
            components={{
                Chevron: ({ ...chevronProps }) =>
                    chevronProps.orientation === 'left' ? (
                        <ChevronLeft
                            {...chevronProps}
                            className="h-4 w-4"
                        />
                    ) : (
                        <ChevronRight
                            {...chevronProps}
                            className="h-4 w-4"
                        />
                    ),
            }}
            {...props}
        />
    );
}

Calendar.displayName = 'Calendar';

export { Calendar };
