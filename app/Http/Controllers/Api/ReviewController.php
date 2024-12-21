<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $reviews = Review::query()
            ->with(['client', 'attributes', 'responses', 'images'])
            ->published()
            ->verified()
            ->when($request->product_id, function ($query, $productId) {
                $query->where('reviewable_type', Product::class)
                    ->where('reviewable_id', $productId);
            })
            ->latest()
            ->paginate();

        return ReviewResource::collection($reviews);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reviewable_id' => 'required|integer',
            'reviewable_type' => 'required|string',
            'content' => 'required|string|min:10',
            'rating' => 'required|integer|between:1,5',
            'attributes' => 'array',
            'attributes.*.name' => 'required|string',
            'attributes.*.rating' => 'required|integer|between:1,5',
            'images' => 'array',
            'images.*' => 'image|max:5120', // 5MB max
        ]);

        $review = Review::create([
            'client_id' => auth()->user()->client->id,
            'reviewable_id' => $validated['reviewable_id'],
            'reviewable_type' => $validated['reviewable_type'],
            'content' => $validated['content'],
            'rating' => $validated['rating'],
        ]);

        if (!empty($validated['attributes'])) {
            foreach ($validated['attributes'] as $attribute) {
                $review->attributes()->create($attribute);
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('reviews', 'public');
                $review->images()->create([
                    'path' => $path,
                    'url' => asset('storage/' . $path),
                ]);
            }
        }

        return new ReviewResource($review->load(['attributes', 'images']));
    }
} 