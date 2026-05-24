<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Welcome', [
            'seo' => SeoController::publicPageSeo(
                '/',
                'Email Campaign Operations Platform',
                'Emailora is a clean campaign operations console for contact imports, audience targeting, templates, queued email sends, reporting, and audit logs.'
            ),
        ]);
    }

    public function privacy(): Response
    {
        return Inertia::render('Legal/Privacy', [
            'seo' => SeoController::publicPageSeo(
                '/privacy',
                'Privacy Policy',
                'How Emailora handles contact records, campaign activity, provider events, imports, user accounts, and operational logs.'
            ),
        ]);
    }

    public function terms(): Response
    {
        return Inertia::render('Legal/Terms', [
            'seo' => SeoController::publicPageSeo(
                '/terms',
                'Terms of Use',
                'Terms for using Emailora, an email campaign, contact management, import, reporting, and sending operations platform.'
            ),
        ]);
    }
}
