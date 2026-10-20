import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import {
    Pencil,
    Plus,
    Search,
    SearchX,
    Trash2,
    Users as UsersIcon,
} from 'lucide-react';
import { useState } from 'react';
import EmptyState, { focusField } from '@/components/admin/empty-state';
import FlashMessages from '@/components/admin/flash-messages';
import NativeSelect from '@/components/admin/native-select';
import PaginationNav from '@/components/admin/pagination-nav';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import { destroy, edit, index, store } from '@/routes/admin/users';
import type { BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/article';

type UserRow = {
    id: number;
    name: string;
    username: string;
    email: string;
    role: string;
    avatar: string | null;
    headline: string | null;
    articles_count: number;
    created_at: string;
};

type Props = {
    users: Paginated<UserRow>;
    filters: { q: string };
    roles: { value: string; label: string }[];
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Authors', href: index() }];

export default function Users({ users, filters, roles }: Props) {
    const { auth } = usePage().props;
    const getInitials = useInitials();
    const [q, setQ] = useState(filters.q ?? '');

    function search(e: React.FormEvent) {
        e.preventDefault();
        router.get(index.url(), q.trim() ? { q: q.trim() } : {}, {
            preserveState: true,
            replace: true,
        });
    }

    function clearSearch() {
        setQ('');
        router.get(index.url());
    }

    function remove(user: UserRow) {
        if (
            window.confirm(
                `Delete ${user.name} (${user.email})? This cannot be undone.`,
            )
        ) {
            router.delete(destroy.url(user.id), { preserveScroll: true });
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Authors" />

            <div className="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
                <Heading
                    title="Authors"
                    description="Everyone who can sign in and write. Authors manage their own articles; admins manage everything."
                />
                <FlashMessages />

                <div className="rounded-xl border bg-card p-4">
                    <h2 className="mb-3 text-sm font-semibold">
                        Add an author
                    </h2>
                    <Form
                        {...store.form()}
                        options={{ preserveScroll: true }}
                        resetOnSuccess
                        className="space-y-3"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="new-name">Name</Label>
                                        <Input
                                            id="new-name"
                                            name="name"
                                            required
                                            autoComplete="off"
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="new-email">Email</Label>
                                        <Input
                                            id="new-email"
                                            name="email"
                                            type="email"
                                            required
                                            autoComplete="off"
                                        />
                                        <InputError message={errors.email} />
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="new-username">
                                            Username{' '}
                                            <span className="font-normal text-muted-foreground">
                                                (optional)
                                            </span>
                                        </Label>
                                        <Input
                                            id="new-username"
                                            name="username"
                                            placeholder="auto-generated"
                                            autoComplete="off"
                                        />
                                        <InputError message={errors.username} />
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="new-role">Role</Label>
                                        <NativeSelect
                                            id="new-role"
                                            name="role"
                                            defaultValue="author"
                                        >
                                            {roles.map((r) => (
                                                <option
                                                    key={r.value}
                                                    value={r.value}
                                                >
                                                    {r.label}
                                                </option>
                                            ))}
                                        </NativeSelect>
                                        <InputError message={errors.role} />
                                    </div>
                                    <div className="grid gap-1.5 sm:col-span-2">
                                        <Label htmlFor="new-password">
                                            Password
                                        </Label>
                                        <PasswordInput
                                            id="new-password"
                                            name="password"
                                            required
                                            autoComplete="new-password"
                                        />
                                        <InputError message={errors.password} />
                                    </div>
                                </div>
                                <Button disabled={processing}>
                                    <Plus /> Add author
                                </Button>
                            </>
                        )}
                    </Form>
                </div>

                <form onSubmit={search} className="flex gap-2" role="search">
                    <Input
                        type="search"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Search name, email or username…"
                        aria-label="Search authors"
                    />
                    <Button type="submit" variant="secondary">
                        <Search /> Search
                    </Button>
                    {filters.q && (
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={clearSearch}
                        >
                            Clear
                        </Button>
                    )}
                </form>

                {users.data.length === 0 ? (
                    filters.q ? (
                        <EmptyState
                            icon={SearchX}
                            message={`No authors match "${filters.q}".`}
                            action={
                                <Button variant="outline" onClick={clearSearch}>
                                    Clear search
                                </Button>
                            }
                        />
                    ) : (
                        <EmptyState
                            icon={UsersIcon}
                            message="No authors yet. Invite someone to write."
                            action={
                                <Button onClick={() => focusField('new-name')}>
                                    <Plus /> Add an author
                                </Button>
                            }
                        />
                    )
                ) : (
                    <ul className="divide-y rounded-xl border bg-card">
                        {users.data.map((user) => (
                            <li
                                key={user.id}
                                className="flex items-center gap-3 p-4"
                            >
                                <Avatar className="size-9">
                                    <AvatarImage
                                        src={user.avatar ?? undefined}
                                        alt=""
                                    />
                                    <AvatarFallback>
                                        {getInitials(user.name)}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="min-w-0 flex-1">
                                    <p className="flex items-center gap-2 font-medium">
                                        <span className="truncate">
                                            {user.name}
                                        </span>
                                        <Badge
                                            variant={
                                                user.role === 'admin'
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {user.role}
                                        </Badge>
                                        {user.id === auth.user.id && (
                                            <Badge variant="outline">you</Badge>
                                        )}
                                    </p>
                                    <p className="truncate text-xs text-muted-foreground">
                                        @{user.username} · {user.email}
                                    </p>
                                </div>
                                <span className="hidden text-sm whitespace-nowrap text-muted-foreground sm:block">
                                    {user.articles_count} article
                                    {user.articles_count === 1 ? '' : 's'}
                                </span>
                                <div className="flex gap-1">
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        asChild
                                        aria-label={`Edit ${user.name}`}
                                    >
                                        <Link href={edit(user.id)}>
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    {user.id !== auth.user.id && (
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            onClick={() => remove(user)}
                                            aria-label={`Delete ${user.name}`}
                                            className="text-destructive hover:text-destructive"
                                        >
                                            <Trash2 />
                                        </Button>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

                <PaginationNav page={users} noun="author" />
            </div>
        </AppLayout>
    );
}
