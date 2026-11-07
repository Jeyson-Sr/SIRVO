import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import type { ReactNode } from 'react';
import { SidebarProvider, useSidebar } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    children: ReactNode;
    variant?: AppVariant;
};

const SIDEBAR_COOKIE = 'sidebar_state';
const TABLET_QUERY = '(min-width: 768px) and (max-width: 1023px)';

/**
 * Tablets start with the icon rail so the content keeps its width. Anyone
 * who has chosen a state (the cookie exists) keeps it. The provider writes
 * that cookie whenever it changes, so the automatic collapse removes it
 * again: it must not read as a preference once the screen gets wider.
 */
function CollapseOnTablet() {
    const { setOpen } = useSidebar();

    useEffect(() => {
        const hasPreference = document.cookie
            .split('; ')
            .some((entry) => entry.startsWith(`${SIDEBAR_COOKIE}=`));

        if (hasPreference || !window.matchMedia(TABLET_QUERY).matches) {
            return;
        }

        setOpen(false);
        document.cookie = `${SIDEBAR_COOKIE}=; path=/; max-age=0`;
    }, [setOpen]);

    return null;
}

export function AppShell({ children, variant = 'sidebar' }: Props) {
    const isOpen = usePage().props.sidebarOpen;

    if (variant === 'header') {
        return (
            <div className="flex min-h-screen w-full flex-col">{children}</div>
        );
    }

    return (
        <SidebarProvider defaultOpen={isOpen}>
            <CollapseOnTablet />
            {children}
        </SidebarProvider>
    );
}
