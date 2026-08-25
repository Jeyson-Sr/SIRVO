import InputError from '@/components/input-error';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { SkuPicker } from '@/features/oee/components/sku-picker';
import type {
    ProductionDraft,
    SelectOption,
    ShiftValue,
    SkuOption,
} from '@/features/oee/types';
import { formatNumber } from '@/features/oee/utils';

type Props = {
    teamSlug: string;
    isEditing: boolean;
    production: ProductionDraft;
    shifts: SelectOption[];
    lines: string[];
    errors: Record<string, string>;
    onChangeShift: (turno: ShiftValue) => void;
    onChangeLine: (linea: string) => void;
    onChangeOp: (op: string) => void;
    onChangeOperador: (operador: string) => void;
    onChangeSku: (sku: string) => void;
    onPickSku: (sku: SkuOption) => void;
};

export function RecordingShiftCard({
    teamSlug,
    isEditing,
    production,
    shifts,
    lines,
    errors,
    onChangeShift,
    onChangeLine,
    onChangeOp,
    onChangeOperador,
    onChangeSku,
    onPickSku,
}: Props) {
    return (
        <Card className="rounded-none border-0 shadow-none">
            <CardHeader>
                <CardTitle>Turno</CardTitle>
                <CardDescription>
                    Completa OP, SKU y operador. Sin eso no se puede registrar
                    lo producido.
                </CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div className="grid gap-1.5">
                    <Label htmlFor="fecha">Fecha</Label>
                    <Input
                        id="fecha"
                        type="date"
                        value={production.fecha}
                        readOnly
                        tabIndex={-1}
                        className="bg-muted"
                    />
                </div>

                <div className="grid gap-1.5">
                    <Label htmlFor="turno">Turno</Label>
                    <NativeSelect
                        id="turno"
                        value={production.turno}
                        disabled={isEditing}
                        onChange={(event) =>
                            onChangeShift(event.target.value as ShiftValue)
                        }
                    >
                        {shifts.map((shift) => (
                            <option key={shift.value} value={shift.value}>
                                {shift.label}
                            </option>
                        ))}
                    </NativeSelect>
                </div>

                <div className="grid gap-1.5">
                    <Label htmlFor="linea">Línea</Label>
                    <NativeSelect
                        id="linea"
                        value={production.linea}
                        disabled={isEditing}
                        onChange={(event) => onChangeLine(event.target.value)}
                        data-test="production-linea"
                    >
                        {lines.map((line) => (
                            <option key={line} value={line}>
                                {line}
                            </option>
                        ))}
                    </NativeSelect>
                    <InputError message={errors['production.linea']} />
                </div>

                <div className="grid gap-1.5">
                    <Label htmlFor="op">Orden de producción</Label>
                    <Input
                        id="op"
                        value={production.op}
                        onChange={(event) => onChangeOp(event.target.value)}
                        placeholder="242"
                        required
                        readOnly={isEditing}
                        className={isEditing ? 'bg-muted' : undefined}
                        data-test="production-op"
                    />
                    <InputError message={errors['production.op']} />
                </div>

                <div className="grid gap-1.5 sm:col-span-2">
                    <Label htmlFor="sku">SKU</Label>
                    <SkuPicker
                        teamSlug={teamSlug}
                        linea={production.linea}
                        value={production.sku}
                        onChange={onChangeSku}
                        onPick={onPickSku}
                    />
                    <InputError message={errors['production.sku']} />
                </div>

                <div className="grid gap-1.5">
                    <Label htmlFor="ingeniero">Ingeniero</Label>
                    <Input
                        id="ingeniero"
                        value={production.ingeniero}
                        readOnly
                        tabIndex={-1}
                        className="bg-muted"
                        data-test="production-ingeniero"
                    />
                </div>

                <div className="grid gap-1.5">
                    <Label htmlFor="operador">Operador</Label>
                    <Input
                        id="operador"
                        value={production.operador}
                        onChange={(event) =>
                            onChangeOperador(event.target.value)
                        }
                        required
                    />
                    <InputError message={errors['production.operador']} />
                </div>
            </CardContent>

            {production.descripcion !== '' && (
                <CardContent className="border-t pt-4">
                    <p className="text-sm font-medium">
                        {production.descripcion}
                    </p>
                    <p className="mt-1 text-xs text-muted-foreground">
                        {production.marca}
                        {production.sabor ? ` · ${production.sabor}` : ''}
                        {production.formato ? ` · ${production.formato}` : ''}
                        {' · '}
                        Meta {production.pallets_por_hora} pal/h
                        {' · '}
                        {formatNumber(
                            Number.parseFloat(production.bph) || 0,
                        )}{' '}
                        BPH
                    </p>
                </CardContent>
            )}
        </Card>
    );
}
