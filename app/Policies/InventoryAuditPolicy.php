<?php

namespace App\Policies;

use App\Models\User;
use App\Models\InventoryAudit;

class InventoryAuditPolicy
{
    public function view(User $user, InventoryAudit $audit): bool
    {
        return true; // Настроить в соответствии с требованиями
    }

    public function create(User $user): bool
    {
        return true; // Настроить в соответствии с требованиями
    }

    public function update(User $user, InventoryAudit $audit): bool
    {
        return in_array($audit->status, [
            InventoryAudit::STATUS_DRAFT,
            InventoryAudit::STATUS_IN_PROGRESS
        ]);
    }
} 