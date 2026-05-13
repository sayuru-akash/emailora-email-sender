<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsTableProps;
use App\Models\EmailEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    use BuildsTableProps;

    public function index(Request $request): Response
    {
        $logs = EmailEvent::query()
            ->when($request->filled('search'), fn ($query) => $query->where('event_type', 'like', '%'.$request->string('search').'%')->orWhere('email_normalized', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('provider'), fn ($query) => $query->where('provider', $request->string('provider')))
            ->latest()
            ->paginate($this->perPage($request->input('per_page')))
            ->withQueryString();

        return Inertia::render('ActivityLogs/Index', ['activities' => $this->pagination($logs), 'filters' => $request->only(['search', 'provider', 'per_page'])]);
    }
}
