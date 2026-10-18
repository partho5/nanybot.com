import { Head, useForm } from '@inertiajs/react';
import { ExternalLink, Plus, X } from 'lucide-react';
import { useState } from 'react';
import FlashMessages from '@/components/admin/flash-messages';
import NativeSelect from '@/components/admin/native-select';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { edit, index, update } from '@/routes/admin/users';
import type { BreadcrumbItem } from '@/types';

type Props = {
    user: {
        id: number;
        name: string;
        username: string;
        email: string;
        role: string;
        avatar: string | null;
        headline: string | null;
        bio: string | null;
        social_links: Record<string, string>;
        author_url: string;
    };
    roles: { value: string; label: string }[];
    isAdmin: boolean;
    isSelf: boolean;
};

type LinkRow = { label: string; url: string };

export default function EditUser({ user, roles, isAdmin, isSelf }: Props) {
    const [links, setLinks] = useState<LinkRow[]>(
        Object.entries(user.social_links ?? {}).map(([label, url]) => ({
            label,
            url,
        })),
    );

    const form = useForm({
        name: user.name,
        email: user.email,
        username: user.username ?? '',
        avatar: user.avatar ?? '',
        headline: user.headline ?? '',
        bio: user.bio ?? '',
        role: user.role,
        password: '',
    });

    const breadcrumbs: BreadcrumbItem[] = isAdmin
        ? [
              { title: 'Authors', href: index() },
              { title: user.name, href: edit(user.id) },
          ]
        : [{ title: 'My profile', href: edit(user.id) }];

    function setLink(i: number, patch: Partial<LinkRow>) {
        setLinks((rows) =>
            rows.map((r, idx) => (idx === i ? { ...r, ...patch } : r)),
        );
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();

        form.transform((data) => {
            const payload: Record<string, unknown> = {
                ...data,
                social_links: Object.fromEntries(
                    links
                        .filter((l) => l.label.trim() && l.url.trim())
                        .map((l) => [l.label.trim(), l.url.trim()]),
                ),
            };

            if (!isAdmin) {
                delete payload.role;
                delete payload.password;
            }

            return payload as typeof data;
        });

        form.put(update.url(user.id), {
            preserveScroll: true,
            onSuccess: () => form.reset('password'),
        });
    }

    // Errors on dynamic links come back as social_links.<label>.
    const linkErrors = Object.entries(form.errors)
        .filter(([key]) => key.startsWith('social_links'))
        .map(([, message]) => message);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isSelf ? 'My profile' : `Edit ${user.name}`} />

            <div className="mx-auto w-full max-w-2xl space-y-6 p-4 md:p-6">
                <Heading
                    title={isSelf ? 'My profile' : `Edit ${user.name}`}
                    description="This information appears on your public author page and under your articles."
                />
                <FlashMessages />

                <a
                    href={user.author_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-1.5 text-sm text-primary underline-offset-4 hover:underline"
                >
                    <ExternalLink className="size-4" />
                    View public author page
                </a>

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-1.5">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.name} />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="username">Username</Label>
                            <Input
                                id="username"
                                value={form.data.username}
                                onChange={(e) =>
                                    form.setData('username', e.target.value)
                                }
                                placeholder="lowercase-letters-and-dashes"
                            />
                            <InputError message={form.errors.username} />
                        </div>
                        <div className="grid gap-1.5 sm:col-span-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData('email', e.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.email} />
                        </div>
                    </div>

                    <div className="grid gap-4">
                        <div className="grid gap-1.5">
                            <Label htmlFor="headline">Headline</Label>
                            <Input
                                id="headline"
                                value={form.data.headline}
                                onChange={(e) =>
                                    form.setData('headline', e.target.value)
                                }
                                maxLength={160}
                                placeholder="One line about you"
                            />
                            <InputError message={form.errors.headline} />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="bio">Bio</Label>
                            <textarea
                                id="bio"
                                value={form.data.bio}
                                onChange={(e) =>
                                    form.setData('bio', e.target.value)
                                }
                                rows={5}
                                maxLength={2000}
                                className="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                            />
                            <InputError message={form.errors.bio} />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="avatar">Avatar URL</Label>
                            <div className="flex items-center gap-3">
                                {form.data.avatar && (
                                    <img
                                        src={form.data.avatar}
                                        alt=""
                                        className="size-10 shrink-0 rounded-full object-cover"
                                    />
                                )}
                                <Input
                                    id="avatar"
                                    type="url"
                                    value={form.data.avatar}
                                    onChange={(e) =>
                                        form.setData('avatar', e.target.value)
                                    }
                                    placeholder="https://…"
                                />
                            </div>
                            <InputError message={form.errors.avatar} />
                        </div>
                    </div>

                    <fieldset className="space-y-2">
                        <legend className="text-sm font-medium">
                            Social links
                        </legend>
                        {links.map((row, i) => (
                            <div key={i} className="flex gap-2">
                                <Input
                                    value={row.label}
                                    onChange={(e) =>
                                        setLink(i, { label: e.target.value })
                                    }
                                    placeholder="Label (e.g. GitHub)"
                                    aria-label="Link label"
                                    className="w-1/3"
                                />
                                <Input
                                    type="url"
                                    value={row.url}
                                    onChange={(e) =>
                                        setLink(i, { url: e.target.value })
                                    }
                                    placeholder="https://…"
                                    aria-label="Link URL"
                                />
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    aria-label="Remove link"
                                    onClick={() =>
                                        setLinks((rows) =>
                                            rows.filter((_, idx) => idx !== i),
                                        )
                                    }
                                >
                                    <X />
                                </Button>
                            </div>
                        ))}
                        {linkErrors.map((m, i) => (
                            <InputError key={i} message={m} />
                        ))}
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                setLinks((rows) => [
                                    ...rows,
                                    { label: '', url: '' },
                                ])
                            }
                        >
                            <Plus /> Add link
                        </Button>
                    </fieldset>

                    {isAdmin && (
                        <div className="grid gap-4 rounded-xl border bg-card p-4 sm:grid-cols-2">
                            <div className="grid gap-1.5">
                                <Label htmlFor="role">Role</Label>
                                <NativeSelect
                                    id="role"
                                    value={form.data.role}
                                    onChange={(e) =>
                                        form.setData('role', e.target.value)
                                    }
                                >
                                    {roles.map((r) => (
                                        <option key={r.value} value={r.value}>
                                            {r.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                                <InputError message={form.errors.role} />
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="password">
                                    New password{' '}
                                    <span className="font-normal text-muted-foreground">
                                        (leave blank to keep)
                                    </span>
                                </Label>
                                <PasswordInput
                                    id="password"
                                    value={form.data.password}
                                    onChange={(e) =>
                                        form.setData('password', e.target.value)
                                    }
                                    autoComplete="new-password"
                                />
                                <InputError message={form.errors.password} />
                            </div>
                        </div>
                    )}

                    <Button disabled={form.processing}>Save profile</Button>
                </form>
            </div>
        </AppLayout>
    );
}
