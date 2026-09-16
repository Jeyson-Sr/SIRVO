import { useState } from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import type { SkuFormValues } from '@/features/oee/types';
import {
    parseSkuNumber,
    skuPaqPallet,
    skuPalletsPerHour,
} from '@/features/oee/utils';

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
    um: '',
    pallets_por_hora: '',
    bph: '',
    compania: '',
    mercado: '',
    nivel: '',
    paq_cama: '',
    cartones: '',
    paq_pallet: '',
    activo: true,
};

export function SkuForm({ lines, values, errors }: Props) {
    const defaults = { ...EMPTY, ...values };
    const [um, setUm] = useState(defaults.um);
    const [bph, setBph] = useState(defaults.bph);
    const [nivel, setNivel] = useState(defaults.nivel);
    const [paqCama, setPaqCama] = useState(defaults.paq_cama);

    const paqPallet = skuPaqPallet(parseSkuNumber(paqCama), parseSkuNumber(nivel));
    const palletsPerHour = skuPalletsPerHour(
        parseSkuNumber(bph),
        parseSkuNumber(um),
        paqPallet,
    );

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

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="compania">Compañía</Label>
                    <Input
                        id="compania"
                        name="compania"
                        defaultValue={defaults.compania}
                        maxLength={64}
                    />
                    <InputError message={errors.compania} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="mercado">Mercado</Label>
                    <Input
                        id="mercado"
                        name="mercado"
                        defaultValue={defaults.mercado}
                        maxLength={64}
                    />
                    <InputError message={errors.mercado} />
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
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
                    <Label htmlFor="um">U.M</Label>
                    <Input
                        id="um"
                        name="um"
                        type="number"
                        step="1"
                        min="1"
                        value={um}
                        onChange={(event) => setUm(event.target.value)}
                        required
                    />
                    <InputError message={errors.um} />
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-3">
                <div className="grid gap-2">
                    <Label htmlFor="nivel">Nivel</Label>
                    <Input
                        id="nivel"
                        name="nivel"
                        type="number"
                        step="1"
                        min="1"
                        value={nivel}
                        onChange={(event) => setNivel(event.target.value)}
                        required
                    />
                    <InputError message={errors.nivel} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="paq_cama">Paq. cama</Label>
                    <Input
                        id="paq_cama"
                        name="paq_cama"
                        type="number"
                        step="1"
                        min="1"
                        value={paqCama}
                        onChange={(event) => setPaqCama(event.target.value)}
                        required
                    />
                    <InputError message={errors.paq_cama} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="cartones">Cartones</Label>
                    <Input
                        id="cartones"
                        name="cartones"
                        type="number"
                        step="1"
                        min="0"
                        defaultValue={defaults.cartones}
                        required
                    />
                    <InputError message={errors.cartones} />
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-3">
                <div className="grid gap-2">
                    <Label htmlFor="bph">BPH</Label>
                    <Input
                        id="bph"
                        name="bph"
                        type="number"
                        step="0.01"
                        min="0"
                        value={bph}
                        onChange={(event) => setBph(event.target.value)}
                        required
                    />
                    <InputError message={errors.bph} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="paq_pallet">Paq. pallet</Label>
                    <Input
                        id="paq_pallet"
                        value={paqPallet || ''}
                        readOnly
                        tabIndex={-1}
                        className="bg-muted"
                    />
                    <p className="text-xs text-muted-foreground">
                        Paq. cama × nivel
                    </p>
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="pallets_por_hora">PH × hora</Label>
                    <Input
                        id="pallets_por_hora"
                        value={palletsPerHour || ''}
                        readOnly
                        tabIndex={-1}
                        className="bg-muted"
                    />
                    <p className="text-xs text-muted-foreground">
                        (BPH / U.M) / paq. pallet
                    </p>
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
