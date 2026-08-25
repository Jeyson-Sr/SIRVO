import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

type Props = {
    label: string;
    value: string;
    hint?: string;
    accent?: string;
    className?: string;
};

/**
 * A single headline figure with its supporting caption.
 */
export function MetricCard({ label, value, hint, accent, className }: Props) {
    return (
        <Card className={cn('gap-0 py-4', className)}>
            <CardContent className="px-4">
                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    {label}
                </p>
                <p
                    className="mt-1 text-2xl font-semibold tabular-nums"
                    style={accent ? { color: accent } : undefined}
                >
                    {value}
                </p>
                {hint && (
                    <p className="mt-0.5 text-xs text-muted-foreground">
                        {hint}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
