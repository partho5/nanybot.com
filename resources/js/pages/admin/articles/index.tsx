import { Head, Link, router } from '@inertiajs/react';
import {
    Eye,
    ExternalLink,
    FileText,
    Pencil,
    Plus,
    SearchX,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import EmptyState from '@/components/admin/empty-state';
import FlashMessages from '@/components/flash-messages';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { create, destroy, edit, index } from '@/routes/admin/articles';
import type { BreadcrumbItem } from '@/types';
import type {
    ArticleRow,
    ArticleStatus,
    AuthorOption,
    CategoryOption,
    Paginated,
    StatusCounts,
} from '@/types/article';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Articles', href: index() }];

type Filters = {
    q?: string | null;
    status?: string | null;
    category?: string | null;
    tag?: string | null;
    author?: string | null;
};

type Props = {
    articles: Paginated<ArticleRow>;
    filters: Filters;
    categories: CategoryOption[];
    authors: AuthorOption[];
    counts: StatusCounts;
    isAdmin: boolean;
};

const SELECT =
    'h-9 min-w-0 flex-1 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:flex-none';

const TABS: { status: ArticleStatus | null; label: string }[] = [
    { status: null, label: 'All' },
    { status: 'published', label: 'Published' },
    { status: 'scheduled', label: 'Scheduled' },
    { status: 'draft', label: 'Drafts' },
];

const STATUS_LABEL: Record<ArticleStatus, string> = {
    published: 'Published',
    scheduled: 'Scheduled',
    draft: 'Draft',
};

/** Colour is a hint only; the label always carries the meaning. */
const STATUS_DOT: Record<ArticleStatus, string> = {
    published: 'bg-emerald-600 dark:bg-emerald-400',
    scheduled: 'bg-brand',
    draft: 'border border-muted-foreground',
};

function absoluteDate(iso: string): string {
    return new Date(iso).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function shortDate(iso: string): string {
    const date = new Date(iso);

    return date.toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year:
            date.getFullYear() === new Date().getFullYear()
                ? undefined
                : 'numeric',
    });
}

/** "just now", "5m ago", "3h ago", "2d ago", then a short date. */
function timeAgo(iso: string): string {
    const seconds = Math.max(
        0,
        Math.round((Date.now() - new Date(iso).getTime()) / 1000),
    );

    if (seconds < 60) {
        return 'just now';
    }

    if (seconds < 3600) {
        return `${Math.floor(seconds / 60)}m ago`;
    }

    if (seconds < 86400) {
        return `${Math.floor(seconds / 3600)}h ago`;
    }

    if (seconds < 86400 * 30) {
        return `${Math.floor(seconds / 86400)}d ago`;
    }

    return shortDate(iso);
}

function Thumbnail({ article }: { article: ArticleRow }) {
    const [failed, setFailed] = useState(false);

    if (article.thumbnail_url && !failed) {
        return (
            <img
                src={article.thumbnail_url}
                onError={() => setFailed(true)}
                alt=""
                width={64}
                height={40}
                loading="lazy"
                className="h-10 w-16 shrink-0 rounded-sm bg-muted object-cover"
            />
        );
    }

    const initial = (article.category?.name ?? article.title).trim().charAt(0);

    return (
        <span
            aria-hidden="true"
            className="flex h-10 w-16 shrink-0 items-center justify-center rounded-sm border bg-secondary font-mono text-xs font-medium text-muted-foreground uppercase"
        >
            {initial || '·'}
        </span>
    );
}

const ACTION =
    'size-8 text-muted-foreground hover:text-foreground lg:w-auto lg:px-2.5';

/**
 * Always-visible, subdued row action. Icon + text from lg up; icon-only below,
 * named by the aria-label on the rendered element, with a tooltip.
 */
function RowAction({
    label,
    icon,
    render,
    className,
}: {
    label: string;
    icon: ReactNode;
    /** Receives the icon + responsive label; returns the link element. */
    render: (inner: ReactNode) => ReactNode;
    className?: string;
}) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    asChild
                    className={cn(ACTION, className)}
                >
                    {render(
                        <>
                            {icon}
                            <span className="hidden lg:inline">{label}</span>
                        </>,
                    )}
                </Button>
            </TooltipTrigger>
            <TooltipContent className="lg:hidden">{label}</TooltipContent>
        </Tooltip>
    );
}

export default function ArticlesIndex({
    articles,
    filters,
    categories,
    authors,
    counts,
    isAdmin,
}: Props) {
    const [q, setQ] = useState(filters.q ?? '');

    /** Current filters merged with `next`, empty values dropped (page resets). */
    function query(next: Filters): Record<string, string> {
        const merged: Record<string, string> = {};

        for (const [key, value] of Object.entries({ ...filters, ...next })) {
            if (value) {
                merged[key] = value;
            }
        }

        return merged;
    }

    function applyFilters(next: Filters) {
        router.get(index.url(), query(next), {
            preserveState: true,
            replace: true,
        });
    }

    function handleSearch(e: FormEvent) {
        e.preventDefault();
        applyFilters({ q: q.trim() });
    }

    function clearFilters() {
        setQ('');
        router.get(index.url());
    }

    function handleDelete(article: ArticleRow) {
        if (
            !window.confirm(`Delete "${article.title}"? This cannot be undone.`)
        ) {
            return;
        }

        router.delete(destroy.url(article.slug), { preserveScroll: true });
    }

    const activeStatus = (TABS.find((t) => t.status === filters.status)
        ?.status ?? null) as ArticleStatus | null;
    const hasOtherFilters = Boolean(
        filters.q || filters.category || filters.tag || filters.author,
    );
    const hasFilters = hasOtherFilters || activeStatus !== null;

    let empty: ReactNode = null;

    if (articles.data.length === 0) {
        if (counts.all === 0 && !hasOtherFilters) {
            empty = (
                <EmptyState
                    icon={FileText}
                    message="No articles yet. Write your first one."
                    action={
                        <Button asChild>
                            <Link href={create()}>
                                <Plus /> New article
                            </Link>
                        </Button>
                    }
                />
            );
        } else if (activeStatus && !hasOtherFilters) {
            empty = (
                <EmptyState
                    icon={FileText}
                    message={`No ${STATUS_LABEL[activeStatus].toLowerCase()} articles.`}
                    action={
                        <Button variant="outline" asChild>
                            <Link href={index()}>Show all articles</Link>
                        </Button>
                    }
                />
            );
        } else {
            empty = (
                <EmptyState
                    icon={SearchX}
                    message="No articles match these filters."
                    action={
                        <Button variant="outline" onClick={clearFilters}>
                            Clear filters
                        </Button>
                    }
                />
            );
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Articles" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
                <FlashMessages />

                <div className="flex items-start justify-between gap-4">
                    <h1 className="text-xl font-semibold tracking-tight">
                        {isAdmin ? 'All articles' : 'My articles'}
                    </h1>
                    <Button asChild>
                        <Link href={create()}>
                            <Plus /> New article
                        </Link>
                    </Button>
                </div>

                <nav
                    aria-label="Filter by status"
                    className="-mx-4 [scrollbar-width:none] overflow-x-auto border-b px-4 md:mx-0 md:px-0"
                >
                    <ul className="flex gap-5">
                        {TABS.map((tab) => {
                            const active = tab.status === activeStatus;
                            const count = counts[tab.status ?? 'all'];

                            return (
                                <li key={tab.label}>
                                    <Link
                                        href={index.url({
                                            query: query({
                                                status: tab.status,
                                            }),
                                        })}
                                        preserveScroll
                                        aria-current={
                                            active ? 'page' : undefined
                                        }
                                        className={cn(
                                            '-mb-px flex items-center gap-1.5 border-b-2 py-2.5 text-sm font-medium whitespace-nowrap transition-colors',
                                            active
                                                ? 'border-brand text-foreground'
                                                : 'border-transparent text-muted-foreground hover:text-foreground',
                                        )}
                                    >
                                        {tab.label}
                                        <span className="font-mono text-xs text-muted-foreground tabular-nums">
                                            {count}
                                        </span>
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </nav>

                <div className="flex flex-wrap items-center gap-2">
                    <form
                        onSubmit={handleSearch}
                        role="search"
                        className="flex w-full gap-2 sm:w-auto"
                    >
                        <Input
                            type="search"
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            placeholder="Search titles…"
                            className="min-w-0 flex-1 sm:w-56"
                            aria-label="Search titles"
                        />
                        <Button type="submit" variant="secondary">
                            Search
                        </Button>
                    </form>

                    <select
                        aria-label="Filter by category"
                        className={SELECT}
                        value={filters.category ?? ''}
                        onChange={(e) =>
                            applyFilters({ category: e.target.value })
                        }
                    >
                        <option value="">All categories</option>
                        {categories.map((c) => (
                            <option key={c.id} value={String(c.id)}>
                                {c.name}
                            </option>
                        ))}
                    </select>

                    {isAdmin && (
                        <select
                            aria-label="Filter by author"
                            className={SELECT}
                            value={filters.author ?? ''}
                            onChange={(e) =>
                                applyFilters({ author: e.target.value })
                            }
                        >
                            <option value="">All authors</option>
                            {authors.map((a) => (
                                <option key={a.id} value={String(a.id)}>
                                    {a.name}
                                </option>
                            ))}
                        </select>
                    )}

                    {filters.tag && (
                        <Badge variant="secondary" className="h-9 px-3">
                            Tag: {filters.tag}
                        </Badge>
                    )}

                    {hasFilters && (
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={clearFilters}
                        >
                            Clear
                        </Button>
                    )}
                </div>

                {empty ?? (
                    <ul className="divide-y rounded-lg border bg-card">
                        {articles.data.map((article) => (
                            <li
                                key={article.id}
                                className="grid grid-cols-[4rem_minmax(0,1fr)] items-center gap-x-3 gap-y-1 px-3 py-3 sm:px-4 md:grid-cols-[4rem_minmax(0,1fr)_auto]"
                            >
                                <Thumbnail article={article} />

                                <div className="min-w-0">
                                    <Link
                                        href={edit(article.slug)}
                                        className="line-clamp-2 font-medium underline-offset-4 hover:underline md:line-clamp-1"
                                    >
                                        {article.title}
                                    </Link>
                                    <p className="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-muted-foreground">
                                        <span className="inline-flex items-center gap-1.5 text-foreground">
                                            <span
                                                aria-hidden="true"
                                                className={cn(
                                                    'size-2 rounded-full',
                                                    STATUS_DOT[article.status],
                                                )}
                                            />
                                            {STATUS_LABEL[article.status]}
                                            {article.status === 'scheduled' &&
                                                article.published_at && (
                                                    <time
                                                        dateTime={
                                                            article.published_at
                                                        }
                                                        title={absoluteDate(
                                                            article.published_at,
                                                        )}
                                                        className="text-muted-foreground"
                                                        suppressHydrationWarning
                                                    >
                                                        {' · '}
                                                        {shortDate(
                                                            article.published_at,
                                                        )}
                                                    </time>
                                                )}
                                        </span>
                                        <span aria-hidden="true">·</span>
                                        <span className="truncate">
                                            {article.category?.name ??
                                                'Uncategorized'}
                                            {isAdmin && article.author && (
                                                <> · {article.author.name}</>
                                            )}
                                        </span>
                                        <span
                                            aria-hidden="true"
                                            className="max-md:hidden"
                                        >
                                            ·
                                        </span>
                                        <time
                                            className="max-md:basis-full"
                                            dateTime={article.updated_at}
                                            title={absoluteDate(
                                                article.updated_at,
                                            )}
                                            suppressHydrationWarning
                                        >
                                            Updated{' '}
                                            {timeAgo(article.updated_at)}
                                        </time>
                                    </p>
                                </div>

                                <div className="col-start-2 -ml-2 flex items-center gap-0.5 md:col-start-3 md:row-start-1 md:ml-0">
                                    <RowAction
                                        label="Edit"
                                        icon={<Pencil />}
                                        render={(inner) => (
                                            <Link
                                                href={edit(article.slug)}
                                                aria-label={`Edit ${article.title}`}
                                            >
                                                {inner}
                                            </Link>
                                        )}
                                    />
                                    <RowAction
                                        label="Preview"
                                        icon={<Eye />}
                                        render={(inner) => (
                                            <a
                                                href={article.preview_url}
                                                target="_blank"
                                                rel="noopener"
                                                aria-label={`Preview ${article.title} (opens in a new tab)`}
                                            >
                                                {inner}
                                            </a>
                                        )}
                                    />
                                    {article.public_url && (
                                        <RowAction
                                            label="View live"
                                            icon={<ExternalLink />}
                                            render={(inner) => (
                                                <a
                                                    href={
                                                        article.public_url ??
                                                        undefined
                                                    }
                                                    target="_blank"
                                                    rel="noopener"
                                                    aria-label={`View ${article.title} live (opens in a new tab)`}
                                                >
                                                    {inner}
                                                </a>
                                            )}
                                        />
                                    )}
                                    <DeleteAction
                                        title={article.title}
                                        onDelete={() => handleDelete(article)}
                                    />
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

                {articles.last_page > 1 && (
                    <nav
                        className="flex flex-wrap justify-center gap-1"
                        aria-label="Pagination"
                    >
                        {articles.links.map((link, i) => (
                            <Link
                                key={i}
                                href={link.url ?? '#'}
                                preserveScroll
                                className={cn(
                                    'rounded-md px-3 py-1.5 text-sm',
                                    link.active
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-muted-foreground hover:text-foreground',
                                    !link.url &&
                                        'pointer-events-none opacity-40',
                                )}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </nav>
                )}
            </div>
        </AppLayout>
    );
}

function DeleteAction({
    title,
    onDelete,
}: {
    title: string;
    onDelete: () => void;
}) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={onDelete}
                    aria-label={`Delete ${title}`}
                    className={cn(ACTION, 'hover:text-destructive')}
                >
                    <Trash2 />
                    <span className="hidden lg:inline">Delete</span>
                </Button>
            </TooltipTrigger>
            <TooltipContent className="lg:hidden">Delete</TooltipContent>
        </Tooltip>
    );
}
