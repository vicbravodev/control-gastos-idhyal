import type { ImgHTMLAttributes } from 'react';

import { cn } from '@/lib/utils';

export default function AppLogoIcon({
    className,
    ...props
}: Omit<ImgHTMLAttributes<HTMLImageElement>, 'src' | 'alt'>) {
    return (
        <img
            src="/images/logo-idhyal.png"
            alt="IDHYAL"
            width={180}
            height={180}
            decoding="async"
            draggable={false}
            className={cn(
                'object-contain [image-rendering:-webkit-optimize-contrast]',
                className,
            )}
            {...props}
        />
    );
}
