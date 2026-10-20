import { ImageIcon, Loader2, X } from 'lucide-react';
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { uploadImage } from '@/lib/cloudinary';
import { cn } from '@/lib/utils';

interface Props {
    value: string;
    onChange: (url: string) => void;
    disabled?: boolean;
    aspectHint?: string;
    className?: string;
}

export default function CloudinaryImageUpload({
    value,
    onChange,
    disabled,
    aspectHint,
    className,
}: Props) {
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const inputRef = useRef<HTMLInputElement>(null);

    async function handleFile(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (!file) {
            return;
        }

        setUploading(true);
        setError(null);

        try {
            const data = await uploadImage(file);
            onChange(data.secure_url as string);
        } catch {
            setError('Upload failed. Please try again.');
        } finally {
            setUploading(false);
            if (inputRef.current) {
                inputRef.current.value = '';
            }
        }
    }

    return (
        <div className={cn('flex items-center gap-3', className)}>
            {value && (
                <div className="relative shrink-0">
                    <img
                        src={value}
                        alt="Preview"
                        className="h-16 w-28 rounded-md border border-input object-cover"
                    />
                    <button
                        type="button"
                        onClick={() => onChange('')}
                        disabled={disabled || uploading}
                        className="absolute -top-1.5 -right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-destructive text-white hover:bg-destructive/80 disabled:opacity-50"
                    >
                        <X className="h-2.5 w-2.5" />
                    </button>
                </div>
            )}

            <div className="flex flex-col gap-1">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={disabled || uploading}
                    onClick={() => inputRef.current?.click()}
                >
                    {uploading ? (
                        <>
                            <Loader2 className="animate-spin" /> Uploading…
                        </>
                    ) : (
                        <>
                            <ImageIcon />{' '}
                            {value ? 'Change image' : 'Upload image'}
                        </>
                    )}
                </Button>
                {aspectHint && !value && (
                    <p className="text-[11px] text-muted-foreground">
                        Recommended: {aspectHint} ratio
                    </p>
                )}
                {error && (
                    <p className="text-[11px] text-destructive">{error}</p>
                )}
            </div>

            <input
                ref={inputRef}
                type="file"
                accept="image/*"
                className="hidden"
                onChange={handleFile}
                disabled={disabled || uploading}
            />
        </div>
    );
}
