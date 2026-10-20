import { ChevronDown } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import Counter from './counter';
import type { ArticleFormState } from './form-utils';
import { TEXTAREA, seoPreview } from './form-utils';
import { SearchPreview, SocialPreview } from './seo-previews';
import { SECTION_HEADING } from './sidebar-section';

/** Meta title / description plus live search and social previews. Collapsed by default, opens itself when a field has an error. */
export default function SeoSection({ form }: { form: ArticleFormState }) {
    const [open, setOpen] = useState(false);
    const hasError = Boolean(
        form.errors.meta_title || form.errors.meta_description,
    );
    const isOpen = open || hasError;
    const seo = seoPreview(form.data);

    return (
        <Collapsible
            open={isOpen}
            onOpenChange={setOpen}
            className="rounded-lg border bg-card p-4 text-card-foreground"
        >
            <CollapsibleTrigger
                className={cn(
                    'group flex w-full items-center justify-between rounded-sm text-left outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
                    SECTION_HEADING,
                )}
            >
                SEO
                <ChevronDown className="size-4 transition-transform group-data-[state=open]:rotate-180" />
            </CollapsibleTrigger>

            <CollapsibleContent className="space-y-4 pt-4">
                <div className="space-y-1.5">
                    <div className="flex items-center justify-between">
                        <Label htmlFor="meta_title">
                            Meta title
                            <span className="ml-1 text-xs font-normal text-muted-foreground">
                                (defaults to the title)
                            </span>
                        </Label>
                        <Counter value={form.data.meta_title} max={70} />
                    </div>
                    <Input
                        id="meta_title"
                        value={form.data.meta_title}
                        onChange={(e) =>
                            form.setData('meta_title', e.target.value)
                        }
                        disabled={form.processing}
                    />
                    <InputError message={form.errors.meta_title} />
                </div>

                <div className="space-y-1.5">
                    <div className="flex items-center justify-between">
                        <Label htmlFor="meta_description">
                            Meta description
                            <span className="ml-1 text-xs font-normal text-muted-foreground">
                                (defaults to the excerpt)
                            </span>
                        </Label>
                        <Counter value={form.data.meta_description} max={200} />
                    </div>
                    <textarea
                        id="meta_description"
                        rows={3}
                        value={form.data.meta_description}
                        onChange={(e) =>
                            form.setData('meta_description', e.target.value)
                        }
                        disabled={form.processing}
                        className={TEXTAREA}
                    />
                    <InputError message={form.errors.meta_description} />
                </div>

                <SearchPreview seo={seo} />
                <SocialPreview seo={seo} />
            </CollapsibleContent>
        </Collapsible>
    );
}
