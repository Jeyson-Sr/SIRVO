import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Props = {
    children: ReactNode;
    label?: string;
    className?: string;
};

/**
 * Primary page actions pinned to the bottom edge on phones, within thumb
 * reach. From md up it renders nothing: the page shows the same actions in
 * its header row instead.
 *
 * The bar sits on a translucent material and keeps clear of the home
 * indicator. A spacer in the page flow stops it from covering the last
 * content.
 */
export function MobileActionBar({
    children,
    label = 'Acciones de la página',
    className,
}: Props) {
    return (
        <>
            <div aria-hidden className="h-20 md:hidden" />

            <nav
                aria-label={label}
                className={cn(
                    'fixed inset-x-0 bottom-0 z-40 flex gap-2 material-top px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] md:hidden [&>*]:flex-1',
                    className,
                )}
            >
                {children}
            </nav>
        </>
    );
}
