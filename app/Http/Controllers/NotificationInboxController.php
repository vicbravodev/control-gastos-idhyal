<?php

namespace App\Http\Controllers;

use App\Services\Notifications\InAppNotificationPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationInboxController extends Controller
{
    private const int PREVIEW_LIMIT = 8;

    public function index(Request $request): Response
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(15)
            ->through(fn (DatabaseNotification $notification): array => $this->presentDatabaseNotification($notification));

        return Inertia::render('notifications/index', [
            'notifications' => $notifications,
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $rows = $request->user()
            ->notifications()
            ->latest()
            ->limit(self::PREVIEW_LIMIT)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => $this->presentDatabaseNotification($notification))
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return redirect()->back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()
            ->back()
            ->with('status', __('Notificaciones marcadas como leídas.'));
    }

    /**
     * @return array{
     *     id: string,
     *     read_at: string|null,
     *     created_at: string|null,
     *     title: string,
     *     body_lines: list<string>,
     *     expense_request_id: int|null,
     *     vacation_request_id: int|null
     * }
     */
    private function presentDatabaseNotification(DatabaseNotification $notification): array
    {
        $data = $notification->data;
        $presented = InAppNotificationPresenter::present(is_array($data) ? $data : []);

        return [
            'id' => $notification->id,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
            'title' => $presented['title'],
            'body_lines' => $presented['body_lines'],
            'expense_request_id' => $presented['expense_request_id'],
            'vacation_request_id' => $presented['vacation_request_id'],
        ];
    }
}
