import { useEffect, useRef, useState } from 'react';

const SEARCH_DEBOUNCE_MS = 250;

type Payload<T> = {
    data: T[];
};

/**
 * Debounced JSON catalog lookup. A slower earlier response must not overwrite
 * a newer one, which is the same rule both type-aheads already followed.
 */
export function useCatalogSearch<T>({
    url,
    enabled = true,
}: {
    url: string;
    enabled?: boolean;
}): { results: T[]; searching: boolean } {
    const [results, setResults] = useState<T[]>([]);
    const [searching, setSearching] = useState(false);
    const requestId = useRef(0);

    useEffect(() => {
        if (!enabled) {
            setResults([]);
            setSearching(false);

            return;
        }

        setSearching(true);

        const timer = window.setTimeout(async () => {
            const currentRequest = ++requestId.current;

            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                });
                const payload: Payload<T> = await response.json();

                if (currentRequest === requestId.current) {
                    setResults(payload.data);
                }
            } finally {
                if (currentRequest === requestId.current) {
                    setSearching(false);
                }
            }
        }, SEARCH_DEBOUNCE_MS);

        return () => window.clearTimeout(timer);
    }, [url, enabled]);

    return { results, searching };
}
