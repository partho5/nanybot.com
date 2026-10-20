import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/** Admin list empty state: icon, one line, one primary action. */
export default function EmptyState({
    icon: Icon,
    message,
    action,
    className,
}: {
    icon: LucideIcon;
    message: ReactNode;
    action?: ReactNode;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'flex flex-col items-center gap-4 rounded-lg border border-dashed px-6 py-12 text-center',
                className,
            )}
        >
            <span
                aria-hidden="true"
                className="flex size-10 items-center justify-center rounded-md bg-secondary text-secondary-foreground"
            >
                <Icon className="size-5" />
            </span>
            <p className="text-sm text-muted-foreground">{message}</p>
            {action}
        </div>
    );
}

/** Focus (and scroll to) the first field of an "Add …" form on the same page. */
export function focusField(id: string) {
    const el = document.getElementById(id);
    el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    el?.focus({ preventScroll: true });
}
