import { InfoIcon } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import type { TeamInvitationContext } from '@/types';

type Props = {
    invitation: TeamInvitationContext;
    action: 'login' | 'register';
};

export default function TeamInvitationAlert({ invitation, action }: Props) {
    const message =
        action === 'login'
            ? `Inicia sesión para unirte al equipo «${invitation.teamName}».`
            : `Regístrate para unirte al equipo «${invitation.teamName}».`;

    return (
        <Alert
            data-test="team-invitation-alert"
            className="border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-900/50 dark:bg-blue-950/50 dark:text-blue-100 [&>svg]:text-blue-600 dark:[&>svg]:text-blue-400"
        >
            <InfoIcon />
            <AlertDescription className="text-blue-900 dark:text-blue-100">
                {message}
            </AlertDescription>
        </Alert>
    );
}
