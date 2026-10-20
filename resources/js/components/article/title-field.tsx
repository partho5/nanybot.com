import { useLayoutEffect, useRef } from 'react';
import InputError from '@/components/input-error';

type Props = {
    value: string;
    onChange: (value: string) => void;
    error?: string;
    disabled?: boolean;
};

/** Large borderless, auto-growing title. Enter is swallowed so titles stay on one logical line. */
export default function TitleField({
    value,
    onChange,
    error,
    disabled,
}: Props) {
    const ref = useRef<HTMLTextAreaElement>(null);

    useLayoutEffect(() => {
        const el = ref.current;

        if (el) {
            el.style.height = 'auto';
            el.style.height = `${el.scrollHeight}px`;
        }
    }, [value]);

    return (
        <div className="space-y-1.5">
            <textarea
                ref={ref}
                id="title"
                rows={1}
                aria-label="Title"
                aria-invalid={error ? true : undefined}
                value={value}
                onChange={(e) => onChange(e.target.value.replace(/\n/g, ' '))}
                onKeyDown={(e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                    }
                }}
                placeholder="Article title"
                disabled={disabled}
                className="block w-full resize-none overflow-hidden border-0 bg-transparent p-0 text-3xl leading-tight font-bold tracking-tight outline-none placeholder:text-muted-foreground/60 focus-visible:ring-0 md:text-4xl"
            />
            <InputError message={error} />
        </div>
    );
}
