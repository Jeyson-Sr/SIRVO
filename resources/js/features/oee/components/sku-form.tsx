import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import type { SkuFormValues } from '@/features/oee/types';

type Props = {
    lines: string[];
    values?: Partial<SkuFormValues>;
    errors: Record<string, string>;
};

const EMPTY: SkuFormValues = {
    id: 0,
    sku: '',
    linea: '',
    descripcion: '',
    formato: '',
    marca: '',
    sabor: '',
    pallets_por_hora: '',
    bph: '',
    activo: true,
};

export function SkuForm({ lines, values, errors }: Props) {
    const defaults = { ...EMPTY, ...values };

    return (
        <div className="grid gap-4">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="sku">SKU</Label>
                    <Input
                        id="sku"
                        name="sku"
                        defaultValue={defaults.sku}
                        required
                        maxLength={32}
                        autoComplete="off"
                    />
                    <InputError message={errors.sku} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="linea">Línea</Label>
                    <NativeSelect
                        id="linea"
                        name="linea"
                        defaultValue={defaults.linea || lines[0]}
                        required
                    >
                        {lines.map((line) => (
                            <option key={line} value={line}>
                                {line}
                            </option>
                        ))}
                    </NativeSelect>
                    <InputError message={errors.linea} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="descripcion">Descripción</Label>
                <Input
                    id="descripcion"
                    name="descripcion"
                    defaultValue={defaults.descripcion}
                    required
                    maxLength={255}
                />
                <InputError message={errors.descripcion} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="marca">Marca</Label>
                    <Input
                        id="marca"
                        name="marca"
                        defaultValue={defaults.marca}
                        maxLength={64}
                    />
                    <InputError message={errors.marca} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="sabor">Sabor</Label>
                    <Input
                        id="sabor"
                        name="sabor"
                        defaultValue={defaults.sabor}
                        maxLength={64}
                    />
                    <InputError message={errors.sabor} />
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-3">
                <div className="grid gap-2">
                    <Label htmlFor="formato">Contenido (L)</Label>
                    <Input
                        id="formato"
                        name="formato"
                        defaultValue={defaults.formato}
                        inputMode="decimal"
                        placeholder="0.625"
                        maxLength={32}
                    />
                    <InputError message={errors.formato} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="pallets_por_hora">PH</Label>
                    <Input
                        id="pallets_por_hora"
                        name="pallets_por_hora"
                        type="number"
                        step="0.01"
                        min="0"
                        defaultValue={defaults.pallets_por_hora}
                        required
                    />
                    <InputError message={errors.pallets_por_hora} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="bph">BPH</Label>
                    <Input
                        id="bph"
                        name="bph"
                        type="number"
                        step="0.01"
                        min="0"
                        defaultValue={defaults.bph}
                        required
                    />
                    <InputError message={errors.bph} />
                </div>
            </div>

            <label className="flex items-start gap-3 rounded-lg border p-3">
                <input type="hidden" name="activo" value="0" />
                <input
                    type="checkbox"
                    name="activo"
                    value="1"
                    defaultChecked={defaults.activo}
                    className="mt-1 size-4 rounded border-input"
                />
                <span>
                    <span className="font-medium">Activo</span>
                    <span className="block text-sm text-muted-foreground">
                        Los productos inactivos no aparecen al registrar un
                        turno.
                    </span>
                </span>
            </label>
        </div>
    );
}
