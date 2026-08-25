import { ClipboardList, Gauge, Package, Tags } from 'lucide-react';
import { dashboard as oeeDashboard } from '@/routes/oee';
import { index as skusIndex } from '@/routes/oee/admin/skus';
import { index as stopCodesIndex } from '@/routes/oee/admin/stop-codes';
import { index as productionsIndex } from '@/routes/oee/productions';
import type { NavItem } from '@/types';
import type { TeamPermissions } from '@/types/teams';

export function oeeNavItems(
    teamSlug: string,
    permissions: TeamPermissions | null,
): NavItem[] {
    return [
        ...(permissions?.canViewOee
            ? [
                  {
                      title: 'Panel OEE',
                      href: oeeDashboard(teamSlug),
                      icon: Gauge,
                  },
              ]
            : []),
        ...(permissions?.canViewProductions
            ? [
                  {
                      title: 'Turnos',
                      href: productionsIndex(teamSlug),
                      icon: ClipboardList,
                  },
              ]
            : []),
        ...(permissions?.canViewCatalog
            ? [
                  {
                      title: 'Códigos de parada',
                      href: stopCodesIndex(teamSlug),
                      icon: Tags,
                  },
              ]
            : []),
        ...(permissions?.canViewSkus
            ? [
                  {
                      title: 'Productos',
                      href: skusIndex(teamSlug),
                      icon: Package,
                  },
              ]
            : []),
    ];
}
