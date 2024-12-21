<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\TaskComment;
use App\Models\TaskAttachment;
use App\Policies\TaskCommentPolicy;
use App\Policies\TaskAttachmentPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        TaskComment::class => TaskCommentPolicy::class,
        TaskAttachment::class => TaskAttachmentPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();

        Gate::define('manage-tasks', function ($user) {
            \Log::info('User type check:', [
                'user_id' => $user->id,
                'user_type' => $user->type,
                'has_access' => in_array($user->type, ['admin', 'manager'])
            ]);
            
            return in_array($user->type, ['admin', 'manager']);
        });
    }
} 