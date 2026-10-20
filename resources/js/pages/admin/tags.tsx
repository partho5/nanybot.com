import { Head, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import FlashMessages from '@/components/admin/flash-messages';
import PaginationNav from '@/components/admin/pagination-nav';
import TaxonomyManager from '@/components/admin/taxonomy-manager';
import type { TaxonomyRow } from '@/components/admin/taxonomy-manager';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { index as articlesIndex } from '@/routes/admin/articles';
import { destroy, index, store, update } from '@/routes/admin/tags';
import type { BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/article';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Tags', href: index() }];

export default function Tags({
    tags,
    filters,
}: {
    tags: Paginated<TaxonomyRow>;
    filters: { q: string };
}) {
    const [q, setQ] = useState(filters.q ?? '');

    function search(e: React.FormEvent) {
        e.preventDefault();
        router.get(index.url(), q.trim() ? { q: q.trim() } : {}, {
            preserveState: true,
            replace: true,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tags" />

            <div className="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
                <Heading
                    title="Tags"
                    description="Tags are also created automatically when you type them in the article editor."
                />
                <FlashMessages />

                <form onSubmit={search} className="flex gap-2" role="search">
                    <Input
                        type="search"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Search tags…"
                        aria-label="Search tags"
                    />
                    <Button type="submit" variant="secondary">
                        <Search /> Search
                    </Button>
                    {filters.q && (
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
                </form>

                <TaxonomyManager
                    noun="tag"
                    rows={tags.data}
                    storeForm={store.form()}
                    updateForm={(slug) => update.form(slug)}
                    destroyUrl={(slug) => destroy.url(slug)}
                    articlesUrl={(row) =>
                        articlesIndex.url({ query: { tag: row.slug } })
                    }
                    emptyMessage={
                        filters.q
                            ? `No tags match "${filters.q}".`
                            : 'No tags yet. Add your first one above.'
                    }
                />

                <PaginationNav page={tags} noun="tag" />
            </div>
        </AppLayout>
    );
}
