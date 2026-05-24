<?php

namespace App\Providers;

use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\ContactImport;
use App\Models\EmailCampaign;
use App\Models\EmailEvent;
use App\Models\EmailMessage;
use App\Models\EmailSuppression;
use App\Models\EmailTemplate;
use App\Models\ImportRow;
use App\Models\ListModel;
use App\Models\SavedSegment;
use App\Models\SystemSetting;
use App\Models\Tag;
use App\Models\User;
use App\Observers\ActivityLogObserver;
use App\Services\Activity\ActivityLogger;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureActivityLogging();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        $this->ensureProductionAppUrlIsPublic();

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function ensureProductionAppUrlIsPublic(): void
    {
        if (! app()->isProduction() && config('app.env') !== 'production') {
            return;
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! $host || in_array($host, ['localhost', '127.0.0.1', '::1'], true) || Str::endsWith($host, '.localhost')) {
            throw new \RuntimeException('APP_URL must be a public production URL when APP_ENV=production.');
        }
    }

    protected function configureActivityLogging(): void
    {
        foreach ([
            CampaignRecipient::class,
            Contact::class,
            ContactImport::class,
            EmailCampaign::class,
            EmailEvent::class,
            EmailMessage::class,
            EmailSuppression::class,
            EmailTemplate::class,
            ImportRow::class,
            ListModel::class,
            SavedSegment::class,
            SystemSetting::class,
            Tag::class,
            User::class,
        ] as $model) {
            $model::observe(ActivityLogObserver::class);
        }

        Event::listen(Login::class, fn (Login $event) => app(ActivityLogger::class)->log(
            event: 'auth.login',
            description: 'User signed in.',
            subject: $event->user,
            category: 'auth',
            user: $event->user,
        ));

        Event::listen(Logout::class, fn (Logout $event) => app(ActivityLogger::class)->log(
            event: 'auth.logout',
            description: 'User signed out.',
            subject: $event->user,
            category: 'auth',
            user: $event->user,
        ));

        Event::listen(Failed::class, fn (Failed $event) => app(ActivityLogger::class)->log(
            event: 'auth.failed',
            description: 'Sign-in attempt failed.',
            properties: ['email' => $event->credentials['email'] ?? null],
            category: 'auth',
            severity: 'warning',
        ));
    }
}
