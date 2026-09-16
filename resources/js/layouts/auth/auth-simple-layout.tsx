import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center bg-background px-6 py-12">
            <div className="w-full max-w-[22rem]">
                <div className="flex flex-col gap-8">
                    <div className="text-center">
                        <Link href={home()} className="inline-flex justify-center">
                            <AppLogoIcon className="mx-auto h-24 w-auto object-contain" />
                        </Link>
                        {title ? (
                            <p className="mt-4 text-sm text-muted-foreground">
                                {title}
                            </p>
                        ) : null}
                        {description ? (
                            <p className="mt-1 text-sm text-muted-foreground">
                                {description}
                            </p>
                        ) : null}
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
