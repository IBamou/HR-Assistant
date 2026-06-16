## Why

The HR Assistant application needs a job offer management system as the foundation for the CV matching workflow. Job offers define the requirements against which submitted CVs will be analyzed and scored by AI. Without structured offer data (skills, experience, responsibilities), the AI-powered CV analysis cannot produce meaningful recommendations.

## What Changes

- Add `Offer` model with all required fields (title, description, responsibilities, required_skills, soft_skills, min_experience_level, education_level, employment_type, location, slug)
- Implement full CRUD routes with soft deletes (archive, restore, force delete)
- Create 5 Volt single-file component pages (Index, Create, Show, Edit, Archived)
- Add `OfferPolicy` for authorization on all actions
- Add `OfferRequest` form request for store/update validation
- Add sidebar navigation link for Offers and Archives
- Include comprehensive feature tests with factory

## Capabilities

### New Capabilities
- `offer-crud`: Complete job offer management — create, read, update, archive/restore, soft deletes, policy-based authorization, Flux UI forms and tables

### Modified Capabilities
<!-- None — this is the first feature in the application -->

## Impact

- **New files**: `app/Models/Offer.php`, `app/Http/Requests/OfferRequest.php`, `app/Policies/OfferPolicy.php`, `database/migrations/*_create_offers_table.php`, `database/factories/OfferFactory.php`, `resources/views/livewire/offers/*.blade.php`, `tests/Feature/OfferCrudTest.php`
- **Modified files**: `routes/web.php` (new offer routes), sidebar navigation component
- **Database**: New `offers` table with soft deletes
- **Dependencies**: None new — uses existing Flux UI, Livewire Volt, Tailwind CSS
