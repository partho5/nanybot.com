import { Image } from '@tiptap/extension-image';

/**
 * TipTap image with a caption and intrinsic size.
 *
 * Serialised as `<img src alt width height data-caption>` — the exact
 * attributes App\Services\Blog\ContentProcessor reads (it turns data-caption
 * into <figure>/<figcaption> and uses width/height to avoid layout shift).
 */
export const CaptionedImage = Image.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            caption: {
                default: null,
                parseHTML: (el) => el.getAttribute('data-caption'),
                renderHTML: (attrs) =>
                    attrs.caption
                        ? { 'data-caption': attrs.caption as string }
                        : {},
            },
        };
    },
});
