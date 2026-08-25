/**
 * Identify a stop so resubmitting the shift updates it instead of duplicating it.
 *
 * `crypto.randomUUID` only exists in secure contexts, and a plant floor is
 * often served over plain HTTP on the local network, so a version 4 UUID is
 * assembled by hand when it is missing.
 */
export function newClientUuid(): string {
    if (
        typeof crypto !== 'undefined' &&
        typeof crypto.randomUUID === 'function'
    ) {
        return crypto.randomUUID();
    }

    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(
        /[xy]/g,
        (placeholder) => {
            const random = (Math.random() * 16) | 0;
            const value = placeholder === 'x' ? random : (random & 0x3) | 0x8;

            return value.toString(16);
        },
    );
}
