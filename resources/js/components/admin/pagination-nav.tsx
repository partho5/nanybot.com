import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types/article';

export default function PaginationNav({
    page,
    noun,
}: {
    page: Paginated<unknown>;
    noun: string;
}) {
    if (page.last_page <= 1) {
        return (
            <p className="text-sm text-muted-foreground">
                {page.total} {noun}
                {page.total === 1 ? '' : 's'}
            </p>
        );
    }

    const prev = page.links[0]?.url;
    const next = page.links[page.links.length - 1]?.url;

    return (
        <div className="flex items-center justify-between gap-4">
            <p className="text-sm text-muted-foreground">
                Page {page.current_page} of {page.last_page} · {page.total}{' '}
                {noun}s
            </p>
            <div className="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    asChild={!!prev}
                    disabled={!prev}
                >
                    {prev ? (
                        <Link href={prev} preserveScroll>
                            Previous
                        </Link>
                    ) : (
                        'Previous'
                    )}
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    asChild={!!next}
                    disabled={!next}
                >
                    {next ? (
                        <Link href={next} preserveScroll>
                            Next
                        </Link>
                    ) : (
                        'Next'
                    )}
                </Button>
            </div>
        </div>
    );
}
