import { usePage } from '@inertiajs/react';
import { AlertCircle, CheckCircle2 } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';

/** Renders `flash.success` / `flash.error` (Inertia::flash) at the top of an admin page. */
export default function FlashMessages() {
    const { flash } = usePage();
    const success = typeof flash?.success === 'string' ? flash.success : null;
    const error = typeof flash?.error === 'string' ? flash.error : null;

    if (!success && !error) {
        return null;
    }

    return (
        <div className="space-y-2" role="status">
            {success && (
                <Alert>
                    <CheckCircle2 />
                    <AlertDescription>{success}</AlertDescription>
                </Alert>
            )}
            {error && (
                <Alert variant="destructive">
                    <AlertCircle />
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}
        </div>
    );
}
