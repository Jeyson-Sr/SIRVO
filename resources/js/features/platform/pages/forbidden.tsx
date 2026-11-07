import { Head, router } from '@inertiajs/react';
import { LoaderCircle, ShieldOff } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

const COUNTDOWN_SECONDS = 10;

type Props = {
    fallbackUrl: string;
};

export default function Forbidden({ fallbackUrl }: Props) {
    const [seconds, setSeconds] = useState(COUNTDOWN_SECONDS);
    const left = useRef(false);

    const leave = useCallback(() => {
        if (left.current) {
            return;
        }

        left.current = true;

        const referrer = document.referrer;
        const sameOrigin =
            referrer !== '' &&
            referrer.startsWith(window.location.origin) &&
            referrer !== window.location.href;

        if (sameOrigin) {
            router.visit(referrer);

            return;
        }

        if (window.history.length > 1) {
            window.history.back();

            return;
        }

        router.visit(fallbackUrl);
    }, [fallbackUrl]);

    useEffect(() => {
        if (seconds <= 0) {
            leave();

            return;
        }

        const timer = window.setTimeout(
            () => setSeconds((current) => current - 1),
            1000,
        );

        return () => window.clearTimeout(timer);
    }, [leave, seconds]);

    return (
        <>
            <Head title="Sin permiso" />

            <div className="flex flex-1 items-center justify-center p-6">
                <Card className="w-full max-w-lg shadow-raised">
                    <CardHeader className="items-center text-center">
                        <div className="mb-2 flex size-14 items-center justify-center rounded-full bg-destructive/10 text-destructive">
                            <ShieldOff className="size-7" />
                        </div>
                        <CardTitle className="text-2xl">
                            Ups… por aquí no
                        </CardTitle>
                        <CardDescription>
                            ¿Y ahora qué hiciste? Quisiste cambiar algo que no
                            te toca. La línea no se mueve con ganas: se mueve
                            con permisos.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 text-center">
                        <p className="text-sm text-muted-foreground">
                            Pídele acceso a un administrador. O finge que
                            viniste a ver el paisaje. Nadie se entera si sales
                            en silencio.
                        </p>
                        <div
                            className="flex items-center justify-center gap-2 text-sm font-medium"
                            data-test="forbidden-countdown"
                        >
                            <LoaderCircle className="size-4 animate-spin text-muted-foreground" />
                            Te devolvemos en {seconds}s, antes de que alguien
                            pregunte.
                        </div>
                    </CardContent>
                    <CardFooter className="justify-center">
                        <Button
                            type="button"
                            onClick={leave}
                            data-test="forbidden-leave"
                        >
                            Sácame de aquí
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </>
    );
}

Forbidden.layout = (props: { fallbackUrl?: string }) => ({
    breadcrumbs: [
        {
            title: 'Sin acceso',
            href: props.fallbackUrl ?? '/',
        },
    ],
});
