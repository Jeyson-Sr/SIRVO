import { Plus, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { HourDraft, StopDraft } from '@/features/oee/types';
import { lossColor } from '@/features/oee/utils';

type Props = {
    hour: HourDraft;
    duration: number;
    onAddStop: () => void;
    onRemoveStop: (clientUuid: string) => void;
    onUpdateStop: (clientUuid: string, changes: Partial<StopDraft>) => void;
    onUpdateComment: (field: keyof HourDraft['comments'], text: string) => void;
};

/**
 * Expanded stop list, comments and add-stop control for one recording hour.
 */
export function HourStopsEditor({
    hour,
    duration,
    onAddStop,
    onRemoveStop,
    onUpdateStop,
    onUpdateComment,
}: Props) {
    return (
        <div
            className="flex flex-col gap-4 border-t p-3"
            data-test="hour-detail"
        >
            <div className="flex flex-col gap-2">
                <p className="text-sm font-medium">Paradas de esta hora</p>

                {hour.stops.length > 0 && (
                    <div className="overflow-x-auto rounded-md border">
                        <table className="w-full min-w-[36rem] text-sm">
                            <thead>
                                <tr className="border-b bg-muted/40 text-left text-xs text-muted-foreground">
                                    <th className="px-3 py-2 font-medium">
                                        Código
                                    </th>
                                    <th className="px-3 py-2 font-medium">
                                        Catálogo
                                    </th>
                                    <th className="px-3 py-2 font-medium">
                                        Min
                                    </th>
                                    <th className="px-3 py-2 font-medium">
                                        Por qué
                                    </th>
                                    <th className="w-10 px-2 py-2">
                                        <span className="sr-only">Quitar</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {hour.stops.map((stop: StopDraft) => (
                                    <tr
                                        key={stop.client_uuid}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-3 py-2 align-top">
                                            <span className="flex items-center gap-2">
                                                <span
                                                    className="size-2.5 shrink-0 rounded-[3px]"
                                                    style={{
                                                        backgroundColor:
                                                            lossColor(
                                                                stop.tipo,
                                                            ),
                                                    }}
                                                />
                                                <span className="font-medium">
                                                    {stop.codigo}
                                                </span>
                                                {stop.continua && (
                                                    <Badge variant="secondary">
                                                        Continúa
                                                    </Badge>
                                                )}
                                            </span>
                                        </td>
                                        <td className="max-w-48 px-3 py-2 align-top text-muted-foreground">
                                            {stop.descripcion}
                                        </td>
                                        <td className="w-28 px-3 py-2 align-top">
                                            <Input
                                                type="number"
                                                min="0.01"
                                                max={duration}
                                                step="0.01"
                                                inputMode="decimal"
                                                value={stop.tiempo_minutos}
                                                onChange={(event) => {
                                                    const parsed =
                                                        Number.parseFloat(
                                                            event.target.value,
                                                        );

                                                    onUpdateStop(
                                                        stop.client_uuid,
                                                        {
                                                            tiempo_minutos:
                                                                Number.isFinite(
                                                                    parsed,
                                                                )
                                                                    ? parsed
                                                                    : 0,
                                                        },
                                                    );
                                                }}
                                                data-test="stop-minutes-edit"
                                            />
                                        </td>
                                        <td className="px-3 py-2 align-top">
                                            <Input
                                                value={stop.comentario}
                                                onChange={(event) =>
                                                    onUpdateStop(
                                                        stop.client_uuid,
                                                        {
                                                            comentario:
                                                                event.target
                                                                    .value,
                                                        },
                                                    )
                                                }
                                                placeholder="Por qué ocurrió esta parada"
                                                data-test="stop-comment"
                                            />
                                        </td>
                                        <td className="px-2 py-2 align-top">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    onRemoveStop(
                                                        stop.client_uuid,
                                                    )
                                                }
                                                aria-label={`Quitar parada ${stop.codigo}`}
                                            >
                                                <Trash2 />
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={onAddStop}
                    className="self-start"
                    data-test="add-stop"
                >
                    <Plus />
                    Agregar parada
                </Button>
            </div>

            <div className="flex flex-col gap-2">
                <p className="text-sm font-medium">Comentarios de la hora</p>
                <div className="grid gap-3 sm:grid-cols-3">
                    {(
                        [
                            ['mnf', 'MNF'],
                            ['mantto', 'Mantenimiento'],
                            ['calidad', 'Calidad'],
                        ] as const
                    ).map(([field, label]) => (
                        <div key={field} className="grid gap-1.5">
                            <Label htmlFor={`${field}-${hour.hour_index}`}>
                                {label}
                            </Label>
                            <textarea
                                id={`${field}-${hour.hour_index}`}
                                value={hour.comments[field]}
                                onChange={(event) =>
                                    onUpdateComment(field, event.target.value)
                                }
                                rows={2}
                                className="rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            />
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
