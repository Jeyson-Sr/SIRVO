export function skuPaqPallet(paqCama: number, nivel: number): number {
    if (paqCama <= 0 || nivel <= 0) {
        return 0;
    }

    return paqCama * nivel;
}

export function skuPalletsPerHour(
    bph: number,
    um: number,
    paqPallet: number,
): number {
    if (bph <= 0 || um <= 0 || paqPallet <= 0) {
        return 0;
    }

    return Math.round((bph / um / paqPallet) * 100) / 100;
}

export function parseSkuNumber(value: string): number {
    const parsed = Number.parseFloat(value.replace(',', '.'));

    return Number.isFinite(parsed) ? parsed : 0;
}
