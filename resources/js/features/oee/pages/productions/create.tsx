import { Head, Link } from '@inertiajs/react';
import { ListChecks, Save } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { ContinuationDialog } from '@/features/oee/components/continuation-dialog';
import { ProductChangeDialog } from '@/features/oee/components/product-change-dialog';
import { RecordingHourList } from '@/features/oee/components/recording-hour-list';
import { RecordingShiftCard } from '@/features/oee/components/recording-shift-card';
import { StopCodePicker } from '@/features/oee/components/stop-code-picker';
import { useRecordingForm } from '@/features/oee/hooks/use-recording-form';
import type { RecordingFormProps } from '@/features/oee/hooks/use-recording-form';
import { dashboard } from '@/routes/oee';
import * as productions from '@/routes/oee/productions';

export default function ProductionCreate(props: RecordingFormProps) {
    const { isClosed = false, shifts, lines } = props;
    const form = useRecordingForm(props);

    return (
        <>
            <Head title={form.isEditing ? 'Editar turno' : 'Registrar turno'} />

            <h1 className="sr-only">
                {form.isEditing
                    ? 'Editar producción de un turno'
                    : 'Registrar producción de un turno'}
            </h1>

            <form
                onSubmit={form.submit}
                className="flex flex-col gap-6 bg-muted/30 p-4 dark:bg-muted/15"
            >
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        variant="small"
                        title={
                            form.isEditing ? 'Editar turno' : 'Registrar turno'
                        }
                        description={
                            form.isEditing
                                ? 'Corrige producido, paradas y datos del turno.'
                                : 'SKU, producido y paradas. El resto lo calcula el sistema.'
                        }
                    />

                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link
                                href={productions.index(form.teamSlug)}
                                prefetch
                            >
                                <ListChecks />
                                Turnos
                            </Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={form.processing}
                            data-test="save-production"
                        >
                            <Save />
                            {form.processing
                                ? 'Guardando…'
                                : form.isEditing
                                  ? 'Guardar cambios'
                                  : 'Guardar'}
                        </Button>
                    </div>
                </div>

                {isClosed && (
                    <p className="rounded-lg border border-primary/30 bg-primary/5 px-4 py-3 text-sm">
                        Este turno está cerrado. Los cambios quedan en el
                        registro histórico.
                    </p>
                )}

                {form.errors.production && (
                    <p className="rounded-lg border border-destructive px-4 py-3 text-sm text-destructive">
                        {form.errors.production}
                    </p>
                )}

                <div className="overflow-hidden rounded-2xl border bg-card shadow-surface">
                    <RecordingShiftCard
                        teamSlug={form.teamSlug}
                        isEditing={form.isEditing}
                        production={form.data.production}
                        shifts={shifts}
                        lines={lines}
                        errors={form.errors}
                        onChangeShift={form.changeShift}
                        onChangeLine={form.changeLine}
                        onChangeOp={(op) => form.setData('production.op', op)}
                        onChangeOperador={(operador) =>
                            form.setData('production.operador', operador)
                        }
                        onChangeSku={(sku) =>
                            form.setData('production.sku', sku)
                        }
                        onPickSku={form.applySku}
                    />

                    <RecordingHourList
                        hours={form.data.hours}
                        errors={form.errors}
                        headerReady={form.headerReady}
                        lastVisibleHourIndex={form.lastVisibleHourIndex}
                        onChangeHour={(index, hour) =>
                            form.setData(`hours.${index}`, hour)
                        }
                        onAddStop={form.setHourAddingStop}
                        onChangeProduct={form.openProductChange}
                    />
                </div>

                <div className="flex justify-end">
                    <Button
                        type="submit"
                        disabled={form.processing}
                        data-test="save-production-bottom"
                    >
                        <Save />
                        {form.processing ? 'Guardando…' : 'Guardar turno'}
                    </Button>
                </div>
            </form>

            <StopCodePicker
                teamSlug={form.teamSlug}
                open={form.hourAddingStop !== null}
                defaultMinutes={
                    form.hourForPicker
                        ? form.pendingMinutes(form.hourForPicker)
                        : 0
                }
                onOpenChange={(open) => !open && form.setHourAddingStop(null)}
                onPick={form.addStop}
            />

            <ContinuationDialog
                continuation={form.continuation}
                onClose={() => form.setContinuation(null)}
                onResolve={form.resolveContinuation}
            />

            <ProductChangeDialog
                open={form.productChange !== null}
                teamSlug={form.teamSlug}
                linea={form.data.production.linea}
                hourRange={
                    form.productChange
                        ? form.data.hours[form.productChange.hourIndex]
                              .hour_range
                        : undefined
                }
                cutTime={form.cutTime}
                nextSkuCode={form.nextSkuCode}
                error={form.productChangeError}
                onClose={() => form.setProductChange(null)}
                onCutTimeChange={form.setCutTime}
                onSkuCodeChange={(sku) => {
                    form.setNextSkuCode(sku);
                    form.setNextSku(null);
                }}
                onSkuPick={(sku) => {
                    form.setNextSkuCode(sku.sku);
                    form.setNextSku(sku);
                }}
                onConfirm={form.applyProductChange}
            />
        </>
    );
}

ProductionCreate.layout = (props: {
    currentTeam?: { slug: string } | null;
    productionId?: number;
}) => ({
    breadcrumbs: [
        {
            title: 'Panel OEE',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Turnos',
            href: props.currentTeam
                ? productions.index(props.currentTeam.slug)
                : '/',
        },
        {
            title: props.productionId ? 'Editar' : 'Registrar',
            href:
                props.currentTeam && props.productionId
                    ? productions.edit([
                          props.currentTeam.slug,
                          props.productionId,
                      ])
                    : props.currentTeam
                      ? productions.create(props.currentTeam.slug)
                      : '/',
        },
    ],
});
