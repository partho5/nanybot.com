import NativeSelect from '@/components/admin/native-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type {
    ArticleDetail,
    ArticleStatus,
    StatusOption,
} from '@/types/article';
import type { ArticleFormState } from './form-utils';
import { timeAgo } from './form-utils';
import SidebarSection from './sidebar-section';
import UnsavedIndicator from './unsaved-indicator';

type Props = {
    form: ArticleFormState;
    article?: ArticleDetail;
    statuses: StatusOption[];
    submitLabel: string;
    onCancel: () => void;
    onPreview: () => void;
};

/** Status, schedule, primary submit and the preview / live links. */
export default function PublishSection({
    form,
    article,
    statuses,
    submitLabel,
    onCancel,
    onPreview,
}: Props) {
    const isScheduled = form.data.status === 'scheduled';

    return (
        <SidebarSection title="Publish">
            <div className="space-y-1.5">
                <Label htmlFor="status">Status</Label>
                <NativeSelect
                    id="status"
                    value={form.data.status}
                    onChange={(e) =>
                        form.setData('status', e.target.value as ArticleStatus)
                    }
                    disabled={form.processing}
                >
                    {statuses.map((s) => (
                        <option key={s.value} value={s.value}>
                            {s.label}
                        </option>
                    ))}
                </NativeSelect>
                <InputError message={form.errors.status} />
            </div>

            {isScheduled && (
                <div className="space-y-1.5">
                    <Label htmlFor="publish_at">
                        Publish at *
                        <span className="ml-1 text-xs font-normal text-muted-foreground">
                            (your local time)
                        </span>
                    </Label>
                    <Input
                        id="publish_at"
                        type="datetime-local"
                        value={form.data.publish_at}
                        onChange={(e) =>
                            form.setData('publish_at', e.target.value)
                        }
                        disabled={form.processing}
                    />
                    <InputError message={form.errors.publish_at} />
                </div>
            )}

            <div className="flex flex-wrap items-center gap-3">
                <Button type="submit" disabled={form.processing}>
                    {submitLabel}
                </Button>
                {form.isDirty && <UnsavedIndicator />}
            </div>

            <div className="flex flex-wrap items-center gap-2">
                <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    onClick={onPreview}
                >
                    Preview
                </Button>
                {article && (
                    <Button type="button" variant="ghost" size="sm" asChild>
                        <a
                            href={article.preview_url}
                            target="_blank"
                            rel="noopener"
                        >
                            Preview saved version
                        </a>
                    </Button>
                )}
                {article?.public_url && (
                    <Button type="button" variant="outline" size="sm" asChild>
                        <a
                            href={article.public_url}
                            target="_blank"
                            rel="noopener"
                        >
                            View live
                        </a>
                    </Button>
                )}
            </div>

            <div className="flex items-center justify-between gap-3 border-t pt-3">
                {article ? (
                    <p
                        className="text-xs text-muted-foreground"
                        title={new Date(article.updated_at).toLocaleString()}
                    >
                        Last saved {timeAgo(article.updated_at)}
                    </p>
                ) : (
                    <p className="text-xs text-muted-foreground">
                        Not saved yet
                    </p>
                )}
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={onCancel}
                    disabled={form.processing}
                >
                    Cancel
                </Button>
            </div>
        </SidebarSection>
    );
}
