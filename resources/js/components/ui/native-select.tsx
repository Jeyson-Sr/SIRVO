import * as React from 'react';
import { cn } from '@/lib/utils';

/**
 * A styled native select.
 *
 * Unlike the Radix select it submits with a plain form and needs no client
 * state, which is what filter bars and long data-entry grids want.
 */
function NativeSelect({
    className,
    children,
    ...props
}: React.ComponentProps<'select'>) {
    return (
        <select
            data-slot="native-select"
            className={cn(
                'h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none',
                'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
                'disabled:cursor-not-allowed disabled:opacity-50',
                'aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40',
                'dark:bg-input/30',
                className,
            )}
            {...props}
        >
            {children}
        </select>
    );
}

export { NativeSelect };
