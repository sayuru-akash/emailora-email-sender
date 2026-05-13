<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\EmailCampaign;
use App\Models\EmailMessage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Reports/Index', [
            'stats' => [
                'contacts' => Contact::count(),
                'active_contacts' => Contact::active()->count(),
                'messages' => EmailMessage::count(),
                'campaigns' => EmailCampaign::count(),
            ],
            'contactsByStatus' => Contact::selectRaw('status, count(*) as total')->groupBy('status')->get(),
            'campaignsByStatus' => EmailCampaign::selectRaw('status, count(*) as total')->groupBy('status')->get(),
        ]);
    }

    public function campaignReport(EmailCampaign $campaign): Response
    {
        return Inertia::render('Campaigns/Report', ['campaign' => $campaign, 'breakdown' => $campaign->recipients()->selectRaw('status, count(*) as total')->groupBy('status')->get()]);
    }

    public function exportCampaign(EmailCampaign $campaign): StreamedResponse
    {
        return response()->streamDownload(function () use ($campaign): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'status', 'provider_message_id', 'error']);
            $campaign->recipients()->cursor()->each(fn ($recipient) => fputcsv($out, [$recipient->email_normalized, $recipient->status, $recipient->provider_message_id, $recipient->error_message]));
        }, 'campaign-report.csv');
    }
}
