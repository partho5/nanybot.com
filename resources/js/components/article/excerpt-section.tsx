import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import Counter from './counter';
import type { ArticleFormState } from './form-utils';
import { TEXTAREA } from './form-utils';
import SidebarSection from './sidebar-section';

export default function ExcerptSection({ form }: { form: ArticleFormState }) {
    return (
        <SidebarSection title="Excerpt">
            <div className="space-y-1.5">
                <div className="flex items-center justify-between">
                    <Label htmlFor="excerpt" className="text-muted-foreground">
                        Shown on cards and listings
                    </Label>
                    <Counter value={form.data.excerpt} max={500} />
                </div>
                <textarea
                    id="excerpt"
                    rows={4}
                    value={form.data.excerpt}
                    onChange={(e) => form.setData('excerpt', e.target.value)}
                    placeholder="A short summary of the article…"
                    disabled={form.processing}
                    className={TEXTAREA}
                />
                <InputError message={form.errors.excerpt} />
            </div>
        </SidebarSection>
    );
}
