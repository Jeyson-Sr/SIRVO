import { Loader2, Search } from 'lucide-react';
import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { useCatalogSearch } from '@/features/oee/hooks/use-catalog-search';
import type { SkuOption } from '@/features/oee/types';
import { index as searchSkus } from '@/routes/oee/skus';

type Props = {
    teamSlug: string;
    linea: string;
    value: string;
    inputId?: string;
    onPick: (sku: SkuOption) => void;
    onChange: (sku: string) => void;
};

/**
 * Type a SKU and the catalog fills the rest of the product sheet.
 *
 * Results are limited to the selected line so a shift cannot pick a SKU
 * that the line does not run.
 */
export function SkuPicker({
    teamSlug,
    linea,
    value,
    inputId = 'sku',
    onPick,
    onChange,
}: Props) {
    const [open, setOpen] = useState(false);
    const { results, searching } = useCatalogSearch<SkuOption>({
        url: searchSkus.url(teamSlug, {
            query: {
                search: value || undefined,
                linea: linea || undefined,
            },
        }),
        enabled: linea !== '',
    });

    return (
        <div className="relative">
            <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
            <Input
                id={inputId}
                value={value}
                onChange={(event) => {
                    onChange(event.target.value);
                    setOpen(true);
                }}
                onFocus={() => setOpen(true)}
                onBlur={() => window.setTimeout(() => setOpen(false), 150)}
                placeholder={linea ? '408462' : 'Elige una línea'}
                className="pl-9"
                autoComplete="off"
                disabled={linea === ''}
                data-test="production-sku"
            />
            {searching && (
                <Loader2 className="absolute top-2.5 right-3 size-4 animate-spin text-muted-foreground" />
            )}

            {open && results.length > 0 && (
                <ul className="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-md border bg-popover shadow-md">
                    {results.map((sku) => (
                        <li key={sku.sku}>
                            <button
                                type="button"
                                onMouseDown={(event) => event.preventDefault()}
                                onClick={() => {
                                    onPick(sku);
                                    setOpen(false);
                                }}
                                data-test="sku-option"
                                className="flex w-full flex-col gap-0.5 border-b px-3 py-2 text-left text-sm last:border-0 hover:bg-accent"
                            >
                                <span className="font-medium">
                                    {sku.sku}
                                    {sku.marca ? ` · ${sku.marca}` : ''}
                                </span>
                                <span className="truncate text-xs text-muted-foreground">
                                    {sku.descripcion}
                                </span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
