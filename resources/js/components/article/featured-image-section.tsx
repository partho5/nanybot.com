import CloudinaryImageUpload from '@/components/cloudinary-image-upload';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ArticleFormState } from './form-utils';
import SidebarSection from './sidebar-section';

export default function FeaturedImageSection({
    form,
    onUploaded,
}: {
    form: ArticleFormState;
    onUploaded?: (url: string) => void;
}) {
    return (
        <SidebarSection title="Featured image">
            <div className="space-y-1.5">
                <CloudinaryImageUpload
                    value={form.data.featured_image}
                    onChange={(url) => form.setData('featured_image', url)}
                    onUploaded={onUploaded}
                    aspectHint="16:9"
                    disabled={form.processing}
                />
                <InputError message={form.errors.featured_image} />
            </div>

            <div className="space-y-1.5">
                <Label htmlFor="featured_image_alt">
                    Alt text
                    <span className="ml-1 text-xs font-normal text-muted-foreground">
                        (accessibility &amp; SEO)
                    </span>
                </Label>
                <Input
                    id="featured_image_alt"
                    value={form.data.featured_image_alt}
                    onChange={(e) =>
                        form.setData('featured_image_alt', e.target.value)
                    }
                    placeholder="Describe the image…"
                    disabled={form.processing}
                />
                <InputError message={form.errors.featured_image_alt} />
            </div>

            <div className="space-y-1.5">
                <Label htmlFor="featured_image_caption">
                    Caption
                    <span className="ml-1 text-xs font-normal text-muted-foreground">
                        (shown under the image)
                    </span>
                </Label>
                <Input
                    id="featured_image_caption"
                    value={form.data.featured_image_caption}
                    onChange={(e) =>
                        form.setData('featured_image_caption', e.target.value)
                    }
                    disabled={form.processing}
                />
                <InputError message={form.errors.featured_image_caption} />
            </div>
        </SidebarSection>
    );
}
