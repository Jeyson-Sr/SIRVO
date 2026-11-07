import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon({
    className,
    alt = 'Caral Embotelladora',
    ...props
}: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src="/images/caral-logo.png"
            alt={alt}
            className={className}
            {...props}
        />
    );
}
