<?php

namespace App\Providers;

use App\Models\Offer;
use App\Policies\Offer\OfferPolicy;
use App\Services\Extraction\Contracts\Extractor;
use App\Services\Extraction\DoclingExtractor;
use App\Services\Extraction\ExtractionOrchestrator;
use App\Services\Extraction\LlamaParseExtractor;
use App\Services\Extraction\LocalPdfExtractor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Volt\Volt;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ExtractionOrchestrator::class, function () {
            return new ExtractionOrchestrator(
                app(DoclingExtractor::class),
                app(LlamaParseExtractor::class),
                app(LocalPdfExtractor::class),
            );
        });

        $this->app->bind(Extractor::class, DoclingExtractor::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Volt::mount([config('view.paths')[0]]);

        Blade::anonymousComponentPath(resource_path('views/components/layouts'), 'layouts');
        Blade::anonymousComponentPath(resource_path('views/pages'), 'pages');

        Gate::policy(Offer::class, OfferPolicy::class);

        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

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
}
