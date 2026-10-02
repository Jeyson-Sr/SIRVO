import { ChevronLeft, Loader2, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCatalogSearch } from '@/features/oee/hooks/use-catalog-search';
import type { StopCode, StopTypeValue } from '@/features/oee/types';
import { LOSS_FAMILIES, lossColor, lossLabel } from '@/features/oee/utils';
import { index as searchStopCodes } from '@/routes/oee/stop-codes';

type Props = {
    teamSlug: string;
    open: boolean;
    defaultMinutes?: number;
    onOpenChange: (open: boolean) => void;
    onPick: (code: StopCode, minutes: number, frequency: number) => void;
};

/**
 * Pick the affected component first, then a catalog code in that family.
 */
export function StopCodePicker({
    teamSlug,
    open,
    defaultMinutes = 0,
    onOpenChange,
    onPick,
}: Props) {
    const [family, setFamily] = useState<StopTypeValue | null>(null);
    const [search, setSearch] = useState('');
    const [selected, setSelected] = useState<StopCode | null>(null);
    const [minutes, setMinutes] = useState(
        defaultMinutes > 0 ? defaultMinutes.toFixed(1) : '',
    );
    const [frequency, setFrequency] = useState('1');

    const { results, searching } = useCatalogSearch<StopCode>({
        url: searchStopCodes.url(teamSlug, {
            query: {
                search: search || undefined,
                tipo: family ?? undefined,
            },
        }),
        enabled: open && family !== null,
    });

    useEffect(() => {
        if (open) {
            setMinutes(defaultMinutes > 0 ? defaultMinutes.toFixed(1) : '');
        }
    }, [open, defaultMinutes]);

    const reset = () => {
        setFamily(null);
        setSearch('');
        setSelected(null);
        setMinutes(defaultMinutes > 0 ? defaultMinutes.toFixed(1) : '');
        setFrequency('1');
    };

    const handleOpenChange = (nextOpen: boolean) => {
        onOpenChange(nextOpen);

        if (!nextOpen) {
            reset();
        }
    };

    const chooseFamily = (nextFamily: StopTypeValue) => {
        setFamily(nextFamily);
        setSearch('');
        setSelected(null);
    };

    const clearFamily = () => {
        setFamily(null);
        setSearch('');
        setSelected(null);
    };

    const confirm = () => {
        const parsedMinutes = Number.parseFloat(minutes);

        if (
            !selected ||
            !Number.isFinite(parsedMinutes) ||
            parsedMinutes <= 0
        ) {
            return;
        }

        onPick(
            selected,
            parsedMinutes,
            Math.max(1, Number.parseInt(frequency, 10) || 1),
        );
        handleOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Agregar parada</DialogTitle>
                    <DialogDescription>
                        {family
                            ? 'Busca el código de este componente e indica cuánto duró.'
                            : 'Elige el componente afectado para ver sus códigos.'}
                    </DialogDescription>
                </DialogHeader>

                {family === null ? (
                    <div className="grid gap-2" data-test="stop-family-list">
                        <Label>Componente</Label>
                        <ul className="grid gap-2">
                            {LOSS_FAMILIES.map((item) => (
                                <li key={item}>
                                    <button
                                        type="button"
                                        onClick={() => chooseFamily(item)}
                                        data-test="stop-family-option"
                                        className="flex w-full items-center gap-3 rounded-lg border px-3 py-2.5 text-left text-sm hover:bg-accent"
                                    >
                                        <span
                                            className="size-2.5 shrink-0 rounded-[3px]"
                                            style={{
                                                backgroundColor:
                                                    lossColor(item),
                                            }}
                                        />
                                        <span className="min-w-0 flex-1 font-medium uppercase">
                                            {lossLabel(item)}
                                        </span>
                                        <span className="shrink-0 font-medium tabular-nums text-muted-foreground">
                                            {item}
                                        </span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </div>
                ) : (
                    <div className="grid gap-4">
                        <div className="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={clearFamily}
                                data-test="stop-family-back"
                            >
                                <ChevronLeft />
                                Cambiar
                            </Button>
                            <p className="text-sm">
                                <span className="uppercase">
                                    {lossLabel(family)}
                                </span>{' '}
                                <span className="font-medium tabular-nums">
                                    {family}
                                </span>
                            </p>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="stop-search">
                                Código o descripción
                            </Label>
                            <div className="relative">
                                <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                                <Input
                                    id="stop-search"
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    placeholder="Ej. J36 o arranque"
                                    className="pl-9"
                                    autoFocus
                                    data-test="stop-search"
                                />
                                {searching && (
                                    <Loader2 className="absolute top-2.5 right-3 size-4 animate-spin text-muted-foreground" />
                                )}
                            </div>
                        </div>

                        <ul className="max-h-56 overflow-y-auto rounded-md border">
                            {results.map((code) => (
                                <li key={code.codigo}>
                                    <button
                                        type="button"
                                        onClick={() => setSelected(code)}
                                        data-test="stop-code-option"
                                        className={`flex w-full items-center gap-3 border-b px-3 py-2 text-left text-sm last:border-0 hover:bg-accent ${
                                            selected?.codigo === code.codigo
                                                ? 'bg-accent'
                                                : ''
                                        }`}
                                    >
                                        <span
                                            className="size-2.5 shrink-0 rounded-[3px]"
                                            style={{
                                                backgroundColor: lossColor(
                                                    code.tipo,
                                                ),
                                            }}
                                        />
                                        <span className="w-16 shrink-0 font-medium">
                                            {code.codigo}
                                        </span>
                                        <span className="min-w-0 flex-1 truncate text-muted-foreground">
                                            {code.detalle ?? code.tipoLabel}
                                        </span>
                                    </button>
                                </li>
                            ))}

                            {results.length === 0 && !searching && (
                                <li className="px-3 py-6 text-center text-sm text-muted-foreground">
                                    Sin códigos de {lossLabel(family)} en el
                                    catálogo.
                                </li>
                            )}
                        </ul>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="stop-minutes">Minutos</Label>
                                <Input
                                    id="stop-minutes"
                                    type="number"
                                    min="0.01"
                                    max="60"
                                    step="0.01"
                                    value={minutes}
                                    onChange={(event) =>
                                        setMinutes(event.target.value)
                                    }
                                    data-test="stop-minutes"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="stop-frequency">
                                    Frecuencia
                                </Label>
                                <Input
                                    id="stop-frequency"
                                    type="number"
                                    min="1"
                                    step="1"
                                    value={frequency}
                                    onChange={(event) =>
                                        setFrequency(event.target.value)
                                    }
                                />
                            </div>
                        </div>

                        <Button
                            type="button"
                            onClick={confirm}
                            disabled={!selected || minutes === ''}
                            data-test="stop-confirm"
                        >
                            Agregar
                            {selected ? ` ${selected.codigo}` : ''}
                        </Button>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
