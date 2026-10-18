import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import CloudinaryImageUpload from '@/components/cloudinary-image-upload';
import InputError from '@/components/input-error';
import RichTextEditor from '@/components/rich-text-editor';
import TagSuggestions from '@/components/tag-suggestions';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { store, tags as tagsRoute, update } from '@/routes/admin/articles';
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

const FIELD =
    'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50';
const TEXTAREA =
    'w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50';

function slugify(value: string): string {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

function parseTags(text: string): string[] {
    const seen = new Set<string>();
    const result: string[] = [];

    for (const raw of text.split(',')) {
        const tag = raw.trim();

        if (tag && !seen.has(tag.toLowerCase())) {
            seen.add(tag.toLowerCase());
            result.push(tag);
        }
    }

    return result;
}

const pad = (n: number) => String(n).padStart(2, '0');

/** Server sends `Y-m-d\TH:i` in the app timezone (UTC); show it in the browser's local time. */
function toLocalInput(serverValue: string | null): string {
    if (!serverValue) {
        return '';
    }

    const d = new Date(`${serverValue}:00Z`);

    if (Number.isNaN(d.getTime())) {
        return '';
    }

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function Counter({ value, max }: { value: string; max: number }) {
    return (
        <span
            className={cn(
                'text-xs tabular-nums',
                value.length > max
                    ? 'text-destructive'
                    : 'text-muted-foreground',
            )}
        >
            {value.length}/{max}
        </span>
    );
}

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

    const form = useForm({
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

    function onTitleChange(title: string) {
        form.setData((data) => ({
            ...data,
            title,
            slug: !isEdit && !slugEdited ? slugify(title) : data.slug,
        }));
    }

    function handleSubmit(e: FormEvent) {
        e.preventDefault();

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

        if (article) {
            form.submit(update(article.slug), { preserveScroll: true });
        } else {
            form.submit(store());
        }
    }

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
        <form onSubmit={handleSubmit} className="space-y-6">
            <div className="space-y-1.5">
                <Label htmlFor="title">Title *</Label>
                <Input
                    id="title"
                    value={form.data.title}
                    onChange={(e) => onTitleChange(e.target.value)}
                    placeholder="Article title"
                    disabled={form.processing}
                />
                <InputError message={form.errors.title} />
            </div>

            <div className="space-y-1.5">
                <Label htmlFor="slug">
                    Slug
                    <span className="ml-1 text-xs font-normal text-muted-foreground">
                        {isEdit
                            ? '(changing it breaks existing links)'
                            : '(generated from the title if empty)'}
                    </span>
                </Label>
                <div className="flex items-center gap-2">
                    <span className="shrink-0 text-sm text-muted-foreground">
                        /blog/
                    </span>
                    <Input
                        id="slug"
                        value={form.data.slug}
                        onChange={(e) => {
                            setSlugEdited(true);
                            form.setData(
                                'slug',
                                e.target.value
                                    .toLowerCase()
                                    .replace(/[^a-z0-9-]/g, '-'),
                            );
                        }}
                        placeholder="my-article"
                        className="font-mono text-sm"
                        disabled={form.processing}
                    />
                </div>
                <InputError message={form.errors.slug} />
            </div>

            <div className="space-y-1.5">
                <div className="flex items-center justify-between">
                    <Label htmlFor="excerpt">
                        Excerpt
                        <span className="ml-1 text-xs font-normal text-muted-foreground">
                            (shown on cards and listings)
                        </span>
                    </Label>
                    <Counter value={form.data.excerpt} max={500} />
                </div>
                <textarea
                    id="excerpt"
                    rows={3}
                    value={form.data.excerpt}
                    onChange={(e) => form.setData('excerpt', e.target.value)}
                    placeholder="A short summary of the article…"
                    disabled={form.processing}
                    className={TEXTAREA}
                />
                <InputError message={form.errors.excerpt} />
            </div>

            <fieldset className="space-y-4 rounded-lg border p-4">
                <legend className="px-1 text-sm font-medium">SEO</legend>

                <div className="space-y-1.5">
                    <div className="flex items-center justify-between">
                        <Label htmlFor="meta_title">
                            Meta title
                            <span className="ml-1 text-xs font-normal text-muted-foreground">
                                (defaults to the title)
                            </span>
                        </Label>
                        <Counter value={form.data.meta_title} max={70} />
                    </div>
                    <Input
                        id="meta_title"
                        value={form.data.meta_title}
                        onChange={(e) =>
                            form.setData('meta_title', e.target.value)
                        }
                        disabled={form.processing}
                    />
                    <InputError message={form.errors.meta_title} />
                </div>

                <div className="space-y-1.5">
                    <div className="flex items-center justify-between">
                        <Label htmlFor="meta_description">
                            Meta description
                            <span className="ml-1 text-xs font-normal text-muted-foreground">
                                (defaults to the excerpt)
                            </span>
                        </Label>
                        <Counter value={form.data.meta_description} max={200} />
                    </div>
                    <textarea
                        id="meta_description"
                        rows={2}
                        value={form.data.meta_description}
                        onChange={(e) =>
                            form.setData('meta_description', e.target.value)
                        }
                        disabled={form.processing}
                        className={TEXTAREA}
                    />
                    <InputError message={form.errors.meta_description} />
                </div>
            </fieldset>

            <fieldset className="space-y-4 rounded-lg border p-4">
                <legend className="px-1 text-sm font-medium">
                    Featured image
                </legend>

                <div className="space-y-1.5">
                    <CloudinaryImageUpload
                        value={form.data.featured_image}
                        onChange={(url) => form.setData('featured_image', url)}
                        aspectHint="16:9"
                        disabled={form.processing}
                    />
                    <InputError message={form.errors.featured_image} />
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-1.5">
                        <Label htmlFor="featured_image_alt">
                            Alt text
                            <span className="ml-1 text-xs font-normal text-muted-foreground">
                                (accessibility &amp; SEO)
                            </span>
                        </Label>
                        <Input
                            id="featured_image_alt"
                            value={form.data.featured_image_alt}
                            onChange={(e) =>
                                form.setData(
                                    'featured_image_alt',
                                    e.target.value,
                                )
                            }
                            placeholder="Describe the image…"
                            disabled={form.processing}
                        />
                        <InputError message={form.errors.featured_image_alt} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="featured_image_caption">
                            Caption
                            <span className="ml-1 text-xs font-normal text-muted-foreground">
                                (shown under the image)
                            </span>
                        </Label>
                        <Input
                            id="featured_image_caption"
                            value={form.data.featured_image_caption}
                            onChange={(e) =>
                                form.setData(
                                    'featured_image_caption',
                                    e.target.value,
                                )
                            }
                            disabled={form.processing}
                        />
                        <InputError
                            message={form.errors.featured_image_caption}
                        />
                    </div>
                </div>
            </fieldset>

            <div className="space-y-1.5">
                <Label>Tags</Label>
                <TagSuggestions
                    value={form.data.tags}
                    onChange={(v) => form.setData('tags', v)}
                    tagsUrl={tagsRoute.url()}
                />
                <InputError
                    message={
                        form.errors.tags ??
                        Object.entries(form.errors).find(([k]) =>
                            k.startsWith('tags.'),
                        )?.[1]
                    }
                />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-1.5">
                    <Label htmlFor="category_id">Category</Label>
                    <select
                        id="category_id"
                        value={form.data.category_id}
                        onChange={(e) =>
                            form.setData('category_id', e.target.value)
                        }
                        disabled={form.processing}
                        className={FIELD}
                    >
                        <option value="">No category</option>
                        {categories.map((cat) => (
                            <option key={cat.id} value={String(cat.id)}>
                                {cat.name}
                            </option>
                        ))}
                    </select>
                    <InputError message={form.errors.category_id} />
                </div>

                {isAdmin && (
                    <div className="space-y-1.5">
                        <Label htmlFor="author_id">Author</Label>
                        <select
                            id="author_id"
                            value={form.data.author_id}
                            onChange={(e) =>
                                form.setData('author_id', e.target.value)
                            }
                            disabled={form.processing}
                            className={FIELD}
                        >
                            {authors.map((a) => (
                                <option key={a.id} value={String(a.id)}>
                                    {a.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={form.errors.author_id} />
                    </div>
                )}

                <div className="space-y-1.5">
                    <Label htmlFor="status">Status</Label>
                    <select
                        id="status"
                        value={form.data.status}
                        onChange={(e) =>
                            form.setData(
                                'status',
                                e.target.value as ArticleStatus,
                            )
                        }
                        disabled={form.processing}
                        className={FIELD}
                    >
                        {statuses.map((s) => (
                            <option key={s.value} value={s.value}>
                                {s.label}
                            </option>
                        ))}
                    </select>
                    <InputError message={form.errors.status} />
                </div>

                {isScheduled && (
                    <div className="space-y-1.5">
                        <Label htmlFor="publish_at">
                            Publish at *
                            <span className="ml-1 text-xs font-normal text-muted-foreground">
                                (your local time)
                            </span>
                        </Label>
                        <Input
                            id="publish_at"
                            type="datetime-local"
                            value={form.data.publish_at}
                            onChange={(e) =>
                                form.setData('publish_at', e.target.value)
                            }
                            disabled={form.processing}
                        />
                        <InputError message={form.errors.publish_at} />
                    </div>
                )}
            </div>

            <div className="space-y-1.5">
                <Label>Content *</Label>
                <RichTextEditor
                    value={form.data.body}
                    onChange={(v) => form.setData('body', v)}
                    placeholder="Start writing…"
                    disabled={form.processing}
                />
                <InputError message={form.errors.body} />
            </div>

            <div className="flex flex-wrap items-center gap-3 pt-2">
                <Button type="submit" disabled={form.processing}>
                    {submitLabel}
                </Button>
                {article && (
                    <>
                        <Button type="button" variant="secondary" asChild>
                            <a
                                href={article.preview_url}
                                target="_blank"
                                rel="noopener"
                            >
                                Preview saved version
                            </a>
                        </Button>
                        {article.public_url && (
                            <Button type="button" variant="outline" asChild>
                                <a
                                    href={article.public_url}
                                    target="_blank"
                                    rel="noopener"
                                >
                                    View live
                                </a>
                            </Button>
                        )}
                    </>
                )}
                <Button
                    type="button"
                    variant="ghost"
                    onClick={onCancel}
                    disabled={form.processing}
                >
                    Cancel
                </Button>
            </div>
        </form>
    );
}
