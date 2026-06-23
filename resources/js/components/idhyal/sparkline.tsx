import { Line, LineChart, ResponsiveContainer } from 'recharts';

import { cn } from '@/lib/utils';

type Tone = 'blue' | 'gold' | 'warn' | 'muted';

const STROKE: Record<Tone, string> = {
    blue: 'var(--brand-blue-500)',
    gold: 'var(--brand-gold-500)',
    warn: 'var(--warning)',
    muted: 'var(--muted-foreground)',
};

type SparklineProps = {
    values: number[];
    tone?: Tone;
    height?: number;
    className?: string;
    'aria-label'?: string;
};

export function Sparkline({
    values,
    tone = 'blue',
    height = 28,
    className,
    'aria-label': ariaLabel,
}: SparklineProps) {
    const data = values.map((value, index) => ({ index, value }));
    const stroke = STROKE[tone];

    return (
        <div
            className={cn('h-7 w-full max-w-[112px]', className)}
            style={{ height }}
            role="img"
            aria-label={ariaLabel}
        >
            <ResponsiveContainer width="100%" height="100%">
                <LineChart
                    data={data}
                    margin={{ top: 2, right: 2, bottom: 2, left: 2 }}
                >
                    <Line
                        type="monotone"
                        dataKey="value"
                        stroke={stroke}
                        strokeWidth={1.6}
                        dot={false}
                        isAnimationActive={false}
                    />
                </LineChart>
            </ResponsiveContainer>
        </div>
    );
}
