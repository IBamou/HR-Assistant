## 1. Database & Model

- [x] 1.1 Create migration for offers table with all fields (title, description, responsibilities, required_skills, soft_skills, min_experience_level, education_level, employment_type, location, slug, user_id, timestamps, soft deletes)
- [x] 1.2 Create ExperienceLevel enum (Junior, Confirmed, Senior, Expert)
- [x] 1.3 Create EmploymentType enum (FullTime, PartTime, Contract, Internship, Temporary)
- [x] 1.4 Create Offer model with casts (required_skills → array, soft_skills → array, min_experience_level → ExperienceLevel, employment_type → EmploymentType)
- [x] 1.5 Add boot() method to auto-generate unique slug from title
- [x] 1.6 Add user() relationship on Offer model
- [x] 1.7 Add User model hasMany(Offer::class) relationship

## 2. Authorization & Validation

- [x] 2.1 Create OfferPolicy with all 7 methods (viewAny, view, create, update, delete, restore, forceDelete)
- [x] 2.2 Register OfferPolicy in AuthServiceProvider
- [x] 2.3 Create OfferRequest form request with store rules (title required, description required, required_skills required|array|min:1, soft_skills array, min_experience_level nullable|in, employment_type nullable|in, education_level nullable, location nullable)
- [x] 2.4 Create OfferRequest with update rules (same fields, use sometimes for partial updates)

## 3. Volt Pages — Create & Store

- [x] 3.1 Create resources/views/livewire/offers/ directory
- [x] 3.2 Create Create volt page at resources/views/livewire/offers/create.blade.php with form (title, description, responsibilities, required_skills with tag input, soft_skills with tag input, min_experience_level select, education_level input, employment_type select, location input)
- [x] 3.3 Implement skill tag add/remove functionality with Alpine.js
- [x] 3.4 Wire form submission to store action with OfferRequest validation and auth()->user()->offers()->create()
- [x] 3.5 Add routes with named routes in routes/web.php: `offers.create` (GET /offers/create), `offers.store` (POST /offers) — all inside auth middleware group

## 4. Volt Pages — Index

- [x] 4.1 Create Index volt page at resources/views/livewire/offers/index.blade.php with search input and offer cards grid
- [x] 4.2 Display offer cards with title, employment_type, location, required_skills badges, created_at
- [x] 4.3 Add search functionality filtering by title
- [x] 4.4 Add empty state for no offers
- [x] 4.5 Add route: `offers.index` (GET /offers) in routes/web.php

## 5. Volt Pages — Show

- [x] 5.1 Create Show volt page at resources/views/livewire/offers/show.blade.php with all offer details
- [x] 5.2 Display required_skills and soft_skills as colored badges
- [x] 5.3 Add Edit button linking to /offers/{offer}/edit
- [x] 5.4 Add Archive button with confirmation modal
- [x] 5.5 Add candidate list placeholder section (empty state — actual candidate integration is future)
- [x] 5.6 Add route: `offers.show` (GET /offers/{offer}) in routes/web.php

## 6. Volt Pages — Edit

- [ ] 6.1 Create Edit volt page at resources/views/livewire/offers/edit.blade.php with pre-filled form
- [ ] 6.2 Wire form submission to update action with OfferRequest validation
- [ ] 6.3 Add routes: `offers.edit` (GET /offers/{offer}/edit), `offers.update` (PUT /offers/{offer}) in routes/web.php

## 7. Archive, Restore & Force Delete

- [x] 7.1 Add archive action in Show volt page (DELETE /offers/{offer} with confirmation modal)
- [x] 7.2 Create Archived volt page at resources/views/livewire/offers/archived.blade.php listing soft-deleted offers
- [x] 7.3 Add Restore button with POST /offers/{offer}/restore action
- [x] 7.4 Add Force Delete button with confirmation modal (DELETE /offers/{offer}/force)
- [x] 7.5 Add routes: `offers.archived` (GET /offers/archived), `offers.restore` (POST /offers/{offer}/restore), `offers.forceDelete` (DELETE /offers/{offer}/force)

## 8. Sidebar Navigation & Route Registration

- [ ] 8.1 Wrap all offer routes in auth middleware group in routes/web.php
- [x] 8.2 Add "Offers" navigation item with document-text icon in sidebar.blade.php under Platform group, using `route('offers.index')`
- [x] 8.3 Add "Archives" sub-link navigating to `route('offers.archived')`

## 9. Factory & Tests

- [x] 9.1 Create OfferFactory with realistic fake data (title, description, skills, etc.)
- [x] 9.2 Create Feature/OfferCrudTest.php with create offer test
- [x] 9.3 Add index offers test (displays active offers, search works)
- [x] 9.4 Add show offer test
- [x] 9.5 Add update offer test
- [x] 9.6 Add archive/restore/force delete tests
- [x] 9.7 Add validation tests (missing required fields rejected)
- [x] 9.8 Add policy tests (user cannot access other user's offers)
- [ ] 9.9 Run php artisan test --compact and ensure all tests pass

## 10. Final Verification

- [ ] 10.1 Run vendor/bin/pint --dirty --format agent to apply code formatting
- [ ] 10.2 Run php artisan test --compact to verify all tests pass
- [ ] 10.3 Verify all pages render correctly in browser
