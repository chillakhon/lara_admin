<?php
namespace App\Traits;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Models\Product;

trait ReviewTrait
{

    public function reviewable_morph_review_map(Request $request): array
    {

        $reviewableMorphMap = [];

        if ($request->get('product_id')) {
            $reviewableMorphMap[Product::class] = ['images']; // or any relations
        }

        // if any other models were morphed with Review model 
        // if ($request->service_id) {
        //     $reviewableMorphMap[Service::class] = ['provider']; // relations
        // }

        // if ($request->store_id) {
        //     $reviewableMorphMap[Store::class] = ['location']; // relations
        // }

        return $reviewableMorphMap;
    }

    public function filter_reviews(Request $request, Builder $reviews, $admin_role): Builder
    {

        $ratings = $request->input('ratings', []);
        $includeUnrated = $request->boolean('include_unrated', false);
        $reply_start_date = $request->get('reply_start_date');
        $reply_end_date = $request->get('reply_end_date');

        if ($request->get('product_id')) {
            $reviews->where('reviewable_type', Product::class)
                ->where('reviewable_id', $request->get('product_id'));
        }

        if ($request->get('search')) {
            $search_value = $request->get('search');
            $reviews->where(function ($query) use ($search_value) {
                $query->where('content', "LIKE", "%{$search_value}%");
            });
        }

        // published should be true by default, then admin
        // can change that from admin panel
        if ($request->has('published')) {
            $reviews->where('is_published', $request->boolean('published'));
        }

        if ($request->has('manager_replied')) {
            $manager_replied = $request->boolean('manager_replied');
            if ($manager_replied) {
                $reviews->whereHas('responses');
            } else {
                $reviews->whereDoesntHave('responses');
            }
        }

        if ($request->has('spam')) {
            $reviews->where('is_spam', $request->boolean('spam'));
        }

        if (count($ratings) >= 1) {
            $reviews->whereIn('rating', $ratings);
        }

        if ($includeUnrated) {
            if (count($ratings) >= 1) {
                $reviews->orWhere(function ($q) {
                    $q->orWhereNull('rating')
                        ->orWhere('rating', 0);
                });
            } else {
                $reviews->where(function ($query) {
                    $query->whereNull('rating')
                        ->orWhere('rating', 0);
                });
            }
        }

        if ($request->boolean('has_image')) {
            $reviews->whereHas('images');
        }

        if ($request->get('start_date')) {
            $reviews->where('created_at', '>=', $request->get('start_date'));
        }

        if ($request->get('end_date')) {
            $reviews->where('created_at', '<=', $request->get('end_date'));
        }

        if ($reply_start_date || $reply_end_date) {
            $reviews->whereHas('responses', function ($query) use ($reply_start_date, $reply_end_date) {
                if ($reply_start_date) {
                    $query->where('created_at', '>=', $reply_start_date);
                }
                if ($reply_end_date) {
                    $query->where('created_at', '<=', $reply_end_date);
                }
            });
        }

        if (!$admin_role) {
            $reviews->verified();
            $reviews->published();
            $reviews->where('is_spam', false);
        }

        $reviews->whereNull("deleted_at");

        return $reviews;
    }
}
