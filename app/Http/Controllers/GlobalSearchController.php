<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\ListModel;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim($request->string('query')->toString());
        if (mb_strlen($query) < 2) {
            return response()->json(['query' => $query, 'groups' => []]);
        }

        return response()->json(['query' => $query, 'groups' => [
            $this->group('Contacts', Contact::search($query)->limit(5)->get()->map(fn (Contact $contact) => ['id' => 'contact-'.$contact->id, 'title' => $contact->display_name, 'subtitle' => $contact->email, 'badge' => $contact->status, 'url' => route('contacts.show', $contact, false)])),
            $this->group('Campaigns', EmailCampaign::search($query)->limit(5)->get()->map(fn (EmailCampaign $campaign) => ['id' => 'campaign-'.$campaign->id, 'title' => $campaign->name, 'subtitle' => $campaign->subject, 'badge' => $campaign->status, 'url' => route('campaigns.show', $campaign, false)])),
            $this->group('Templates', EmailTemplate::search($query)->limit(5)->get()->map(fn (EmailTemplate $template) => ['id' => 'template-'.$template->id, 'title' => $template->name, 'subtitle' => $template->subject, 'badge' => $template->status, 'url' => route('templates.show', $template, false)])),
            $this->group('Lists', ListModel::search($query)->limit(5)->get()->map(fn (ListModel $list) => ['id' => 'list-'.$list->id, 'title' => $list->name, 'subtitle' => 'Mailing list', 'badge' => $list->status, 'url' => route('lists.show', $list, false)])),
            $this->group('Tags', Tag::search($query)->limit(5)->get()->map(fn (Tag $tag) => ['id' => 'tag-'.$tag->id, 'title' => $tag->name, 'subtitle' => 'Tag', 'badge' => 'tag', 'url' => route('tags.show', $tag, false)])),
        ]]);
    }

    private function group(string $label, $items): array
    {
        return ['label' => $label, 'items' => $items->values()];
    }
}
