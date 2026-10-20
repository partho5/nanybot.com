import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center">
                <AppLogoIcon className="size-7" />
            </div>
            <div className="ml-1 grid flex-1 text-left">
                <span className="truncate text-lg leading-tight font-extrabold tracking-tight">
                    nanybot
                </span>
            </div>
        </>
    );
}
