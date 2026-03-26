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
            className={cn('object-contain', className)}
            {...props}
        />
    );
}
