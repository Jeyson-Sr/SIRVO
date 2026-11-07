import { AppContent } from '@/features/platform/components/app-content';
import { AppShell } from '@/features/platform/components/app-shell';
import { AppSidebar } from '@/features/platform/components/app-sidebar';
import { AppSidebarHeader } from '@/features/platform/components/app-sidebar-header';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-clip">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
    );
}
