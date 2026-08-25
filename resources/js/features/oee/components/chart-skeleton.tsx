import { cn } from '@/lib/utils';

type Props = {
    className?: string;
};

/**
 * Placeholder shown while a deferred figure is still on its way.
 */
export function ChartSkeleton({ className }: Props) {
    return (
        <div
            className={cn(
                'h-56 w-full animate-pulse rounded-lg bg-muted',
                className,
            )}
            aria-hidden
        />
    );
}

/**
 * Placeholder matching the shape of the headline figures row.
 */
export function SummarySkeleton() {
    return (
        <div className="grid gap-4 lg:grid-cols-4" aria-hidden>
            <div className="h-44 animate-pulse rounded-xl bg-muted lg:col-span-2" />
            <div className="grid gap-4 sm:grid-cols-2 lg:col-span-2">
                {Array.from({ length: 4 }).map((_, index) => (
                    <div
                        key={index}
                        className="h-20 animate-pulse rounded-xl bg-muted"
                    />
                ))}
            </div>
        </div>
    );
}
