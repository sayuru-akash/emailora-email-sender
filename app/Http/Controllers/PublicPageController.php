<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Welcome');
    }

    public function privacy(): Response
    {
        return Inertia::render('Legal/Privacy');
    }

    public function terms(): Response
    {
        return Inertia::render('Legal/Terms');
    }
}
