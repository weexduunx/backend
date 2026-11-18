<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppFeedback;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Lecture des avis bêta : /admin/avis (mot de passe FEEDBACK_ADMIN_PASSWORD dans .env).
 */
class FeedbackAdminController extends Controller
{
    private const SESSION_KEY = 'feedback_admin';

    private function password(): ?string
    {
        $p = config('services.feedback.admin_password');
        return is_string($p) && $p !== '' ? $p : null;
    }

    private function authorized(Request $request): bool
    {
        return $this->password() !== null && $request->session()->get(self::SESSION_KEY) === true;
    }

    public function loginForm(Request $request)
    {
        abort_if($this->password() === null, 404);
        if ($this->authorized($request)) {
            return redirect()->route('admin.feedback.index');
        }
        return view('admin.feedback-login');
    }

    public function login(Request $request)
    {
        abort_if($this->password() === null, 404);
        $request->validate(['password' => ['required', 'string']]);
        if (!hash_equals($this->password(), (string) $request->input('password'))) {
            return back()->withErrors(['password' => 'Mot de passe incorrect.']);
        }
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, true);
        return redirect()->route('admin.feedback.index');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(self::SESSION_KEY);
        $request->session()->regenerate();
        return redirect()->route('admin.feedback.login');
    }

    private function filtered(Request $request)
    {
        $q = AppFeedback::query()->latest();
        if (in_array($request->query('category'), AppFeedback::CATEGORIES, true)) {
            $q->where('category', $request->query('category'));
        }
        if ($rating = (int) $request->query('rating')) {
            $q->where('rating', $rating);
        }
        if ($search = trim((string) $request->query('q'))) {
            $q->where('message', 'like', '%' . $search . '%');
        }
        return $q;
    }

    public function index(Request $request)
    {
        if (!$this->authorized($request)) {
            return redirect()->route('admin.feedback.login');
        }

        $all = AppFeedback::query();
        $liked = [];
        foreach (AppFeedback::query()->whereNotNull('liked')->pluck('liked') as $list) {
            foreach ((array) $list as $f) {
                $liked[$f] = ($liked[$f] ?? 0) + 1;
            }
        }
        arsort($liked);

        return view('admin.feedback', [
            'items' => $this->filtered($request)->paginate(50)->withQueryString(),
            'stats' => [
                'total' => (clone $all)->count(),
                'testers' => (clone $all)->whereNotNull('client_id')->distinct()->count('client_id'),
                'average' => round((float) (clone $all)->whereNotNull('rating')->avg('rating'), 2),
                'byRating' => (clone $all)->whereNotNull('rating')->selectRaw('rating, count(*) as n')->groupBy('rating')->pluck('n', 'rating'),
                'byCategory' => (clone $all)->selectRaw('category, count(*) as n')->groupBy('category')->pluck('n', 'category'),
                'liked' => $liked,
                'last7' => (clone $all)->where('created_at', '>=', now()->subDays(7))->count(),
            ],
            'filters' => $request->only(['category', 'rating', 'q']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($this->authorized($request), 403);
        $rows = $this->filtered($request)->cursor();
        $columns = ['id', 'created_at', 'rating', 'category', 'liked', 'message', 'contact', 'screen', 'client_id',
            'app_version', 'build', 'platform', 'os_version', 'device', 'locale', 'place', 'sent_at'];

        return response()->streamDownload(function () use ($rows, $columns) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 pour Excel
            fputcsv($out, $columns, ';');
            foreach ($rows as $r) {
                fputcsv($out, array_map(function ($c) use ($r) {
                    $v = $r->{$c};
                    return is_array($v) ? implode(', ', $v) : (string) $v;
                }, $columns), ';');
            }
            fclose($out);
        }, 'avis-bayan-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
