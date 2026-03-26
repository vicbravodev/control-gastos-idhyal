import { useState } from 'react';
import { NumericFormat } from 'react-number-format';
import { cn } from '@/lib/utils';

type CurrencyInputProps = {
    id?: string;
    name?: string;
    value?: number;
    defaultValue?: number;
    onChange?: (cents: number) => void;
    required?: boolean;
    disabled?: boolean;
    placeholder?: string;
    className?: string;
    'aria-invalid'?: boolean;
};

/**
 * Displays a peso-formatted input ($1,000.00) but works with centavos internally.
 * The hidden field (if `name` provided) sends centavos to the server.
 * The `onChange` callback also receives centavos.
 */
export function CurrencyInput({
    id,
    name,
    value,
    defaultValue,
    onChange,
    required,
    disabled,
    placeholder = '$0.00',
    className,
    ...rest
}: CurrencyInputProps) {
    const [internalCents, setInternalCents] = useState<number | ''>(
        defaultValue ?? '',
    );

    const isControlled = value !== undefined;
    const centavos = isControlled ? value : internalCents;

    const displayValue =
        centavos !== '' && centavos != null ? centavos / 100 : undefined;

    return (
        <div className="relative">
            <NumericFormat
                id={id}
                thousandSeparator=","
                decimalSeparator="."
                decimalScale={2}
                fixedDecimalScale
                prefix="$"
                allowNegative={false}
                value={displayValue}
                onValueChange={(values) => {
                    const cents =
                        values.floatValue != null
                            ? Math.round(values.floatValue * 100)
                            : 0;
                    if (!isControlled) {
                        setInternalCents(cents);
                    }
                    onChange?.(cents);
                }}
                required={required}
                disabled={disabled}
                placeholder={placeholder}
                className={cn(
                    'border-input placeholder:text-muted-foreground flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 pr-12 text-base tabular-nums shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                    'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
                    className,
                )}
                {...rest}
            />
            {name && (
                <input
                    type="hidden"
                    name={name}
                    value={centavos !== '' ? centavos : ''}
                />
            )}
            <span className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-xs text-muted-foreground">
                MXN
            </span>
        </div>
    );
}
