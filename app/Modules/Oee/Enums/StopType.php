<?php

namespace App\Modules\Oee\Enums;

/**
 * The OEE loss families a production stop can be attributed to.
 *
 * Values are the canonical short codes used by the `cod_stops` catalog.
 */
enum StopType: string
{
    case Equipment = 'EQ';
    case Operational = 'OPD';
    case Organizational = 'OR';
    case Planned = 'PD';
    case Quality = 'QD';
    case Routine = 'RD';
    case Unscheduled = 'TNP';

    /**
     * Get the display label for the stop type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Equipment => 'Equipo',
            self::Operational => 'Operativas',
            self::Organizational => 'Organizacionales',
            self::Planned => 'Planificadas',
            self::Quality => 'Pérdidas de calidad',
            self::Routine => 'Rutinarias',
            self::Unscheduled => 'Tiempo no programado',
        };
    }

    /**
     * Explain which loss-tree field this family feeds.
     */
    public function description(): string
    {
        return match ($this) {
            self::Equipment => 'Árbol de pérdidas EQ: fallas y paradas de máquina.',
            self::Operational => 'Árbol de pérdidas OPD: ajustes, regulaciones y cambios de formato.',
            self::Organizational => 'Árbol de pérdidas OR: personal, materiales o coordinación.',
            self::Planned => 'Árbol de pérdidas PD: mantenimientos y paradas programadas.',
            self::Quality => 'Árbol de pérdidas QD: rechazos, retrabajo y no conformes.',
            self::Routine => 'Árbol de pérdidas RD: limpiezas, inspecciones y arranques.',
            self::Unscheduled => 'TNP no es una pérdida: reduce el tiempo programado del turno.',
        };
    }

    /**
     * Resolve a stop type from any of the labels or aliases found in the wild.
     *
     * The catalog stores short codes, but historical records and operator input
     * use the long Spanish names, so both spellings must resolve identically.
     */
    public static function fromLabel(?string $value): ?self
    {
        $normalized = self::normalize($value);

        if ($normalized === '') {
            return null;
        }

        return self::tryFrom($normalized) ?? match ($normalized) {
            'EQUIPO', 'EQUIPOS' => self::Equipment,
            'OPERATIVA', 'OPERATIVAS' => self::Operational,
            'ORGANIZACIONAL', 'ORGANIZACIONALES' => self::Organizational,
            'PLANIFICADA', 'PLANIFICADAS' => self::Planned,
            'CALIDAD', 'PERDIDAS DE CALIDAD' => self::Quality,
            'RUTINARIA', 'RUTINARIAS' => self::Routine,
            'TIEMPO NO PROGRAMADO' => self::Unscheduled,
            default => null,
        };
    }

    /**
     * Determine whether the stop removes time from the scheduled window.
     *
     * Unscheduled time is not a loss: it shrinks the denominator instead of
     * counting against it.
     */
    public function reducesScheduledTime(): bool
    {
        return $this === self::Unscheduled;
    }

    /**
     * Determine whether the stop counts as a productivity loss.
     */
    public function countsAsLoss(): bool
    {
        return ! $this->reducesScheduledTime();
    }

    /**
     * Get every stop type that counts as a productivity loss.
     *
     * @return array<int, self>
     */
    public static function losses(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $type) => $type->countsAsLoss(),
        ));
    }

    /**
     * Get the stop types as selectable options.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }

    /**
     * Get the stop types as selectable options for the catalog admin.
     *
     * @return array<int, array{value: string, label: string, description: string}>
     */
    public static function adminOptions(): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->value.' — '.$type->label(),
                'description' => $type->description(),
            ],
            self::cases(),
        );
    }

    /**
     * Strip accents, casing and padding so aliases can be matched reliably.
     */
    private static function normalize(?string $value): string
    {
        $value = mb_strtoupper(trim((string) $value));

        return str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'],
            ['A', 'E', 'I', 'O', 'U', 'U', 'N'],
            $value,
        );
    }
}
