import { Trash2 } from 'lucide-react';
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { uploadImage } from '@/lib/cloudinary';

export type ImageValues = {
    src: string;
    alt: string;
    caption: string;
    width: number | null;
    height: number | null;
};

type Props = {
    /** Present when editing an image that is already in the article. */
    initial: ImageValues | null;
    onSubmit: (values: ImageValues) => void;
    onRemove: () => void;
    onClose: () => void;
    /** Every successful upload, so the article can clean up images that never get used. */
    onUploaded?: (url: string) => void;
};

/**
 * Insert or edit an image. A modal, so it is always in view no matter how far
 * the article has been scrolled (the old inline panel scrolled out of sight).
 */
export default function ImageDialog({
    initial,
    onSubmit,
    onRemove,
    onClose,
    onUploaded,
}: Props) {
    const editing = initial !== null;
    const fileRef = useRef<HTMLInputElement>(null);
    const [src, setSrc] = useState(initial?.src ?? '');
    const [size, setSize] = useState<{
        width: number | null;
        height: number | null;
    }>({ width: initial?.width ?? null, height: initial?.height ?? null });
    const [alt, setAlt] = useState(initial?.alt ?? '');
    const [caption, setCaption] = useState(initial?.caption ?? '');
    const [fileName, setFileName] = useState('');
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [previewFailed, setPreviewFailed] = useState(false);
    // Editing keeps the current image; replacing it is opt-in.
    const [replacing, setReplacing] = useState(!editing);

    const url = src.trim();

    async function handleFile(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];

        if (!file) {
            return;
        }

        setUploading(true);
        setError(null);
        setFileName(file.name);

        try {
            const data = await uploadImage(file);
            const uploaded = data.secure_url as string;

            onUploaded?.(uploaded);
            setSrc(uploaded);
            setPreviewFailed(false);
            setSize({
                width: typeof data.width === 'number' ? data.width : null,
                height: typeof data.height === 'number' ? data.height : null,
            });
        } catch {
            setError('Upload failed. Please try again.');
        } finally {
            setUploading(false);

            if (fileRef.current) {
                fileRef.current.value = '';
            }
        }
    }

    function changeUrl(value: string) {
        setSrc(value);
        setPreviewFailed(false);
        setFileName('');
        // Intrinsic size is only known for the original; the server probes pasted URLs.
        setSize({ width: null, height: null });
    }

    function submit() {
        if (!url || uploading) {
            return;
        }

        onSubmit({
            src: url,
            alt: alt.trim(),
            caption: caption.trim(),
            width: size.width,
            height: size.height,
        });
    }

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent
                // `dark` swaps in the dark tokens so text, inputs and buttons contrast with the black surface.
                className="dark max-h-[calc(100svh-2rem)] overflow-y-auto border-white/15 bg-black text-foreground"
                // Keep focus out of the editor until we are done, then hand it back.
                onCloseAutoFocus={(e) => e.preventDefault()}
            >
                <DialogHeader>
                    <DialogTitle>
                        {editing ? 'Edit image' : 'Insert image'}
                    </DialogTitle>
                    <DialogDescription>
                        {editing
                            ? 'Change the description or caption. Replace the image only if you need to.'
                            : 'Upload an image or paste its URL.'}
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-4">
                    {url && !previewFailed && (
                        <img
                            src={url}
                            alt=""
                            className="max-h-48 w-full rounded-md border bg-muted/30 object-contain"
                            onError={() => setPreviewFailed(true)}
                        />
                    )}

                    {!replacing && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="justify-self-start"
                            onClick={() => setReplacing(true)}
                        >
                            Replace image
                        </Button>
                    )}

                    {replacing && (
                        <>
                            <div className="grid gap-1.5">
                                <Label>Upload</Label>
                                <div className="flex items-center gap-2">
                                    <input
                                        ref={fileRef}
                                        type="file"
                                        accept="image/*"
                                        className="sr-only"
                                        onChange={handleFile}
                                        disabled={uploading}
                                        tabIndex={-1}
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        disabled={uploading}
                                        onClick={() => fileRef.current?.click()}
                                    >
                                        {uploading
                                            ? 'Uploading…'
                                            : 'Choose file'}
                                    </Button>
                                    <span className="truncate text-xs text-muted-foreground">
                                        {fileName || 'No file chosen'}
                                    </span>
                                </div>
                                {error && (
                                    <p className="text-xs text-destructive">
                                        {error}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="img-url">Image URL</Label>
                                <Input
                                    id="img-url"
                                    type="url"
                                    placeholder="https://…"
                                    value={src}
                                    onChange={(e) => changeUrl(e.target.value)}
                                    onKeyDown={(e) =>
                                        e.key === 'Enter' && submit()
                                    }
                                />
                            </div>
                        </>
                    )}

                    <div className="grid gap-1.5">
                        <Label htmlFor="img-alt">Alt text</Label>
                        <Input
                            id="img-alt"
                            placeholder="Describe the image"
                            value={alt}
                            onChange={(e) => setAlt(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && submit()}
                        />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="img-caption">Caption (optional)</Label>
                        <Input
                            id="img-caption"
                            placeholder="Shown under the image"
                            value={caption}
                            onChange={(e) => setCaption(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && submit()}
                        />
                    </div>
                </div>

                <DialogFooter className="sm:items-center">
                    {editing && (
                        <Button
                            type="button"
                            variant="ghost"
                            className="text-destructive hover:text-destructive sm:mr-auto"
                            onClick={onRemove}
                        >
                            <Trash2 /> Remove image
                        </Button>
                    )}
                    <Button type="button" variant="outline" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        onClick={submit}
                        disabled={uploading || !url}
                    >
                        {editing ? 'Save' : 'Insert'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
