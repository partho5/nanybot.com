import { Head, router } from '@inertiajs/react';
import ArticleForm from '@/components/article-form';
import FlashMessages from '@/components/flash-messages';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import { create, index } from '@/routes/admin/articles';
import type { BreadcrumbItem } from '@/types';
import type {
    AuthorOption,
    CategoryOption,
    StatusOption,
} from '@/types/article';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Articles', href: index() },
    { title: 'New article', href: create() },
];

type Props = {
    categories: CategoryOption[];
    authors: AuthorOption[];
    statuses: StatusOption[];
    isAdmin: boolean;
    defaultAuthorId: number;
};

export default function ArticleCreate({
    categories,
    authors,
    statuses,
    isAdmin,
    defaultAuthorId,
}: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New article" />

            <div className="mx-auto w-full max-w-6xl space-y-6 p-4 md:p-6">
                <FlashMessages />
                <Heading
                    title="New article"
                    description="Write a post, then publish it now, schedule it, or save it as a draft."
                />
                <ArticleForm
                    categories={categories}
                    authors={authors}
                    statuses={statuses}
                    isAdmin={isAdmin}
                    defaultAuthorId={defaultAuthorId}
                    onCancel={() => router.visit(index())}
                />
            </div>
        </AppLayout>
    );
}
