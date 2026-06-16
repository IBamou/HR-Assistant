## Context

The HR Assistant is a Laravel 13 application using Livewire Volt + Flux UI for the frontend. It has authentication via Fortify, a single user role (RH Agent), and all data is user-scoped. This is the first feature being built — there are no existing models, migrations, or feature tests beyond the Fortify auth scaffolding.

The sidebar currently only has a Dashboard link. We need to add Offer management as the foundation for the AI-powered CV matching workflow.

## Goals / Non-Goals

**Goals:**
- Complete CRUD for job offers with soft deletes (archive/restore/force delete)
- Policy-based authorization ensuring RH agents only access their own offers
- FormRequest validation for clean, testable validation logic
- Modern Flux UI with professional color system (indigo/violet primary, emerald success, amber warning, rose danger)
- Responsive design that works on mobile and desktop
- Comprehensive feature tests covering all CRUD operations and authorization

**Non-Goals:**
- AI-powered CV analysis (separate change)
- PDF upload and RAG ingestion (separate change)
- Multi-role authorization (single RH Agent role for now)
- API endpoints (web-only via Livewire Volt)

## Decisions

### 1. Livewire Volt for all pages
**Decision**: Use Volt single-file components for Index, Create, Show, Edit, and Archived pages.

**Rationale**: Volt keeps PHP logic and Blade templates co-located, reducing file count and context switching. The Flux UI components integrate seamlessly with Volt's Livewire foundation.

**Alternatives considered**: Traditional Livewire components with separate class files — rejected for this simple CRUD because Volt's functional style is more concise.

### 2. Soft deletes with archive/restore pattern
**Decision**: Use Laravel's built-in soft deletes. Archive = soft delete, restore = restore, force delete = permanently delete.

**Rationale**: HR data often needs to be recoverable. The archive pattern allows viewing "deleted" offers and restoring them without data loss. Force delete is a separate, destructive action requiring confirmation.

**Alternatives considered**: Manual `archived_at` timestamp — rejected because soft deletes are built into Eloquent and provide `withTrashed()`, `onlyTrashed()`, and `restore()` out of the box.

### 3. Policy-based authorization
**Decision**: Create `OfferPolicy` with methods for all 7 actions (viewAny, view, create, update, delete, restore, forceDelete).

**Rationale**: Policies are the Laravel convention for model authorization. They integrate with Volt via `$this->authorize()` and are easily testable. Each method checks `user_id` ownership.

**Alternatives considered**: Inline authorization checks in Livewire components — rejected for maintainability and testability.

### 4. FormRequest for validation
**Decision**: Create `OfferRequest` class handling both store and update validation with conditional rules.

**Rationale**: FormRequests keep validation logic out of controllers/components, are reusable, and can include custom validation messages. The `required` vs `sometimes` pattern handles create vs update cleanly.

**Alternatives considered**: Inline validation in Volt components — rejected because it mixes concerns and is harder to test.

### 5. Enum for min_experience_level
**Decision**: Create `ExperienceLevel` enum with TitleCase keys (Junior, Confirmed, Senior, Expert).

**Rationale**: Enums provide type safety, IDE autocomplete, and are the Laravel convention for fixed sets of values. TitleCase keys match the project's naming conventions.

**Alternatives considered**: String constants — rejected because enums are more expressive and castable.

### 6. Enum for employment_type
**Decision**: Create `EmploymentType` enum with TitleCase keys (FullTime, PartTime, Contract, Internship, Temporary).

**Rationale**: Same as ExperienceLevel — type safety and Laravel convention.

### 7. JSON columns for skills arrays
**Decision**: Cast `required_skills` and `soft_skills` to `array` using Eloquent casts.

**Rationale**: MySQL JSON columns allow storing arrays directly. Eloquent casts handle serialization automatically. This avoids a separate skills table for what is essentially tag data.

**Alternatives considered**: Many-to-many relationship with a `skills` table — rejected for complexity. The skills are display-only for now; if search/filtering by individual skill becomes needed, we can migrate later.

### 8. Auto-generated slug from title
**Decision**: Use `Str::slug($title)` in the `boot()` method with unique constraint.

**Rationale**: Slugs provide SEO-friendly URLs and human-readable identifiers. Auto-generation from title ensures consistency.

### 9. Flux UI components
**Decision**: Use `flux:card`, `flux:badge`, `flux:button`, `flux:input`, `flux:select`, `flux:textarea`, `flux:modal` for all UI elements.

**Rationale**: Flux provides consistent, accessible components that match the design system. Using them ensures visual consistency and reduces custom CSS.

### 10. Confirmation modals for destructive actions
**Decision**: Use Flux modals with wire:model for archive, force delete, and restore confirmations.

**Rationale**: Destructive actions require user confirmation to prevent accidental data loss. Modals are the standard UX pattern for this.

## Risks / Trade-offs

- **JSON skills columns** → If we need to query/filter by individual skills later, we'll need to migrate to a relational structure. Mitigation: The current requirement is display-only; we can add indexes or a pivot table later if needed.

- **Single user role** → The policy only checks ownership. If multi-role support is needed later, we'll need to refactor the policy. Mitigation: The policy structure makes this straightforward — add role checks to each method.

- **No API endpoints** → If mobile apps or third-party integrations need offer data, we'll need to add API routes. Mitigation: The model and validation logic are reusable; we just need to add routes and resources.

- **Volt component size** → The Show page with candidates list could grow large. Mitigation: Extract sub-components if needed, but keep it simple for now.
