import type { ImgHTMLAttributes } from 'react';

/** NanyBot mark (same /logo-64.png as the public site header). Decorative: pair it with visible text or an sr-only label. */
export default function AppLogoIcon({
    alt = '',
    ...props
}: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img src="/logo-64.png" width={64} height={64} alt={alt} {...props} />
    );
}
