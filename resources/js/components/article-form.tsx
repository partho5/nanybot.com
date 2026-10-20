import { router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import ExcerptSection from '@/components/article/excerpt-section';
import FeaturedImageSection from '@/components/article/featured-image-section';
import {
    openUnsavedPreview,
    parseTags,
    slugify,
    toLocalInput,
} from '@/components/article/form-utils';
import type { ArticleFormData } from '@/components/article/form-utils';
import OrganizeSection from '@/components/article/organize-section';
import PublishSection from '@/components/article/publish-section';
import SeoSection from '@/components/article/seo-section';
import SlugField from '@/components/article/slug-field';
import TitleField from '@/components/article/title-field';
import UnsavedIndicator from '@/components/article/unsaved-indicator';
import InputError from '@/components/input-error';
import RichTextEditor from '@/components/rich-text-editor';
import { Button } from '@/components/ui/button';
import { store, update } from '@/routes/admin/articles';
import { unsaved } from '@/routes/admin/articles/preview';
import type {
    ArticleDetail,
    ArticleStatus,
    AuthorOption,
    CategoryOption,
    StatusOption,
} from '@/types/article';

type Props = {
    article?: ArticleDetail;
    categories: CategoryOption[];
    authors: AuthorOption[];
    statuses: StatusOption[];
    isAdmin: boolean;
    defaultAuthorId: number;
    onCancel: () => void;
};

const LEAVE_MESSAGE = 'You have unsaved changes. Leave this page anyway?';

export default function ArticleForm({
    article,
    categories,
    authors,
    statuses,
    isAdmin,
    defaultAuthorId,
    onCancel,
}: Props) {
    const isEdit = Boolean(article);

    const form = useForm<ArticleFormData>({
        title: article?.title ?? '',
        slug: article?.slug ?? '',
        excerpt: article?.excerpt ?? '',
        meta_title: article?.meta_title ?? '',
        meta_description: article?.meta_description ?? '',
        body: article?.body ?? '',
        featured_image: article?.featured_image ?? '',
        featured_image_alt: article?.featured_image_alt ?? '',
        featured_image_caption: article?.featured_image_caption ?? '',
        tags: (article?.tags ?? []).join(', '),
        category_id: article?.category_id ? String(article.category_id) : '',
        author_id: String(article?.author_id ?? defaultAuthorId),
        status: (article?.status ?? 'draft') as ArticleStatus,
        publish_at: toLocalInput(article?.publish_at ?? null),
    });

    // On create the slug follows the title until the author edits it by hand.
    const [slugEdited, setSlugEdited] = useState(false);

    const isScheduled = form.data.status === 'scheduled';
    const isDraft = form.data.status === 'draft';

    // True while our own save request is in flight, so it never triggers the leave guard.
    const submittingRef = useRef(false);
    const dirtyRef = useRef(false);

    useEffect(() => {
        dirtyRef.current = form.isDirty;
    }, [form.isDirty]);

    function onTitleChange(title: string) {
        form.setData((data) => ({
            ...data,
            title,
            slug: !isEdit && !slugEdited ? slugify(title) : data.slug,
        }));
    }

    function handleSubmit(e?: FormEvent) {
        e?.preventDefault();

        if (form.processing) {
            return;
        }

        form.transform((data) => ({
            ...data,
            tags: parseTags(data.tags),
            slug: data.slug || null,
            category_id: data.category_id || null,
            author_id: isAdmin ? data.author_id : undefined,
            publish_at:
                data.status === 'scheduled' && data.publish_at
                    ? new Date(data.publish_at).toISOString()
                    : null,
        }));

        submittingRef.current = true;
        const options = {
            onFinish: () => {
                submittingRef.current = false;
            },
        };

        if (article) {
            form.submit(update(article.slug), {
                ...options,
                preserveScroll: true,
            });
        } else {
            form.submit(store(), options);
        }
    }

    // Ctrl/Cmd+S saves with the current status instead of the browser's "Save page".
    const submitRef = useRef(handleSubmit);

    useEffect(() => {
        submitRef.current = handleSubmit;
    });

    useEffect(() => {
        function onKeyDown(e: KeyboardEvent) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                e.preventDefault();
                submitRef.current();
            }
        }

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, []);

    // Unsaved-changes guards: in-app (Inertia) navigation and tab close / reload.
    useEffect(() => {
        const removeListener = router.on('before', () => {
            if (!dirtyRef.current || submittingRef.current) {
                return;
            }

            return window.confirm(LEAVE_MESSAGE);
        });

        function onBeforeUnload(e: BeforeUnloadEvent) {
            if (dirtyRef.current && !submittingRef.current) {
                e.preventDefault();
            }
        }

        window.addEventListener('beforeunload', onBeforeUnload);

        return () => {
            removeListener();
            window.removeEventListener('beforeunload', onBeforeUnload);
        };
    }, []);

    const submitLabel = form.processing
        ? 'Saving…'
        : isDraft
          ? 'Save draft'
          : isScheduled
            ? 'Schedule'
            : isEdit && article?.is_published
              ? 'Update article'
              : 'Publish article';

    return (
        <form onSubmit={handleSubmit}>
            <div className="lg:flex lg:items-start lg:gap-8">
                <div className="min-w-0 flex-1 space-y-4 lg:max-w-[760px]">
                    <TitleField
                        value={form.data.title}
                        onChange={onTitleChange}
                        error={form.errors.title}
                        disabled={form.processing}
                    />

                    <SlugField
                        value={form.data.slug}
                        onChange={(slug) => {
                            setSlugEdited(true);
                            form.setData('slug', slug);
                        }}
                        isEdit={isEdit}
                        error={form.errors.slug}
                        disabled={form.processing}
                    />

                    <div className="space-y-1.5 pt-2">
                        <RichTextEditor
                            tall
                            value={form.data.body}
                            onChange={(v) => form.setData('body', v)}
                            placeholder="Start writing…"
                            disabled={form.processing}
                        />
                        <InputError message={form.errors.body} />
                    </div>
                </div>

                <aside
                    aria-label="Article settings"
                    className="mt-8 space-y-4 lg:sticky lg:top-4 lg:-m-1 lg:mt-0 lg:max-h-[calc(100svh-2rem)] lg:w-80 lg:shrink-0 lg:overflow-y-auto lg:p-1"
                >
                    <PublishSection
                        form={form}
                        article={article}
                        statuses={statuses}
                        submitLabel={submitLabel}
                        onCancel={onCancel}
                        onPreview={() =>
                            openUnsavedPreview(
                                unsaved.url(),
                                form.data,
                                article?.id,
                            )
                        }
                    />
                    <FeaturedImageSection form={form} />
                    <OrganizeSection
                        form={form}
                        categories={categories}
                        authors={authors}
                        isAdmin={isAdmin}
                    />
                    <ExcerptSection form={form} />
                    <SeoSection form={form} />
                </aside>
            </div>

            {/* Below lg the sidebar sits after the editor, so keep Save in reach. */}
            <div className="sticky bottom-0 z-20 -mx-4 mt-6 flex items-center justify-between gap-3 border-t bg-background/95 px-4 py-3 backdrop-blur md:-mx-6 md:px-6 lg:hidden">
                {form.isDirty ? <UnsavedIndicator /> : <span />}
                <Button type="submit" disabled={form.processing}>
                    {submitLabel}
                </Button>
            </div>
        </form>
    );
}
