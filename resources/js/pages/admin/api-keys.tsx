import { Form, Head, router, usePage } from '@inertiajs/react';
import { Check, Copy, KeyRound, Plus, TriangleAlert } from 'lucide-react';
import FlashMessages from '@/components/admin/flash-messages';
import NativeSelect from '@/components/admin/native-select';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useClipboard } from '@/hooks/use-clipboard';
import AppLayout from '@/layouts/app-layout';
import { destroy, index, store } from '@/routes/admin/api-keys';
import type { BreadcrumbItem } from '@/types';

type ApiKeyRow = {
    id: number;
    name: string;
    prefix: string;
    user: { id: number; name: string } | null;
    last_used_at: string | null;
    revoked_at: string | null;
    created_at: string;
};

type Props = {
    apiKeys: ApiKeyRow[];
    users: { id: number; name: string }[];
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'API Keys', href: index() }];

const exampleRequest = `curl -X POST https://YOUR-DOMAIN/api/v1/articles \\
  -H "Authorization: Bearer <your-key>" \\
  -H "Content-Type: application/json" \\
  -d '{
    "title": "Hello from the webhook",
    "excerpt": "A short summary.",
    "body": "<p>Article HTML, same format as the editor.</p>",
    "category": "news",
    "tags": ["laravel", "automation"],
    "status": "draft",
    "external_id": "my-system-123"
  }'`;

function formatDate(value: string | null) {
    return value ? new Date(value).toLocaleString() : null;
}

function NewKeyBox({ apiKey }: { apiKey: { name: string; key: string } }) {
    const [copied, copy] = useClipboard();
    const isCopied = copied === apiKey.key;

    return (
        <div className="space-y-3 rounded-xl border-2 border-amber-400 bg-amber-50 p-4 dark:border-amber-500/60 dark:bg-amber-950/30">
            <div className="flex items-start gap-2 text-amber-900 dark:text-amber-200">
                <TriangleAlert className="mt-0.5 size-4 shrink-0" />
                <p className="text-sm">
                    <strong>Key &ldquo;{apiKey.name}&rdquo; created.</strong>{' '}
                    Copy it now, it won&apos;t be shown again.
                </p>
            </div>
            <div className="flex gap-2">
                <code
                    className="min-w-0 flex-1 rounded-md border bg-background px-3 py-2 font-mono text-sm break-all select-all"
                    data-test="new-api-key"
                >
                    {apiKey.key}
                </code>
                <Button type="button" onClick={() => copy(apiKey.key)}>
                    {isCopied ? <Check /> : <Copy />}
                    {isCopied ? 'Copied' : 'Copy'}
                </Button>
            </div>
        </div>
    );
}

export default function ApiKeys({ apiKeys, users }: Props) {
    const { flash, props } = usePage();
    const auth = props.auth;
    const newKey = flash?.api_key;
    function revoke(key: ApiKeyRow) {
        if (
            window.confirm(
                `Revoke "${key.name}"? Anything using this key will stop working immediately.`,
            )
        ) {
            router.delete(destroy.url(key.id), { preserveScroll: true });
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="API Keys" />

            <div className="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
                <Heading
                    title="API Keys"
                    description="Keys let external tools publish articles through the webhook. Articles are attributed to the key's owner."
                />

                {newKey && <NewKeyBox apiKey={newKey} />}
                <FlashMessages />

                <div className="rounded-xl border bg-card p-4">
                    <h2 className="mb-3 text-sm font-semibold">Create a key</h2>
                    <Form
                        {...store.form()}
                        options={{ preserveScroll: true }}
                        resetOnSuccess
                        className="flex flex-col gap-3 sm:flex-row sm:items-start"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid flex-1 gap-1.5">
                                    <Label htmlFor="key-name">Name</Label>
                                    <Input
                                        id="key-name"
                                        name="name"
                                        required
                                        placeholder="e.g. Zapier, n8n, CMS sync"
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="grid gap-1.5 sm:w-56">
                                    <Label htmlFor="key-owner">Owner</Label>
                                    <NativeSelect
                                        id="key-owner"
                                        name="user_id"
                                        defaultValue={auth.user.id}
                                    >
                                        {users.map((u) => (
                                            <option key={u.id} value={u.id}>
                                                {u.name}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError message={errors.user_id} />
                                </div>
                                <Button
                                    disabled={processing}
                                    className="sm:mt-6"
                                >
                                    <Plus /> Create
                                </Button>
                            </>
                        )}
                    </Form>
                </div>

                {apiKeys.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground">
                        <KeyRound className="mx-auto mb-2 size-5" />
                        No API keys yet. Create one above to start using the
                        webhook.
                    </div>
                ) : (
                    <ul className="divide-y rounded-xl border bg-card">
                        {apiKeys.map((key) => (
                            <li
                                key={key.id}
                                className="flex items-center gap-4 p-4"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="flex items-center gap-2 font-medium">
                                        <span className="truncate">
                                            {key.name}
                                        </span>
                                        {key.revoked_at && (
                                            <Badge variant="destructive">
                                                Revoked
                                            </Badge>
                                        )}
                                    </p>
                                    <p className="truncate text-xs text-muted-foreground">
                                        <code>{key.prefix}…</code> · owner{' '}
                                        {key.user?.name ?? 'unknown'} · created{' '}
                                        {formatDate(key.created_at)} ·{' '}
                                        {key.last_used_at
                                            ? `last used ${formatDate(key.last_used_at)}`
                                            : 'never used'}
                                    </p>
                                </div>
                                {!key.revoked_at && (
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => revoke(key)}
                                        className="text-destructive hover:text-destructive"
                                    >
                                        Revoke
                                    </Button>
                                )}
                            </li>
                        ))}
                    </ul>
                )}

                <Alert>
                    <AlertTitle>Using the webhook</AlertTitle>
                    <AlertDescription className="space-y-2">
                        <p>
                            <code>POST /api/v1/articles</code> with{' '}
                            <code>Authorization: Bearer &lt;key&gt;</code>.
                            Sending the same <code>external_id</code> again
                            updates the article (201 on create, 200 on update).{' '}
                            <code>PUT</code> and <code>DELETE</code> work on{' '}
                            <code>
                                /api/v1/articles/&#123;external_id&#125;
                            </code>
                            .
                        </p>
                        <pre className="overflow-x-auto rounded-md border bg-muted p-3 text-xs">
                            {exampleRequest}
                        </pre>
                    </AlertDescription>
                </Alert>
            </div>
        </AppLayout>
    );
}
