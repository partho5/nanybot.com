import { ImageIcon, TriangleAlert } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import type { SeoPreviewData } from './form-utils';

const TITLE_MAX = 60;
const DESC_MAX = 160;
const DESC_MIN = 70;

function Heading({ children }: { children: ReactNode }) {
    return (
        <p className="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
            {children}
        </p>
    );
}

function Warning({ children }: { children: ReactNode }) {
    return (
        <li className="flex items-start gap-1.5 text-xs text-muted-foreground">
            <TriangleAlert
                className="mt-px size-3.5 shrink-0 text-brand"
                aria-hidden="true"
            />
            <span>{children}</span>
        </li>
    );
}

/** Google-style result, live from the form with the same fallbacks the server uses. */
export function SearchPreview({ seo }: { seo: SeoPreviewData }) {
    const titleLength = seo.title.length;
    const descLength = seo.rawDescription.length;
    const shownTitle =
        titleLength > TITLE_MAX
            ? `${seo.title.slice(0, TITLE_MAX).trimEnd()}…`
            : seo.title;

    return (
        <div className="space-y-2">
            <Heading>Search result preview</Heading>
            <div className="rounded-md border bg-background p-3">
                <p className="truncate text-xs text-muted-foreground">
                    {seo.breadcrumb}
                </p>
                <p
                    className={cn(
                        'mt-1 text-lg leading-snug break-words',
                        seo.title ? 'text-foreground' : 'text-muted-foreground',
                    )}
                >
                    {shownTitle || 'Article title'}
                </p>
                <p
                    className={cn(
                        'mt-1 text-sm leading-snug break-words',
                        seo.description
                            ? 'text-foreground/80'
                            : 'text-muted-foreground',
                    )}
                >
                    {seo.description ||
                        'Add a meta description or an excerpt to control this text.'}
                </p>
            </div>

            {(titleLength > TITLE_MAX ||
                descLength > DESC_MAX ||
                (descLength > 0 && descLength < DESC_MIN)) && (
                <ul className="space-y-1" aria-label="Search preview warnings">
                    {titleLength > TITLE_MAX && (
                        <Warning>
                            Title is {titleLength} characters. Google usually
                            cuts it off after about {TITLE_MAX}.
                        </Warning>
                    )}
                    {descLength > DESC_MAX && (
                        <Warning>
                            Description is {descLength} characters. It will be
                            cut off after {DESC_MAX}.
                        </Warning>
                    )}
                    {descLength > 0 && descLength < DESC_MIN && (
                        <Warning>
                            Description is only {descLength} characters. Aim for{' '}
                            {DESC_MIN}-{DESC_MAX} so it tells readers enough.
                        </Warning>
                    )}
                </ul>
            )}
        </div>
    );
}

/** Link-card (Open Graph / X large card) look: 16:9 image, domain, title, description. */
export function SocialPreview({ seo }: { seo: SeoPreviewData }) {
    return (
        <div className="space-y-2">
            <Heading>Social card preview</Heading>
            <div className="overflow-hidden rounded-md border bg-background">
                {seo.image ? (
                    <img
                        src={seo.image}
                        alt={seo.imageAlt}
                        className="aspect-video w-full bg-muted object-cover"
                    />
                ) : (
                    <div
                        className="flex aspect-video w-full items-center justify-center bg-muted text-muted-foreground"
                        role="img"
                        aria-label="No featured image"
                    >
                        <ImageIcon className="size-8" aria-hidden="true" />
                    </div>
                )}
                <div className="space-y-1 border-t p-3">
                    <p className="text-xs tracking-wide text-muted-foreground uppercase">
                        {seo.domain}
                    </p>
                    <p
                        className={cn(
                            'line-clamp-2 text-sm leading-snug font-semibold break-words',
                            !seo.title && 'text-muted-foreground',
                        )}
                    >
                        {seo.title || 'Article title'}
                    </p>
                    {seo.description && (
                        <p className="line-clamp-2 text-xs leading-snug break-words text-muted-foreground">
                            {seo.description}
                        </p>
                    )}
                </div>
            </div>
        </div>
    );
}
