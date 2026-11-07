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
            className={cn('h-56 w-full shimmer rounded-2xl', className)}
            aria-hidden
        />
    );
}

/**
 * Placeholder matching the shape of the headline figures row.
 */
export function SummarySkeleton() {
    return (
        <div className="grid items-start gap-4 lg:grid-cols-4" aria-hidden>
            <div className="h-44 shimmer rounded-2xl lg:col-span-2" />
            <div className="grid gap-4 lg:col-span-2">
                <div className="grid gap-4 sm:grid-cols-2">
                    {Array.from({ length: 2 }).map((_, index) => (
                        <div key={index} className="h-20 shimmer rounded-2xl" />
                    ))}
                </div>
                <div className="h-72 shimmer rounded-2xl" />
            </div>
        </div>
    );
}
