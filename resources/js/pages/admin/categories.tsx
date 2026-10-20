import { Head } from '@inertiajs/react';
import { FolderTree } from 'lucide-react';
import FlashMessages from '@/components/admin/flash-messages';
import TaxonomyManager from '@/components/admin/taxonomy-manager';
import type { TaxonomyRow } from '@/components/admin/taxonomy-manager';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import { index as articlesIndex } from '@/routes/admin/articles';
import { destroy, index, store, update } from '@/routes/admin/categories';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Categories', href: index() }];

export default function Categories({
    categories,
}: {
    categories: TaxonomyRow[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Categories" />

            <div className="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
                <Heading
                    title="Categories"
                    description="Each article belongs to one category. Deleting a category keeps its articles."
                />
                <FlashMessages />
                <TaxonomyManager
                    noun="category"
                    rows={categories}
                    storeForm={store.form()}
                    updateForm={(slug) => update.form(slug)}
                    destroyUrl={(slug) => destroy.url(slug)}
                    articlesUrl={(row) =>
                        articlesIndex.url({ query: { category: row.id } })
                    }
                    emptyIcon={FolderTree}
                    emptyMessage="No categories yet. Every article needs one."
                />
            </div>
        </AppLayout>
    );
}
