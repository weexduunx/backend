<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AppFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    /** Reçoit un avis de l'app (sans compte). */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rating' => ['nullable', 'integer', 'between:1,5', 'required_without:message'],
            'category' => ['nullable', Rule::in(AppFeedback::CATEGORIES)],
            'liked' => ['nullable', 'array', 'max:10'],
            'liked.*' => [Rule::in(AppFeedback::FEATURES)],
            'message' => ['nullable', 'string', 'max:3000', 'required_without:rating'],
            'contact' => ['nullable', 'string', 'max:120'],
            'screen' => ['nullable', 'string', 'max:60'],
            'client_id' => ['nullable', 'uuid'],
            'app_version' => ['nullable', 'string', 'max:20'],
            'build' => ['nullable', 'string', 'max:20'],
            'platform' => ['nullable', 'string', 'max:10'],
            'os_version' => ['nullable', 'string', 'max:20'],
            'device' => ['nullable', 'string', 'max:80'],
            'locale' => ['nullable', 'string', 'max:20'],
            'place' => ['nullable', 'string', 'max:120'],
            'sent_at' => ['nullable', 'date'],
        ]);

        $data['category'] = $data['category'] ?? 'general';
        $data['message'] = isset($data['message']) ? trim($data['message']) : null;

        $feedback = AppFeedback::create($data);

        return response()->json(['success' => true, 'data' => ['id' => $feedback->id]], 201);
    }
}
