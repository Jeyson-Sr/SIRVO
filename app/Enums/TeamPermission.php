<?php

namespace App\Enums;

enum TeamPermission: string
{
    case UpdateTeam = 'team:update';
    case DeleteTeam = 'team:delete';

    case AddMember = 'member:add';
    case UpdateMember = 'member:update';
    case RemoveMember = 'member:remove';

    case CreateInvitation = 'invitation:create';
    case CancelInvitation = 'invitation:cancel';

    case RecordProduction = 'production:record';
    case ReopenProduction = 'production:reopen';
    case DeleteProduction = 'production:delete';

    case ManageCatalog = 'catalog:manage';
    case ManageUsers = 'users:manage';
}
