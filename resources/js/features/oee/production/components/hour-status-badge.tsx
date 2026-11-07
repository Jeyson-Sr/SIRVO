import type { HourStatusValue } from '@/features/oee/types';
import { statusColor } from '@/features/oee/utils';

type Props = {
    status: HourStatusValue;
    label: string;
};

/**
 * The attainment of an hour, coloured by the same tokens used in the charts.
 */
export function HourStatusBadge({ status, label }: Props) {
    return (
        <span
            className="inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-xs font-medium"
            style={{
                color: statusColor(status),
                borderColor: statusColor(status),
            }}
        >
            <span
                className="size-1.5 rounded-full"
                style={{ backgroundColor: statusColor(status) }}
            />
            {label}
        </span>
    );
}
