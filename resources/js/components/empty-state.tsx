import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Props = {
    icon: LucideIcon;
    title: string;
    description?: string;
    /** A call to action, usually a Button. */
    action?: ReactNode;
    className?: string;
};

/**
 * What a list or report shows when there is nothing to list yet: an icon, a
 * plain statement of what is missing, and optionally the way to fix it.
 */
export function EmptyState({
    icon: Icon,
    title,
    description,
    action,
    className,
}: Props) {
    return (
        <div
            className={cn(
                'flex flex-col items-center gap-3 rounded-2xl border border-dashed px-6 py-12 text-center',
                className,
            )}
        >
            <span className="flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                <Icon className="size-6" aria-hidden />
            </span>

            <div className="grid gap-1">
                <p className="text-title font-medium">{title}</p>
                {description && (
                    <p className="max-w-sm text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>

            {action}
        </div>
    );
}
