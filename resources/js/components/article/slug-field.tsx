import { useState } from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';

type Props = {
    value: string;
    onChange: (value: string) => void;
    isEdit: boolean;
    error?: string;
    disabled?: boolean;
};

/** `nanybot.com/blog/<slug>` as small text with an Edit link that turns it into an input. */
export default function SlugField({
    value,
    onChange,
    isEdit,
    error,
    disabled,
}: Props) {
    const [editing, setEditing] = useState(false);
    const showInput = editing || Boolean(error);

    return (
        <div className="space-y-1.5">
            {showInput ? (
                <div className="space-y-1.5">
                    <div className="flex items-center gap-1 text-sm text-muted-foreground">
                        <label htmlFor="slug" className="shrink-0">
                            nanybot.com/blog/
                        </label>
                        <Input
                            id="slug"
                            value={value}
                            onChange={(e) =>
                                onChange(
                                    e.target.value
                                        .toLowerCase()
                                        .replace(/[^a-z0-9-]/g, '-'),
                                )
                            }
                            placeholder="my-article"
                            className="h-8 font-mono text-sm"
                            disabled={disabled}
                            autoFocus={editing}
                        />
                        <button
                            type="button"
                            onClick={() => setEditing(false)}
                            className="shrink-0 rounded-sm px-1 text-foreground underline underline-offset-4 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            Done
                        </button>
                    </div>
                    <p className="text-xs text-muted-foreground">
                        {isEdit
                            ? 'Changing the slug breaks existing links.'
                            : 'Generated from the title if empty.'}
                    </p>
                </div>
            ) : (
                <p className="flex flex-wrap items-center gap-x-2 text-sm text-muted-foreground">
                    <span className="font-mono break-all">
                        nanybot.com/blog/
                        {value || (
                            <span className="italic">generated-from-title</span>
                        )}
                    </span>
                    <button
                        type="button"
                        onClick={() => setEditing(true)}
                        disabled={disabled}
                        className="rounded-sm text-foreground underline underline-offset-4 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50"
                    >
                        Edit
                    </button>
                </p>
            )}
            <InputError message={error} />
        </div>
    );
}
