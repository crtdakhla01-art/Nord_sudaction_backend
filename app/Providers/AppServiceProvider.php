<?php

namespace App\Providers;

use App\Events\ContactMessageCreated;
use App\Events\EventPublished;
use App\Events\InscriptionSubmitted;
use App\Events\OpportunityAccepted;
use App\Events\OpportunitySubmitted;
use App\Events\OtpCodeGenerated;
use App\Events\PostPublished;
use App\Listeners\SendContactMessageEmail;
use App\Listeners\SendEventPublishedNewsletterEmail;
use App\Listeners\SendInscriptionSubmissionEmail;
use App\Listeners\SendOpportunityAcceptedNewsletterEmail;
use App\Listeners\SendOpportunitySubmissionEmail;
use App\Listeners\SendOtpCodeEmail;
use App\Listeners\SendPostPublishedNewsletterEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

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
        // Register all event listeners
        Event::listen(ContactMessageCreated::class, SendContactMessageEmail::class);
        Event::listen(InscriptionSubmitted::class, SendInscriptionSubmissionEmail::class);
        Event::listen(OpportunityAccepted::class, SendOpportunityAcceptedNewsletterEmail::class);
        Event::listen(OpportunitySubmitted::class, SendOpportunitySubmissionEmail::class);
        Event::listen(OtpCodeGenerated::class, SendOtpCodeEmail::class);
        Event::listen(PostPublished::class, SendPostPublishedNewsletterEmail::class);
        Event::listen(EventPublished::class, SendEventPublishedNewsletterEmail::class);
    }
}
