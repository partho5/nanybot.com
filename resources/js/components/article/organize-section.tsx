import NativeSelect from '@/components/admin/native-select';
import InputError from '@/components/input-error';
import TagSuggestions from '@/components/tag-suggestions';
import { Label } from '@/components/ui/label';
import { tags as tagsRoute } from '@/routes/admin/articles';
import type { AuthorOption, CategoryOption } from '@/types/article';
import type { ArticleFormState } from './form-utils';
import SidebarSection from './sidebar-section';

type Props = {
    form: ArticleFormState;
    categories: CategoryOption[];
    authors: AuthorOption[];
    isAdmin: boolean;
};

export default function OrganizeSection({
    form,
    categories,
    authors,
    isAdmin,
}: Props) {
    return (
        <SidebarSection title="Organize">
            <div className="space-y-1.5">
                <Label htmlFor="category_id">Category</Label>
                <NativeSelect
                    id="category_id"
                    value={form.data.category_id}
                    onChange={(e) =>
                        form.setData('category_id', e.target.value)
                    }
                    disabled={form.processing}
                >
                    <option value="">No category</option>
                    {categories.map((cat) => (
                        <option key={cat.id} value={String(cat.id)}>
                            {cat.name}
                        </option>
                    ))}
                </NativeSelect>
                <InputError message={form.errors.category_id} />
            </div>

            {isAdmin && (
                <div className="space-y-1.5">
                    <Label htmlFor="author_id">Author</Label>
                    <NativeSelect
                        id="author_id"
                        value={form.data.author_id}
                        onChange={(e) =>
                            form.setData('author_id', e.target.value)
                        }
                        disabled={form.processing}
                    >
                        {authors.map((a) => (
                            <option key={a.id} value={String(a.id)}>
                                {a.name}
                            </option>
                        ))}
                    </NativeSelect>
                    <InputError message={form.errors.author_id} />
                </div>
            )}

            <div className="space-y-1.5">
                <Label>Tags</Label>
                <TagSuggestions
                    value={form.data.tags}
                    onChange={(v) => form.setData('tags', v)}
                    tagsUrl={tagsRoute.url()}
                />
                <InputError
                    message={
                        form.errors.tags ??
                        Object.entries(form.errors).find(([k]) =>
                            k.startsWith('tags.'),
                        )?.[1]
                    }
                />
            </div>
        </SidebarSection>
    );
}
