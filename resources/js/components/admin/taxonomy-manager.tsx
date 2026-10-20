import { Form, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import EmptyState, { focusField } from '@/components/admin/empty-state';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type TaxonomyRow = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    articles_count: number;
    url: string;
};

type FormProps = {
    action: string;
    method: 'get' | 'post' | 'put' | 'patch' | 'delete';
};

type Props = {
    /** Singular lowercase noun, e.g. "category". */
    noun: string;
    rows: TaxonomyRow[];
    storeForm: FormProps;
    updateForm: (slug: string) => FormProps;
    destroyUrl: (slug: string) => string;
    /** Admin article list filtered to this row. */
    articlesUrl: (row: TaxonomyRow) => string;
    /** Empty state: icon, one line, and an action (defaults to focusing the add form). */
    emptyIcon: LucideIcon;
    emptyMessage: string;
    emptyAction?: ReactNode;
};

function Fields({
    noun,
    row,
    errors,
}: {
    noun: string;
    row?: TaxonomyRow;
    errors: Record<string, string>;
}) {
    const key = row?.id ?? 'new';

    return (
        <div className="grid flex-1 gap-3 sm:grid-cols-3">
            <div className="grid gap-1.5">
                <Label htmlFor={`name-${key}`}>Name</Label>
                <Input
                    id={`name-${key}`}
                    name="name"
                    defaultValue={row?.name}
                    required
                    placeholder={`${noun[0].toUpperCase()}${noun.slice(1)} name`}
                />
                <InputError message={errors.name} />
            </div>
            <div className="grid gap-1.5">
                <Label htmlFor={`slug-${key}`}>
                    Slug{' '}
                    <span className="font-normal text-muted-foreground">
                        (optional)
                    </span>
                </Label>
                <Input
                    id={`slug-${key}`}
                    name="slug"
                    defaultValue={row?.slug}
                    placeholder="auto-generated"
                />
                <InputError message={errors.slug} />
            </div>
            <div className="grid gap-1.5">
                <Label htmlFor={`description-${key}`}>
                    Description{' '}
                    <span className="font-normal text-muted-foreground">
                        (optional)
                    </span>
                </Label>
                <Input
                    id={`description-${key}`}
                    name="description"
                    defaultValue={row?.description ?? ''}
                    placeholder="Shown on the public page"
                />
                <InputError message={errors.description} />
            </div>
        </div>
    );
}

function TaxonomyItem({
    noun,
    row,
    updateForm,
    destroyUrl,
    articlesUrl,
}: {
    noun: string;
    row: TaxonomyRow;
    updateForm: Props['updateForm'];
    destroyUrl: Props['destroyUrl'];
    articlesUrl: Props['articlesUrl'];
}) {
    const [editing, setEditing] = useState(false);

    function remove() {
        const detail =
            row.articles_count > 0
                ? ` It is used by ${row.articles_count} article${row.articles_count === 1 ? '' : 's'}; they will be kept.`
                : '';

        if (window.confirm(`Delete ${noun} "${row.name}"?${detail}`)) {
            router.delete(destroyUrl(row.slug), { preserveScroll: true });
        }
    }

    if (editing) {
        return (
            <li className="p-4">
                <Form
                    {...updateForm(row.slug)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setEditing(false)}
                    className="flex flex-col gap-3 sm:flex-row sm:items-start"
                >
                    {({ processing, errors }) => (
                        <>
                            <Fields noun={noun} row={row} errors={errors} />
                            <div className="flex gap-2 sm:mt-6">
                                <Button size="sm" disabled={processing}>
                                    Save
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onClick={() => setEditing(false)}
                                >
                                    Cancel
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </li>
        );
    }

    return (
        <li className="flex items-center gap-4 p-4">
            <div className="min-w-0 flex-1">
                <p className="truncate font-medium">
                    <Link href={articlesUrl(row)} className="hover:underline">
                        {row.name}
                    </Link>
                </p>
                <p className="truncate text-xs text-muted-foreground">
                    <a
                        href={row.url}
                        target="_blank"
                        rel="noreferrer"
                        className="hover:text-foreground hover:underline"
                    >
                        /{row.slug}
                    </a>
                    {row.description ? ` · ${row.description}` : ''}
                </p>
            </div>
            <Link
                href={articlesUrl(row)}
                className="text-sm whitespace-nowrap text-muted-foreground hover:text-foreground hover:underline"
            >
                {row.articles_count} article
                {row.articles_count === 1 ? '' : 's'}
            </Link>
            <div className="flex gap-1">
                <Button
                    size="icon"
                    variant="ghost"
                    onClick={() => setEditing(true)}
                    aria-label={`Edit ${row.name}`}
                >
                    <Pencil />
                </Button>
                <Button
                    size="icon"
                    variant="ghost"
                    onClick={remove}
                    aria-label={`Delete ${row.name}`}
                    className="text-destructive hover:text-destructive"
                >
                    <Trash2 />
                </Button>
            </div>
        </li>
    );
}

export default function TaxonomyManager({
    noun,
    rows,
    storeForm,
    updateForm,
    destroyUrl,
    articlesUrl,
    emptyIcon,
    emptyMessage,
    emptyAction,
}: Props) {
    return (
        <div className="space-y-6">
            <div className="rounded-xl border bg-card p-4">
                <h2 className="mb-3 text-sm font-semibold">Add a {noun}</h2>
                <Form
                    {...storeForm}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    className="flex flex-col gap-3 sm:flex-row sm:items-start"
                >
                    {({ processing, errors }) => (
                        <>
                            <Fields noun={noun} errors={errors} />
                            <Button disabled={processing} className="sm:mt-6">
                                <Plus /> Add
                            </Button>
                        </>
                    )}
                </Form>
            </div>

            {rows.length === 0 ? (
                <EmptyState
                    icon={emptyIcon}
                    message={emptyMessage}
                    action={
                        emptyAction ?? (
                            <Button onClick={() => focusField('name-new')}>
                                <Plus /> Add a {noun}
                            </Button>
                        )
                    }
                />
            ) : (
                <ul className="divide-y rounded-xl border bg-card">
                    {rows.map((row) => (
                        <TaxonomyItem
                            key={row.id}
                            noun={noun}
                            row={row}
                            updateForm={updateForm}
                            destroyUrl={destroyUrl}
                            articlesUrl={articlesUrl}
                        />
                    ))}
                </ul>
            )}
        </div>
    );
}
