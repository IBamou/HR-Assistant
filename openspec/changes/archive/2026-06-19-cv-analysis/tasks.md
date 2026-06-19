## 1. Enum & Model

- [x] 1.1 Create `app/Enums/Recommandation.php` with backed string enum (Shortlisted, OnHold, Rejected) and label() method
- [x] 1.2 Add `'recommendation' => Recommandation::class` to Analysis model casts()
- [x] 1.3 Update `database/factories/AnalysisFactory.php` to use Recommandation enum cases and ProcessStatus::Processed

## 2. AI Agent & Prompt

- [x] 2.1 Create `app/Prompts/CvScorerPrompt.php` with build() returning scoring prompt (JSON schema, scoring rules, offer context instructions)
- [x] 2.2 Create `app/Ai/Agents/CvScorer.php` implementing Agent + Promptable, instructions() returns CvScorerPrompt

## 3. Job Rewrite

- [x] 3.1 Rewrite `app/Jobs/AnalyseCVJob.php` — load Application with candidate + offer, build prompt, call CvScorer via AiClient, parse JSON, update Analysis, handle failure

## 4. Frontend

- [x] 4.1 Update `resources/views/livewire/offers/show.blade.php` — add analysis eager load, pending/processed/failed badges per app, clickable row modal with full detail

## 5. Tests

- [x] 5.1 Write `tests/Feature/AnalyseCVJobTest.php` — stores analysis on success, stores failed on AI error, handles missing candidate
- [x] 5.2 Write `tests/Feature/CvScorerPromptTest.php` — prompt contains JSON schema keys, mentions offer context
- [x] 5.3 Update `tests/Feature/OfferCrudTest.php` — add test for analysis badge on show page

## 6. Lint & Final Verification

- [x] 6.1 Run `vendor/bin/pint` to format all modified PHP files
- [x] 6.2 Run `php artisan test --compact` and confirm all tests pass
