<?php
namespace App\Traits;

use App\Models\User;
use App\Models\UserProfile;

trait ClientControllerTrait
{
    public function check_users_with_same_email($client): UserProfile|null
    {
        $check_for_user_with_same_email = User::whereNull('deleted_at')
            ->where('email', $client->email)
            ->first();

        $user_profile = null;

        if ($check_for_user_with_same_email) {
            $user_profile = UserProfile
                ::where('user_id', $check_for_user_with_same_email->id)
                ->first();
        }

        return $user_profile;
    }
}
