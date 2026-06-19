## Why

AnalyseCVJob currently creates an empty Analysis record with status `pending`. The CV text extraction pipeline is complete, but no actual AI scoring is performed. This change replaces the placeholder with a real AI agent that evaluates each CV against the offer requirements and returns structured scoring.

## What Changes

- Create `app/Enums/Recommandation.php` backed enum with label() method
- Add `recommendation` cast to `app/Models/Analysis.php`
- Update `database/factories/AnalysisFactory.php` to use enums
- Create `app/Prompts/CvScorerPrompt.php` with JSON schema instruction
- Create `app/Ai/Agents/CvScorer.php` implementing Agent + Promptable
- Rewrite `app/Jobs/AnalyseCVJob.php` to load application, build prompt with CV + offer data, call CvScorer via AiClient, store structured results
- Update `resources/views/livewire/offers/show.blade.php` with analysis badges per application and full modal detail view
- Add tests for AnalyseCVJob, CvScorerPrompt, and OfferCrudTest badge display

## Capabilities

### New Capabilities
- `cv-scoring`: AI-powered CV scoring against job offer requirements with structured output (score, skills, gaps, recommendation)

### Modified Capabilities
- *(none — no spec-level behavior changes to existing capabilities)*

## Impact

- `app/Enums/Recommandation.php` — new file
- `app/Models/Analysis.php` — add cast
- `database/factories/AnalysisFactory.php` — use enums
- `app/Prompts/CvScorerPrompt.php` — new file
- `app/Ai/Agents/CvScorer.php` — new file
- `app/Jobs/AnalyseCVJob.php` — rewrite logic
- `resources/views/livewire/offers/show.blade.php` — add badges + modal
- `tests/Feature/AnalyseCVJobTest.php` — new file
- `tests/Feature/CvScorerPromptTest.php` — new file
- `tests/Feature/OfferCrudTest.php` — add test
