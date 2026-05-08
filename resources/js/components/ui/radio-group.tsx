import { cn } from '@/lib/utils';
import * as React from 'react';

type RadioGroupContextValue = {
    name: string;
    value: string;
    onValueChange?: (value: string) => void;
};

const RadioGroupContext = React.createContext<RadioGroupContextValue | null>(
    null,
);

export function RadioGroup({
    value,
    onValueChange,
    name,
    className,
    children,
}: {
    value: string;
    onValueChange?: (value: string) => void;
    name?: string;
    className?: string;
    children: React.ReactNode;
}) {
    const generatedName = React.useId();
    return (
        <RadioGroupContext.Provider
            value={{ name: name ?? generatedName, value, onValueChange }}
        >
            <div role="radiogroup" className={cn('flex flex-col gap-2', className)}>
                {children}
            </div>
        </RadioGroupContext.Provider>
    );
}

export function RadioGroupItem({
    value,
    id,
    disabled,
    className,
}: {
    value: string;
    id?: string;
    disabled?: boolean;
    className?: string;
}) {
    const ctx = React.useContext(RadioGroupContext);
    if (!ctx) {
        throw new Error('RadioGroupItem must be used within RadioGroup');
    }
    const checked = ctx.value === value;
    return (
        <input
            type="radio"
            id={id}
            name={ctx.name}
            value={value}
            checked={checked}
            disabled={disabled}
            onChange={() => ctx.onValueChange?.(value)}
            className={cn(
                'size-4 shrink-0 cursor-pointer accent-primary',
                className,
            )}
        />
    );
}
