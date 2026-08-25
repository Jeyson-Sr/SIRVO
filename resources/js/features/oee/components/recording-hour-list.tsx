import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { HourEntryRow } from '@/features/oee/components/hour-entry-row';
import type { HourDraft } from '@/features/oee/types';
import {
    accumulatedProduced,
    hourLockReason,
    isHourVisible,
    MAX_HOUR_SLICES,
} from '@/features/oee/utils';

type Props = {
    hours: HourDraft[];
    errors: Record<string, string>;
    headerReady: boolean;
    lastVisibleHourIndex: number;
    onChangeHour: (index: number, hour: HourDraft) => void;
    onAddStop: (index: number) => void;
    onChangeProduct: (index: number) => void;
};

export function RecordingHourList({
    hours,
    errors,
    headerReady,
    lastVisibleHourIndex,
    onChangeHour,
    onAddStop,
    onChangeProduct,
}: Props) {
    return (
        <Card className="rounded-none border-0 border-t bg-muted/25 shadow-none">
            <CardHeader>
                <CardTitle>Horas del turno</CardTitle>
                <CardDescription>
                    Solo se muestra la hora en curso. La siguiente aparece al
                    cuadrar los tiempos. Si cambia el producto, corta la hora en
                    el minuto exacto.
                </CardDescription>
            </CardHeader>

            <CardContent className="flex flex-col gap-3">
                {hours.map((hour, index) => {
                    if (!isHourVisible(index, hours)) {
                        return null;
                    }

                    const lockReason = hourLockReason(
                        index,
                        hours,
                        headerReady,
                    );

                    return (
                        <HourEntryRow
                            key={hour.hour_index}
                            hour={hour}
                            acumulado={accumulatedProduced(hours, index)}
                            locked={lockReason !== null}
                            lockReason={lockReason ?? undefined}
                            canChangeProduct={
                                headerReady &&
                                lockReason === null &&
                                index === lastVisibleHourIndex &&
                                hours.length < MAX_HOUR_SLICES
                            }
                            onChangeProduct={() => onChangeProduct(index)}
                            onChange={(updated) => onChangeHour(index, updated)}
                            onAddStop={() => {
                                if (lockReason !== null) {
                                    return;
                                }

                                onAddStop(index);
                            }}
                            errors={{
                                estimado: errors[`hours.${index}.estimado`],
                                producido: errors[`hours.${index}.producido`],
                                stops: errors[`hours.${index}.stops`],
                            }}
                        />
                    );
                })}
            </CardContent>
        </Card>
    );
}
