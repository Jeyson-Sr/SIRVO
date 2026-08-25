import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, Users } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { oeeNavItems } from '@/features/oee/nav';
import { dashboard } from '@/routes';
import { index as usersIndex } from '@/routes/oee/admin/users';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const page = usePage();
    const currentTeam = page.props.currentTeam;
    const permissions = page.props.teamPermissions;
    const dashboardUrl = currentTeam ? dashboard(currentTeam.slug) : '/';

    const mainNavItems: NavItem[] = [
        ...(permissions?.canViewDashboard !== false
            ? [
                  {
                      title: 'Dashboard',
                      href: dashboardUrl,
                      icon: LayoutGrid,
                  },
              ]
            : []),
        ...(currentTeam ? oeeNavItems(currentTeam.slug, permissions) : []),
        ...(currentTeam && permissions?.canManageUsers
            ? [
                  {
                      title: 'Usuarios',
                      href: usersIndex(currentTeam.slug),
                      icon: Users,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
