import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { PendingContinuation } from '@/features/oee/hooks/use-recording-form';

type Props = {
    continuation: PendingContinuation | null;
    onClose: () => void;
    onResolve: (isContinuous: boolean) => void;
};

export function ContinuationDialog({
    continuation,
    onClose,
    onResolve,
}: Props) {
    return (
        <Dialog
            open={continuation !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent data-test="continuation-dialog">
                <DialogHeader>
                    <DialogTitle>¿La parada continúa?</DialogTitle>
                    <DialogDescription>
                        El código {continuation?.code.codigo} ya está en la hora
                        anterior ({continuation?.previousRange}). Si es la misma
                        parada continua, se cuenta una sola vez y se copia el
                        comentario.
                    </DialogDescription>
                </DialogHeader>

                {continuation?.previous.comentario !== '' && (
                    <p className="rounded-md border bg-muted/40 px-3 py-2 text-sm">
                        <span className="text-muted-foreground">
                            Comentario anterior:{' '}
                        </span>
                        {continuation?.previous.comentario}
                    </p>
                )}

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onResolve(false)}
                        data-test="continuation-new"
                    >
                        Es otra parada
                    </Button>
                    <Button
                        type="button"
                        onClick={() => onResolve(true)}
                        data-test="continuation-yes"
                    >
                        Sí, continúa
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
