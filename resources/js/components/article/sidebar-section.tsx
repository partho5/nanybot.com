import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export const SECTION_HEADING =
    'text-xs font-semibold tracking-wider text-muted-foreground uppercase';

/** A titled card in the editor sidebar. */
export default function SidebarSection({
    title,
    children,
    className,
}: {
    title: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <section
            aria-label={title}
            className={cn(
                'space-y-4 rounded-lg border bg-card p-4 text-card-foreground',
                className,
            )}
        >
            <h3 className={SECTION_HEADING}>{title}</h3>
            {children}
        </section>
    );
}
