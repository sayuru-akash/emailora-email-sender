<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsTableProps;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    use BuildsTableProps;

    public function index(Request $request): Response
    {
        $logs = $this->baseQuery($request)
            ->paginate($this->perPage($request->input('per_page')))
            ->withQueryString();

        return Inertia::render('ActivityLogs/Index', [
            'activities' => $this->pagination($logs),
            'filters' => $request->only(['search', 'category', 'event', 'severity', 'user_id', 'per_page']),
            'filterOptions' => [
                'categories' => ActivityLog::query()->select('category')->distinct()->orderBy('category')->pluck('category'),
                'events' => ActivityLog::query()->select('event')->distinct()->orderBy('event')->limit(100)->pluck('event'),
                'severities' => ['info', 'warning', 'error', 'critical'],
                'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        return response()->streamDownload(function () use ($request): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['occurred_at', 'category', 'event', 'severity', 'user', 'subject_type', 'subject_id', 'subject_name', 'description', 'properties', 'ip_address']);

            $this->baseQuery($request)->cursor()->each(function (ActivityLog $log) use ($out): void {
                fputcsv($out, [
                    optional($log->occurred_at)->toDateTimeString(),
                    $log->category,
                    $log->event,
                    $log->severity,
                    $log->user?->email,
                    class_basename((string) $log->subject_type),
                    $log->subject_id,
                    $log->subject_name,
                    $log->description,
                    json_encode($log->properties ?? []),
                    $log->ip_address,
                ]);
            });
        }, 'activity-logs.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function baseQuery(Request $request)
    {
        return ActivityLog::query()
            ->with('user:id,name,email')
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($query) => $query
                ->where('event', 'like', '%'.$request->string('search').'%')
                ->orWhere('description', 'like', '%'.$request->string('search').'%')
                ->orWhere('subject_name', 'like', '%'.$request->string('search').'%')
                ->orWhere('url', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('event'), fn ($query) => $query->where('event', $request->string('event')))
            ->when($request->filled('severity'), fn ($query) => $query->where('severity', $request->string('severity')))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->latest('occurred_at')
            ->latest('id');
    }
}
