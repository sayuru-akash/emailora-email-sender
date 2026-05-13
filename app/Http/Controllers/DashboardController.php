<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactImport;
use App\Models\EmailCampaign;
use App\Models\EmailMessage;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'total_contacts' => Contact::count(),
                'active_contacts' => Contact::active()->count(),
                'sent_this_month' => EmailMessage::where('status', 'sent')->whereMonth('sent_at', now()->month)->count(),
                'failed_bounced' => EmailMessage::whereIn('status', ['failed', 'bounced'])->count(),
                'active_campaigns' => EmailCampaign::whereIn('status', ['queued', 'preparing', 'sending', 'paused'])->count(),
                'scheduled_campaigns' => EmailCampaign::where('status', 'scheduled')->count(),
            ],
            'recentCampaigns' => EmailCampaign::latest()->limit(5)->get(['id', 'name', 'status', 'total_recipients', 'sent_count', 'failed_count', 'created_at']),
            'recentImports' => ContactImport::latest()->limit(5)->get(['id', 'file_name', 'status', 'processed_rows', 'total_rows', 'created_at']),
            'contactsByStatus' => Contact::selectRaw('status, count(*) as total')->groupBy('status')->orderBy('status')->get(),
        ]);
    }
}
