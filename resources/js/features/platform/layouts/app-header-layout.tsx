import { AppContent } from '@/features/platform/components/app-content';
import { AppHeader } from '@/features/platform/components/app-header';
import { AppShell } from '@/features/platform/components/app-shell';
import type { AppLayoutProps } from '@/types';

export default function AppHeaderLayout({
    children,
    breadcrumbs,
}: AppLayoutProps) {
    return (
        <AppShell variant="header">
            <AppHeader breadcrumbs={breadcrumbs} />
            <AppContent variant="header">{children}</AppContent>
        </AppShell>
    );
}
