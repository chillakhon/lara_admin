<?php

namespace App\Providers;

use App\Models\Review;
use App\Models\TaskStatus;
use App\Policies\ReviewPolicy;
use App\Policies\TaskStatusPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\TaskComment;
use App\Models\TaskAttachment;
use App\Policies\TaskCommentPolicy;
use App\Policies\TaskAttachmentPolicy;
use Log;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        TaskStatus::class => TaskStatusPolicy::class,
        TaskComment::class => TaskCommentPolicy::class,
        TaskAttachment::class => TaskAttachmentPolicy::class,
        Review::class => ReviewPolicy::class,
    ];


    public function boot()
    {
        $this->registerPolicies();

        Gate::define('manage-tasks', function ($user) {
            Log::info('User type check:', [
                'user_id' => $user->id,
                'user_type' => $user->type,
                'has_access' => in_array($user->type, ['admin', 'manager', 'super-admin']),
            ]);

            return in_array($user->type, ['admin', 'manager', 'super-admin']);
        });
    }
}
