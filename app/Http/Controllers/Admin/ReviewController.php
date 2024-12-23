<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $reviews = Review::with(['client', 'reviewable', 'attributes', 'responses'])
            ->when($request->search, function ($query, $search) {
                $query->where('content', 'like', "%{$search}%");
            })
            ->when($request->rating, function ($query, $rating) {
                $query->where('rating', $rating);
            })
            ->when($request->status, function ($query, $status) {
                switch ($status) {
                    case 'published':
                        $query->where('is_published', true);
                        break;
                    case 'pending':
                        $query->where('is_verified', false);
                        break;
                    case 'rejected':
                        $query->where('is_verified', true)
                              ->where('is_published', false);
                        break;
                }
            })
            ->latest()
            ->paginate();

        return Inertia::render('Dashboard/Reviews/Index', [
            'reviews' => $reviews,
            'filters' => $request->only(['search', 'rating', 'status'])
        ]);
    }

    public function update(Request $request, Review $review)
    {
        $validated = $request->validate([
            'is_verified' => 'boolean',
            'is_published' => 'boolean',
        ]);

        $review->update($validated);

        if ($validated['is_published'] && !$review->published_at) {
            $review->update(['published_at' => now()]);
        }

        return back()->with('success', 'Отзыв успешно обновлен');
    }

    public function destroy(Review $review)
    {
        $review->delete();
        return back()->with('success', 'Отзыв успешно удален');
    }

    public function respond(Request $request, Review $review)
    {
        $validated = $request->validate([
            'content' => 'required|string|min:10',
        ]);

        $review->responses()->create([
            'user_id' => auth()->id(),
            'content' => $validated['content'],
        ]);

        return back()->with('success', 'Ответ успешно добавлен');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'reviewable_id' => 'required|exists:products,id',
            'reviewable_type' => 'required|string',
            'rating' => 'required|integer|between:1,5',
            'content' => 'required|string|min:10',
            'is_verified' => 'boolean',
            'is_published' => 'boolean',
        ]);

        $validated['reviewable_type'] = $validated['reviewable_type'] ?? 'App\\Models\\Product';

        $review = Review::create($validated);

        if ($validated['is_published']) {
            $review->published_at = now();
            $review->save();
        }

        return back()->with('success', 'Отзыв успешно создан');
    }
} 