<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\EmailCampaign;
use App\Models\EmailEvent;
use App\Models\EmailMessage;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $period = $this->period($request);
        $from = $this->fromDate($period);
        $messages = EmailMessage::query()->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from));
        $campaigns = EmailCampaign::query()->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from));
        $events = EmailEvent::query()->when($from, fn (Builder $query) => $query->where('occurred_at', '>=', $from));

        $messageTotals = (clone $messages)
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when status in ('sent', 'delivered', 'opened', 'clicked') then 1 else 0 end) as sent")
            ->selectRaw("sum(case when status in ('delivered', 'opened', 'clicked') then 1 else 0 end) as delivered")
            ->selectRaw("sum(case when opened_at is not null or status in ('opened', 'clicked') then 1 else 0 end) as opened")
            ->selectRaw("sum(case when clicked_at is not null or status = 'clicked' then 1 else 0 end) as clicked")
            ->selectRaw("sum(case when status in ('failed', 'bounced', 'complained') then 1 else 0 end) as failed")
            ->first();

        $campaignTotals = (clone $campaigns)
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when status = 'completed' then 1 else 0 end) as completed")
            ->selectRaw("sum(case when status in ('queued', 'preparing', 'sending', 'scheduled', 'paused') then 1 else 0 end) as active")
            ->selectRaw("sum(case when status in ('draft') then 1 else 0 end) as draft")
            ->first();

        return Inertia::render('Reports/Index', [
            'filters' => ['period' => $period],
            'stats' => [
                'contacts' => Contact::count(),
                'active_contacts' => Contact::active()->count(),
                'unsubscribed_contacts' => Contact::where('status', 'unsubscribed')->count(),
                'messages' => (int) ($messageTotals->total ?? 0),
                'sent_messages' => (int) ($messageTotals->sent ?? 0),
                'delivered_messages' => (int) ($messageTotals->delivered ?? 0),
                'opened_messages' => (int) ($messageTotals->opened ?? 0),
                'clicked_messages' => (int) ($messageTotals->clicked ?? 0),
                'failed_messages' => (int) ($messageTotals->failed ?? 0),
                'campaigns' => (int) ($campaignTotals->total ?? 0),
                'completed_campaigns' => (int) ($campaignTotals->completed ?? 0),
                'active_campaigns' => (int) ($campaignTotals->active ?? 0),
                'draft_campaigns' => (int) ($campaignTotals->draft ?? 0),
            ],
            'rates' => [
                'delivery' => $this->rate((int) ($messageTotals->delivered ?? 0), (int) ($messageTotals->sent ?? 0)),
                'open' => $this->rate((int) ($messageTotals->opened ?? 0), (int) ($messageTotals->delivered ?? 0)),
                'click' => $this->rate((int) ($messageTotals->clicked ?? 0), (int) ($messageTotals->delivered ?? 0)),
                'failure' => $this->rate((int) ($messageTotals->failed ?? 0), (int) ($messageTotals->total ?? 0)),
            ],
            'contactsByStatus' => Contact::selectRaw('status, count(*) as total')->groupBy('status')->orderByDesc('total')->get(),
            'contactsBySource' => Contact::selectRaw("coalesce(source, 'unknown') as source, count(*) as total")->groupBy('source')->orderByDesc('total')->limit(10)->get(),
            'campaignsByStatus' => (clone $campaigns)->selectRaw('status, count(*) as total')->groupBy('status')->orderByDesc('total')->get(),
            'messagesByStatus' => (clone $messages)->selectRaw('status, count(*) as total')->groupBy('status')->orderByDesc('total')->get(),
            'eventsByType' => (clone $events)->selectRaw('event_type, count(*) as total')->groupBy('event_type')->orderByDesc('total')->limit(12)->get(),
            'messageTimeline' => $this->messageTimeline($from),
            'topCampaigns' => $this->topCampaigns($from),
            'recentCampaigns' => $this->recentCampaigns($from),
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

    private function period(Request $request): string
    {
        $period = $request->string('period')->toString();

        return in_array($period, ['7', '30', '90', 'all'], true) ? $period : '30';
    }

    private function fromDate(string $period): ?CarbonInterface
    {
        return $period === 'all' ? null : now()->subDays((int) $period);
    }

    private function rate(int $value, int $total): float
    {
        return $total > 0 ? round(($value / $total) * 100, 1) : 0.0;
    }

    private function dateExpression(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "date({$column})"
            : "date({$column})";
    }

    private function messageTimeline(?CarbonInterface $from): array
    {
        return EmailMessage::query()
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from))
            ->selectRaw($this->dateExpression('created_at').' as date')
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when status in ('sent', 'delivered', 'opened', 'clicked') then 1 else 0 end) as sent")
            ->selectRaw("sum(case when status in ('failed', 'bounced', 'complained') then 1 else 0 end) as failed")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'total' => (int) $row->total,
                'sent' => (int) $row->sent,
                'failed' => (int) $row->failed,
            ])
            ->all();
    }

    private function topCampaigns(?CarbonInterface $from): array
    {
        return EmailCampaign::query()
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from))
            ->where('total_recipients', '>', 0)
            ->orderByDesc('sent_count')
            ->limit(8)
            ->get(['id', 'name', 'subject', 'status', 'total_recipients', 'sent_count', 'delivered_count', 'opened_count', 'clicked_count', 'failed_count'])
            ->map(fn (EmailCampaign $campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'subject' => $campaign->subject,
                'status' => $campaign->status,
                'total_recipients' => $campaign->total_recipients,
                'sent_count' => $campaign->sent_count,
                'delivery_rate' => $this->rate((int) $campaign->delivered_count, (int) $campaign->sent_count),
                'open_rate' => $this->rate((int) $campaign->opened_count, (int) $campaign->delivered_count),
                'click_rate' => $this->rate((int) $campaign->clicked_count, (int) $campaign->delivered_count),
                'failed_count' => $campaign->failed_count,
            ])
            ->all();
    }

    private function recentCampaigns(?CarbonInterface $from): array
    {
        return EmailCampaign::query()
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from))
            ->latest()
            ->limit(10)
            ->get(['id', 'name', 'subject', 'status', 'created_at', 'scheduled_at', 'completed_at', 'total_recipients', 'sent_count', 'failed_count'])
            ->map(fn (EmailCampaign $campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'subject' => $campaign->subject,
                'status' => $campaign->status,
                'created_at' => $campaign->created_at?->toDateTimeString(),
                'scheduled_at' => $campaign->scheduled_at?->toDateTimeString(),
                'completed_at' => $campaign->completed_at?->toDateTimeString(),
                'total_recipients' => $campaign->total_recipients,
                'sent_count' => $campaign->sent_count,
                'failed_count' => $campaign->failed_count,
            ])
            ->all();
    }
}
