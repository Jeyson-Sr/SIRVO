import { ChevronDown, SlidersHorizontal } from 'lucide-react';
import { useId, useState } from 'react';
import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Props = {
    children: ReactNode;
    /** How many filters are currently applied; shown next to the label. */
    activeCount?: number;
    label?: string;
    className?: string;
};

/**
 * Filters that fold away on phones and sit open from md up.
 *
 * The collapsed state is pure CSS (max-md variants), so the server-rendered
 * markup already matches what the phone shows and nothing flashes open
 * before hydration. The form inside stays mounted either way, so applied
 * values are never lost when the panel is closed.
 */
export function CollapsibleFilters({
    children,
    activeCount = 0,
    label = 'Filtros',
    className,
}: Props) {
    const [open, setOpen] = useState(false);
    const panelId = useId();

    return (
        <div className={className}>
            <Button
                type="button"
                variant="outline"
                className="w-full press justify-between md:hidden"
                aria-expanded={open}
                aria-controls={panelId}
                onClick={() => setOpen((current) => !current)}
            >
                <span className="flex items-center gap-2">
                    <SlidersHorizontal />
                    {label}
                    {activeCount > 0 && (
                        <Badge className="tabular-nums">{activeCount}</Badge>
                    )}
                </span>
                <ChevronDown
                    className={cn(
                        'transition-transform duration-300 ease-spring motion-reduce:transition-none',
                        open && 'rotate-180',
                    )}
                />
            </Button>

            <div
                id={panelId}
                className={cn(
                    'max-md:grid max-md:transition-[grid-template-rows,visibility,margin] max-md:duration-300 max-md:ease-spring motion-reduce:max-md:transition-none',
                    open
                        ? 'max-md:mt-3 max-md:grid-rows-[1fr]'
                        : 'max-md:invisible max-md:mt-0 max-md:grid-rows-[0fr]',
                )}
            >
                <div className="max-md:-m-1 max-md:min-h-0 max-md:overflow-hidden max-md:p-1">
                    {children}
                </div>
            </div>
        </div>
    );
}
