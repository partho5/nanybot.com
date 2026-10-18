import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import FlashMessages from '@/components/flash-messages';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
    StatusOption,
} from '@/types/article';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Articles', href: index() }];

type Filters = {
    q?: string | null;
    status?: string | null;
    category?: string | null;
    author?: string | null;
};

type Props = {
    articles: Paginated<ArticleRow>;
    filters: Filters;
    categories: CategoryOption[];
    authors: AuthorOption[];
    statuses: StatusOption[];
    isAdmin: boolean;
};

const SELECT =
    'h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

const STATUS_VARIANT: Record<
    ArticleStatus,
    'default' | 'secondary' | 'outline'
> = {
    published: 'default',
    scheduled: 'secondary',
    draft: 'outline',
};

function formatDate(iso: string | null): string {
    return iso
        ? new Date(iso).toLocaleDateString(undefined, {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          })
        : '';
}

export default function ArticlesIndex({
    articles,
    filters,
    categories,
    authors,
    statuses,
    isAdmin,
}: Props) {
    const [q, setQ] = useState(filters.q ?? '');

    function applyFilters(next: Filters) {
        const merged: Record<string, string> = {};

        for (const [key, value] of Object.entries({ ...filters, ...next })) {
            if (value) {
                merged[key] = value;
            }
        }

        router.get(index.url(), merged, {
            preserveState: true,
            replace: true,
        });
    }

    function handleSearch(e: FormEvent) {
        e.preventDefault();
        applyFilters({ q: q.trim() });
    }

    function handleDelete(article: ArticleRow) {
        if (
            !window.confirm(`Delete "${article.title}"? This cannot be undone.`)
        ) {
            return;
        }

        router.delete(destroy.url(article.slug), { preserveScroll: true });
    }

    const hasFilters = Boolean(
        filters.q || filters.status || filters.category || filters.author,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Articles" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
                <FlashMessages />

                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title={isAdmin ? 'All articles' : 'My articles'}
                        description={`${articles.total} ${articles.total === 1 ? 'article' : 'articles'}`}
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus /> New article
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <form onSubmit={handleSearch} className="flex gap-2">
                        <Input
                            type="search"
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            placeholder="Search titles…"
                            className="w-56"
                            aria-label="Search titles"
                        />
                        <Button type="submit" variant="secondary">
                            Search
                        </Button>
                    </form>

                    <select
                        aria-label="Filter by status"
                        className={SELECT}
                        value={filters.status ?? ''}
                        onChange={(e) =>
                            applyFilters({ status: e.target.value })
                        }
                    >
                        <option value="">All statuses</option>
                        {statuses.map((s) => (
                            <option key={s.value} value={s.value}>
                                {s.label}
                            </option>
                        ))}
                    </select>

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

                    {hasFilters && (
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => {
                                setQ('');
                                router.get(index.url());
                            }}
                        >
                            Clear
                        </Button>
                    )}
                </div>

                {articles.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-12 text-center text-muted-foreground">
                        {hasFilters ? (
                            'No articles match these filters.'
                        ) : (
                            <>
                                No articles yet.{' '}
                                <Link
                                    href={create()}
                                    className="font-medium text-primary underline"
                                >
                                    Write the first one.
                                </Link>
                            </>
                        )}
                    </div>
                ) : (
                    <ul className="divide-y rounded-xl border">
                        {articles.data.map((article) => (
                            <li
                                key={article.id}
                                className="flex flex-wrap items-start justify-between gap-3 px-4 py-4"
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Link
                                            href={edit(article.slug)}
                                            className="font-medium hover:underline"
                                        >
                                            {article.title}
                                        </Link>
                                        <Badge
                                            variant={
                                                STATUS_VARIANT[article.status]
                                            }
                                        >
                                            {article.status}
                                        </Badge>
                                    </div>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {article.category?.name ??
                                            'Uncategorized'}
                                        {isAdmin && article.author && (
                                            <> · {article.author.name}</>
                                        )}
                                        <>
                                            {' '}
                                            · {article.read_time_minutes} min
                                            read
                                        </>
                                        {article.published_at && (
                                            <>
                                                {' · '}
                                                {article.status === 'scheduled'
                                                    ? 'Scheduled for '
                                                    : 'Published '}
                                                {formatDate(
                                                    article.published_at,
                                                )}
                                            </>
                                        )}
                                        <>
                                            {' '}
                                            · Updated{' '}
                                            {formatDate(article.updated_at)}
                                        </>
                                    </p>
                                </div>

                                <div className="flex shrink-0 gap-2">
                                    {article.public_url && (
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            asChild
                                        >
                                            <a
                                                href={article.public_url}
                                                target="_blank"
                                                rel="noopener"
                                            >
                                                View
                                            </a>
                                        </Button>
                                    )}
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={edit(article.slug)}>
                                            Edit
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        onClick={() => handleDelete(article)}
                                    >
                                        Delete
                                    </Button>
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
