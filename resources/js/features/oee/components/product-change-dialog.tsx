import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SkuPicker } from '@/features/oee/components/sku-picker';
import type { SkuOption } from '@/features/oee/types';

type Props = {
    open: boolean;
    teamSlug: string;
    linea: string;
    hourRange?: string;
    cutTime: string;
    nextSkuCode: string;
    error: string;
    onClose: () => void;
    onCutTimeChange: (value: string) => void;
    onSkuCodeChange: (sku: string) => void;
    onSkuPick: (sku: SkuOption) => void;
    onConfirm: () => void;
};

export function ProductChangeDialog({
    open,
    teamSlug,
    linea,
    hourRange,
    cutTime,
    nextSkuCode,
    error,
    onClose,
    onCutTimeChange,
    onSkuCodeChange,
    onSkuPick,
    onConfirm,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent data-test="product-change-dialog">
                <DialogHeader>
                    <DialogTitle>Cambio de producto</DialogTitle>
                    <DialogDescription>
                        Indica el minuto exacto del corte. Esa hora se parte: el
                        SKU anterior se cobra hasta ahí y el nuevo desde ese
                        momento, cada uno con su PH.
                    </DialogDescription>
                </DialogHeader>

                {open && (
                    <div className="grid gap-4">
                        <p className="text-sm text-muted-foreground">
                            Hora actual:{' '}
                            <span className="font-medium text-foreground">
                                {hourRange}
                            </span>
                        </p>
                        <div className="grid gap-1.5">
                            <Label htmlFor="cut-time">Hora del cambio</Label>
                            <Input
                                id="cut-time"
                                type="time"
                                value={cutTime}
                                onChange={(event) =>
                                    onCutTimeChange(event.target.value)
                                }
                                data-test="product-change-time"
                            />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="change-sku">SKU que entra</Label>
                            <SkuPicker
                                teamSlug={teamSlug}
                                linea={linea}
                                inputId="change-sku"
                                value={nextSkuCode}
                                onChange={(sku) => onSkuCodeChange(sku)}
                                onPick={onSkuPick}
                            />
                        </div>
                        {error !== '' && (
                            <p className="text-sm text-destructive">{error}</p>
                        )}
                    </div>
                )}

                <DialogFooter>
                    <Button type="button" variant="outline" onClick={onClose}>
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        onClick={onConfirm}
                        data-test="product-change-confirm"
                    >
                        Aplicar cambio
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
