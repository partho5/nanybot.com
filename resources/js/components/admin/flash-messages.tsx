import { usePage } from '@inertiajs/react';
import { AlertCircleIcon, CheckCircle2Icon } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';

export default function FlashMessages() {
    const { flash } = usePage();

    if (!flash?.success && !flash?.error) {
        return null;
    }

    return (
        <div className="space-y-2" aria-live="polite">
            {flash.success && (
                <Alert className="border-green-200 bg-green-50 text-green-800 dark:border-green-900 dark:bg-green-950/40 dark:text-green-200">
                    <CheckCircle2Icon />
                    <AlertDescription className="text-current">
                        {flash.success}
                    </AlertDescription>
                </Alert>
            )}
            {flash.error && (
                <Alert variant="destructive">
                    <AlertCircleIcon />
                    <AlertDescription className="text-current">
                        {flash.error}
                    </AlertDescription>
                </Alert>
            )}
        </div>
    );
}
