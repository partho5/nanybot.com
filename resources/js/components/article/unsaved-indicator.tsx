export default function UnsavedIndicator() {
    return (
        <span
            role="status"
            className="inline-flex items-center gap-1.5 text-xs text-muted-foreground"
        >
            <span
                aria-hidden="true"
                className="size-2 rounded-full bg-[#b83119] dark:bg-[#e0583d]"
            />
            Unsaved changes
        </span>
    );
}
