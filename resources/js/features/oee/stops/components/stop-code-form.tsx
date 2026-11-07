import { useState } from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import type {
    StopCodeFormValues,
    StopTypeAdminOption,
} from '@/features/oee/types';

type Props = {
    types: StopTypeAdminOption[];
    values?: Partial<StopCodeFormValues>;
    errors: Record<string, string>;
};

const EMPTY: StopCodeFormValues = {
    id: 0,
    codigo: '',
    detalle: '',
    tipo_parada: 'EQ',
    categoria: '',
    causa: '',
    recurso_afectado: '',
    familia_oee: '',
    es_tetra_pak: false,
    activo: true,
};

export function StopCodeForm({ types, values, errors }: Props) {
    const defaults = { ...EMPTY, ...values };
    const [tipo, setTipo] = useState(defaults.tipo_parada);
    const selected = types.find((type) => type.value === tipo);

    return (
        <div className="grid gap-4">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="codigo">Código</Label>
                    <Input
                        id="codigo"
                        name="codigo"
                        defaultValue={defaults.codigo}
                        required
                        maxLength={16}
                        autoComplete="off"
                        className="uppercase"
                    />
                    <InputError message={errors.codigo} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="tipo_parada">Árbol de pérdidas</Label>
                    <NativeSelect
                        id="tipo_parada"
                        name="tipo_parada"
                        value={tipo}
                        onChange={(event) =>
                            setTipo(
                                event.target
                                    .value as StopCodeFormValues['tipo_parada'],
                            )
                        }
                        required
                    >
                        {types.map((type) => (
                            <option key={type.value} value={type.value}>
                                {type.label}
                            </option>
                        ))}
                    </NativeSelect>
                    {selected && (
                        <p className="text-sm text-muted-foreground">
                            {selected.description}
                        </p>
                    )}
                    <InputError message={errors.tipo_parada} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="detalle">Detalle</Label>
                <Input
                    id="detalle"
                    name="detalle"
                    defaultValue={defaults.detalle}
                    required
                    maxLength={255}
                />
                <InputError message={errors.detalle} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="categoria">Categoría</Label>
                    <Input
                        id="categoria"
                        name="categoria"
                        defaultValue={defaults.categoria}
                        maxLength={255}
                        placeholder="Se completa según el árbol"
                    />
                    <InputError message={errors.categoria} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="causa">Causa / recurso</Label>
                    <Input
                        id="causa"
                        name="causa"
                        defaultValue={defaults.causa}
                        maxLength={255}
                    />
                    <InputError message={errors.causa} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="recurso_afectado">Recurso afectado</Label>
                <Input
                    id="recurso_afectado"
                    name="recurso_afectado"
                    defaultValue={defaults.recurso_afectado}
                    maxLength={255}
                />
                <InputError message={errors.recurso_afectado} />
            </div>

            <label className="flex items-start gap-3 rounded-lg border p-3">
                <input type="hidden" name="es_tetra_pak" value="0" />
                <input
                    type="checkbox"
                    name="es_tetra_pak"
                    value="1"
                    defaultChecked={defaults.es_tetra_pak}
                    className="mt-1 size-4 rounded border-input"
                />
                <span>
                    <span className="font-medium">Es de Tetra Pak</span>
                    <span className="block text-sm text-muted-foreground">
                        Márcalo si el código solo aplica a líneas Tetra Pak.
                    </span>
                </span>
            </label>

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
                        Los códigos inactivos no aparecen al registrar un turno.
                    </span>
                </span>
            </label>
        </div>
    );
}
