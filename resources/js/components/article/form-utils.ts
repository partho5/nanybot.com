import type { InertiaFormProps } from '@inertiajs/react';
import type { ArticleStatus } from '@/types/article';

/** Shape of the editor form state (server-facing transforms happen on submit). */
export type ArticleFormData = {
    title: string;
    slug: string;
    excerpt: string;
    meta_title: string;
    meta_description: string;
    body: string;
    featured_image: string;
    featured_image_alt: string;
    featured_image_caption: string;
    tags: string;
    category_id: string;
    author_id: string;
    status: ArticleStatus;
    publish_at: string;
};

export type ArticleFormState = InertiaFormProps<ArticleFormData>;

/** Shared look for native text areas inside the editor sidebar. */
export const TEXTAREA =
    'w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50 md:text-sm';

export function slugify(value: string): string {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

export function parseTags(text: string): string[] {
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
export function toLocalInput(serverValue: string | null): string {
    if (!serverValue) {
        return '';
    }

    const d = new Date(`${serverValue}:00Z`);

    if (Number.isNaN(d.getTime())) {
        return '';
    }

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

/** "just now", "5m ago", "3h ago", "2d ago", then a short date. */
export function timeAgo(iso: string): string {
    const time = new Date(iso).getTime();

    if (Number.isNaN(time)) {
        return '';
    }

    const seconds = Math.max(0, Math.round((Date.now() - time) / 1000));

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

    return new Date(time).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

const SITE_DOMAIN = 'nanybot.com';

/** Plain text of an HTML string (DOMParser never runs scripts or loads images). */
function htmlToText(html: string): string {
    const doc = new DOMParser().parseFromString(html, 'text/html');

    return (doc.body.textContent ?? '').replace(/\s+/g, ' ').trim();
}

/** Mirrors Str::limit($v, $max, '…'): cut at $max, trim the end, add an ellipsis. */
function limit(value: string, max: number): string {
    return value.length <= max ? value : `${value.slice(0, max).trimEnd()}…`;
}

/** Mirrors ContentProcessor's auto-excerpt: first paragraph of 40+ characters, capped at 200. */
function firstParagraph(body: string): string {
    const doc = new DOMParser().parseFromString(body, 'text/html');

    for (const p of Array.from(doc.querySelectorAll('p'))) {
        const text = (p.textContent ?? '').replace(/\s+/g, ' ').trim();

        if (text.length >= 40) {
            return limit(text, 200);
        }
    }

    return '';
}

export type SeoPreviewData = {
    title: string;
    /** Raw description before the 160-character cut, used for the length warnings. */
    rawDescription: string;
    description: string;
    url: string;
    breadcrumb: string;
    image: string;
    imageAlt: string;
    domain: string;
};

/** What search engines and social cards will show, using the server's fallbacks (Article::metaTitle / metaDescription). */
export function seoPreview(data: ArticleFormData): SeoPreviewData {
    const slug = data.slug || slugify(data.title);
    const rawDescription = (
        data.meta_description.trim() ||
        data.excerpt.trim() ||
        firstParagraph(data.body) ||
        htmlToText(data.body)
    ).replace(/\s+/g, ' ');

    return {
        title: data.meta_title.trim() || data.title.trim(),
        rawDescription,
        description: limit(rawDescription, 160),
        url: `${SITE_DOMAIN}/blog/${slug}`,
        breadcrumb: `${SITE_DOMAIN} › blog${slug ? ` › ${slug}` : ''}`,
        image: data.featured_image.trim(),
        imageAlt: data.featured_image_alt.trim() || data.title.trim(),
        domain: SITE_DOMAIN,
    };
}

/** Submits the current form to the unsaved-preview route in a new tab (nothing is saved). */
export function openUnsavedPreview(
    action: string,
    data: ArticleFormData,
    articleId?: number,
): void {
    const token =
        document
            .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? '';

    const fields: [string, string][] = [['_token', token]];

    if (articleId) {
        fields.push(['article_id', String(articleId)]);
    }

    const { tags, ...rest } = data;

    for (const [key, value] of Object.entries(rest)) {
        // publish_at is a local datetime-local value; send it as an ISO instant like a real save.
        fields.push([
            key,
            key === 'publish_at' && value
                ? new Date(value).toISOString()
                : String(value ?? ''),
        ]);
    }

    for (const tag of parseTags(tags)) {
        fields.push(['tags[]', tag]);
    }

    const form = document.createElement('form');
    form.method = 'post';
    form.action = action;
    form.target = '_blank';
    form.rel = 'noopener';
    form.hidden = true;

    for (const [name, value] of fields) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
    form.remove();
}
