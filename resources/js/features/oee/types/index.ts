import type { SelectOption } from '@/types/ui';

export type { SelectOption };

/** Canonical short codes of the OEE loss families. */
export type StopTypeValue = 'EQ' | 'OPD' | 'OR' | 'PD' | 'QD' | 'RD' | 'TNP';

/** Attainment of a production hour against its target. */
export type HourStatusValue = 'pending' | 'on_target' | 'warning' | 'critical';

export type ShiftValue = 'DIA' | 'NOCHE';

/** Downtime figures keyed by loss family. */
export type LossBreakdown = Record<StopTypeValue, number>;

export type OeeSummary = {
    oee: number;
    em: number;
    closedHours: number;
    scheduledMinutes: number;
    unscheduledMinutes: number;
    effectiveMinutes: number;
    productiveMinutes: number;
    lossMinutes: LossBreakdown;
    lossImpact: LossBreakdown;
};

export type LinePerformance = {
    linea: string;
    oee: number;
    em: number;
    volumen: number;
    closedHours: number;
    lossImpact: LossBreakdown;
    lossMinutes: LossBreakdown;
};

export type DailyPoint = {
    fecha: string;
    label: string;
    oee: number;
    em: number;
    volumen: number;
};

export type WeeklyPoint = {
    semana: string;
    label: string;
    year: number;
    oee: number;
    em: number;
    volumen: number;
};

export type OeeReport = {
    summary: OeeSummary;
    volumen: number;
    parihuelas: number;
    byLine: LinePerformance[];
    byDay: DailyPoint[];
    byWeek: WeeklyPoint[];
};

export type RankedStop = {
    codigo: string;
    descripcion: string | null;
    tipo: StopTypeValue;
    tipoLabel: string;
    totalMinutos: number;
    totalFrecuencia: number;
    porcentaje: number;
    porcentajeAcumulado: number;
};

export type StopCommentGroup = {
    comentario: string;
    fecha: string;
    hora: string;
    totalMinutos: number;
    totalFrecuencia: number;
};

export type StopProductGroup = {
    sku: string;
    producto: string;
    totalMinutos: number;
    totalFrecuencia: number;
};

export type StopLineGroup = {
    linea: string;
    totalMinutos: number;
    totalFrecuencia: number;
};

export type StopOccurrence = {
    sku: string;
    producto: string;
    linea: string;
    totalMinutos: number;
    totalFrecuencia: number;
};

export type ExplainedStop = RankedStop & {
    comentarios: StopCommentGroup[];
    productos: StopProductGroup[];
    lineas: StopLineGroup[];
    ocurrencias: StopOccurrence[];
};

export type DashboardFilterOptions = {
    lineas: string[];
    marcas: string[];
    years: number[];
    componentes: SelectOption[];
    sorts: SelectOption[];
};

export type AppliedFilters = {
    from: string | null;
    to: string | null;
    linea: string | null;
    marca: string | null;
    componente: StopTypeValue | null;
    closedOnly: boolean;
    sortBy: string;
    limit: number;
};

/** A finished good from the SKU catalog, as offered by the type-ahead. */
export type SkuOption = {
    sku: string;
    descripcion: string;
    formato: string | null;
    marca: string | null;
    sabor: string | null;
    palletsPorHora: number;
    bph: number;
};

/** A code from the stop catalog, as offered by the type-ahead. */
export type StopCode = {
    codigo: string;
    detalle: string | null;
    tipo: StopTypeValue;
    tipoLabel: string;
    categoria: string | null;
    causa: string | null;
    recursoAfectado: string | null;
    esTetraPak: boolean;
};

export type StopTypeAdminOption = {
    value: StopTypeValue;
    label: string;
    description: string;
};

export type StopCodeListItem = {
    id: number;
    codigo: string;
    detalle: string;
    tipo: StopTypeValue;
    tipoLabel: string;
    tipoDescription: string;
    categoria: string | null;
    causa: string | null;
    recursoAfectado: string | null;
    esTetraPak: boolean;
    activo: boolean;
};

export type StopCodeFormValues = {
    id: number;
    codigo: string;
    detalle: string;
    tipo_parada: StopTypeValue;
    categoria: string;
    causa: string;
    recurso_afectado: string;
    familia_oee: string;
    es_tetra_pak: boolean;
    activo: boolean;
};

export type StopCodeFilters = {
    search: string;
    tipo_parada: string;
    es_tetra_pak: string;
    activo: string;
};

export type SkuListItem = {
    id: number;
    sku: string;
    linea: string;
    descripcion: string;
    formato: string | null;
    marca: string | null;
    sabor: string | null;
    um: number;
    palletsPorHora: number;
    bph: number;
    compania: string | null;
    mercado: string | null;
    paqPallet: number;
    activo: boolean;
};

export type SkuFormValues = {
    id: number;
    sku: string;
    linea: string;
    descripcion: string;
    formato: string;
    marca: string;
    sabor: string;
    um: string;
    pallets_por_hora: string;
    bph: string;
    compania: string;
    mercado: string;
    nivel: string;
    paq_cama: string;
    cartones: string;
    paq_pallet: string;
    activo: boolean;
};

export type SkuBphChange = {
    id: number;
    linea: string;
    bphAnterior: number | null;
    bphNuevo: number;
    userName: string | null;
    createdAt: string;
};

export type SkuFilters = {
    search: string;
    linea: string;
    activo: string;
};

export type ProductionStop = {
    id: number;
    clientUuid: string;
    codigo: string;
    tipo: StopTypeValue;
    tipoLabel: string;
    descripcion: string | null;
    comentario: string | null;
    tiempoMinutos: number;
    frecuencia: number;
    continua: boolean;
    registeredAt: string;
};

export type ProductionHour = {
    id: number;
    hourIndex: number;
    hourRange: string;
    durationMinutes: number;
    sku: string | null;
    estimado: number;
    producido: number | null;
    status: HourStatusValue;
    statusLabel: string;
    minutosAJustificar: number;
    minutosJustificados: number;
    minutosPendientes: number;
    closed: boolean;
    comments: {
        mnf: string | null;
        mantto: string | null;
        calidad: string | null;
    };
    stops: ProductionStop[];
};

export type ProductionDetail = {
    id: number;
    fecha: string;
    turno: ShiftValue;
    turnoLabel: string;
    linea: string;
    op: string;
    ingeniero: string | null;
    operador: string | null;
    sku: string | null;
    descripcion: string | null;
    formato: string | null;
    marca: string | null;
    sabor: string | null;
    palletsPorHora: number;
    bph: number;
    isClosed: boolean;
    createdBy: string | null;
    hours: ProductionHour[];
};

/** A stop being captured on the recording screen, before it is persisted. */
export type StopDraft = {
    client_uuid: string;
    codigo: string;
    tipo: StopTypeValue;
    descripcion: string;
    comentario: string;
    tiempo_minutos: number;
    frecuencia: number;
    continua: boolean;
    registered_at: string;
};

/** One hour slot being captured on the recording screen. */
export type HourDraft = {
    hour_index: number;
    hour_range: string;
    duration_minutes: number;
    sku: string;
    formato: string;
    pallets_por_hora: string;
    bph: string;
    estimado: string;
    producido: string;
    closed: boolean;
    comments: {
        mnf: string;
        mantto: string;
        calidad: string;
    };
    stops: StopDraft[];
};

/** Header fields of the shift recording form. */
export type ProductionDraft = {
    fecha: string;
    turno: ShiftValue;
    linea: string;
    op: string;
    ingeniero: string;
    operador: string;
    sku: string;
    descripcion: string;
    formato: string;
    marca: string;
    sabor: string;
    pallets_por_hora: string;
    bph: string;
};

export type RecordingPayload = {
    production: ProductionDraft;
    hours: HourDraft[];
};

export type ProductionListItem = {
    id: number;
    fecha: string;
    turno: ShiftValue;
    turnoLabel: string;
    linea: string;
    op: string;
    marca: string | null;
    descripcion: string | null;
    hours: number;
    closedHours: number;
    isClosed: boolean;
    canEdit: boolean;
    canDelete: boolean;
};
