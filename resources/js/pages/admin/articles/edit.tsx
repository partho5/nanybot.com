import { Head, router } from '@inertiajs/react';
import ArticleForm from '@/components/article-form';
import FlashMessages from '@/components/flash-messages';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { destroy, edit, index } from '@/routes/admin/articles';
import type { BreadcrumbItem } from '@/types';
import type {
    ArticleDetail,
    AuthorOption,
    CategoryOption,
    StatusOption,
} from '@/types/article';

type Props = {
    article: ArticleDetail;
    categories: CategoryOption[];
    authors: AuthorOption[];
    statuses: StatusOption[];
    isAdmin: boolean;
    defaultAuthorId: number;
};

export default function ArticleEdit({
    article,
    categories,
    authors,
    statuses,
    isAdmin,
    defaultAuthorId,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Articles', href: index() },
        { title: article.title, href: edit(article.slug) },
    ];

    function handleDelete() {
        if (
            !window.confirm(`Delete "${article.title}"? This cannot be undone.`)
        ) {
            return;
        }

        router.delete(destroy.url(article.slug));
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit: ${article.title}`} />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4 md:p-6">
                <FlashMessages />
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Edit article"
                        description={`Last updated ${new Date(article.updated_at).toLocaleString()}`}
                    />
                    <Button
                        type="button"
                        variant="destructive"
                        size="sm"
                        onClick={handleDelete}
                    >
                        Delete
                    </Button>
                </div>
                <ArticleForm
                    key={article.id}
                    article={article}
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
