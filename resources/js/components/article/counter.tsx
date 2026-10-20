import { cn } from '@/lib/utils';

export default function Counter({
    value,
    max,
}: {
    value: string;
    max: number;
}) {
    return (
        <span
            className={cn(
                'text-xs tabular-nums',
                value.length > max
                    ? 'text-destructive'
                    : 'text-muted-foreground',
            )}
        >
            {value.length}/{max}
        </span>
    );
}
