import type { ReactNode } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

/**
 * Building blocks for skeleton screens: placeholders that keep the exact
 * footprint of the content they stand in for, so nothing jumps when the
 * real thing arrives. They are decorative; whoever mounts them announces
 * the loading state once (see NavigationSkeleton).
 */

/** The same container every page uses, so the skeleton lines up with it. */
export function PageFrame({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'mx-auto flex w-full max-w-[90rem] flex-col gap-4 p-4 md:gap-6 md:p-6',
                className,
            )}
        >
            {children}
        </div>
    );
}

/** A surface shaped like a Card, for content that has not arrived yet. */
export function CardSkeleton({
    children,
    className,
}: {
    children?: ReactNode;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'rounded-2xl border bg-card p-6 shadow-surface',
                className,
            )}
        >
            {children}
        </div>
    );
}

/** A page title and its one-line description. */
export function PageHeaderSkeleton() {
    return (
        <div className="grid gap-2">
            <Skeleton className="h-6 w-48" />
            <Skeleton className="h-4 w-72 max-w-full" />
        </div>
    );
}

/** A card title with its caption. */
export function CardHeaderSkeleton() {
    return (
        <div className="grid gap-2">
            <Skeleton className="h-5 w-40" />
            <Skeleton className="h-4 w-64 max-w-full" />
        </div>
    );
}

/** A half-circle gauge with its label underneath. */
export function GaugeSkeleton({ size = 'compact' }: { size?: 'hero' | 'compact' }) {
    return (
        <div className="flex w-full flex-col items-center gap-3">
            <Skeleton
                className={cn(
                    'aspect-[2/1] w-full rounded-t-full rounded-b-lg',
                    size === 'hero' ? 'max-w-[320px]' : 'max-w-[180px]',
                )}
            />
            <Skeleton className="h-4 w-16" />
        </div>
    );
}

/** Rows of a list of cards, like turnos or productos. */
export function ListSkeleton({ rows = 5 }: { rows?: number }) {
    return (
        <div className="flex flex-col gap-3">
            {Array.from({ length: rows }).map((_, index) => (
                <div
                    key={index}
                    className="flex items-center justify-between gap-4 rounded-2xl border bg-card p-4"
                >
                    <div className="grid min-w-0 flex-1 gap-2">
                        <Skeleton className="h-5 w-1/3 min-w-32" />
                        <Skeleton className="h-4 w-2/3" />
                    </div>
                    <Skeleton className="h-9 w-24 shrink-0" />
                </div>
            ))}
        </div>
    );
}

/** A table: a header strip and evenly spaced rows. */
export function TableSkeleton({
    rows = 6,
    columns = 5,
}: {
    rows?: number;
    columns?: number;
}) {
    const grid = {
        gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))`,
    };

    return (
        <div className="grid gap-3">
            <div className="grid gap-4" style={grid}>
                {Array.from({ length: columns }).map((_, index) => (
                    <Skeleton key={index} className="h-3 w-3/4" />
                ))}
            </div>

            {Array.from({ length: rows }).map((_, row) => (
                <div
                    key={row}
                    className="grid gap-4 border-t pt-3"
                    style={grid}
                >
                    {Array.from({ length: columns }).map((_, column) => (
                        <Skeleton
                            key={column}
                            className={cn(
                                'h-4',
                                column === 0 ? 'w-1/2' : 'w-full',
                            )}
                        />
                    ))}
                </div>
            ))}
        </div>
    );
}

/** A form: label and field pairs, then the submit button. */
export function FormSkeleton({ fields = 5 }: { fields?: number }) {
    return (
        <CardSkeleton className="grid gap-5">
            {Array.from({ length: fields }).map((_, index) => (
                <div key={index} className="grid gap-2">
                    <Skeleton className="h-4 w-28" />
                    <Skeleton className="h-9 w-full" />
                </div>
            ))}
            <Skeleton className="h-9 w-32" />
        </CardSkeleton>
    );
}

/** The generic screen: a header, a toolbar and a list. */
export function PageSkeleton() {
    return (
        <PageFrame>
            <PageHeaderSkeleton />
            <Skeleton className="h-11 w-full rounded-2xl" />
            <ListSkeleton />
        </PageFrame>
    );
}
