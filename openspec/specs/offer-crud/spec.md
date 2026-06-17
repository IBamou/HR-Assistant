# Offer CRUD

## Purpose

Provide HR agents with a complete job offer management workflow — create, view, edit, archive, restore, and permanently delete offers. Offers are user-scoped: each agent manages only their own listings.

## Requirements

### Requirement: Offer model with required fields
The system SHALL provide an Offer model with fields: title (string, required), description (text, required), responsibilities (text), required_skills (JSON array, required), soft_skills (JSON array), min_experience_level (enum: junior, confirmed, senior, expert), education_level (string), employment_type (enum: full_time, part_time, contract, internship, temporary), location (string), slug (auto-generated from title, unique). The model SHALL support soft deletes.

#### Scenario: Create offer with all fields
- **WHEN** user submits a valid offer with all fields
- **THEN** system creates the offer with all fields stored correctly

#### Scenario: Create offer with required fields only
- **WHEN** user submits a valid offer with only title, description, and required_skills
- **THEN** system creates the offer with optional fields as null

#### Scenario: Reject offer with missing required fields
- **WHEN** user submits an offer without title, description, or required_skills
- **THEN** system rejects the request with validation errors for missing fields

#### Scenario: Auto-generate slug from title
- **WHEN** user creates an offer with title "Senior PHP Developer"
- **THEN** system generates slug "senior-php-developer"

#### Scenario: Unique slug constraint
- **WHEN** user creates an offer with title that produces a duplicate slug
- **THEN** system appends a numeric suffix to ensure uniqueness

### Requirement: List active offers
The system SHALL provide an Index page at /offers showing all non-archived offers with title, employment_type, location, required_skills badges, and created_at date. The page SHALL include a search input to filter by title. Each offer card SHALL show an "Archived" badge count or similar indicator.

#### Scenario: Display active offers
- **WHEN** user navigates to /offers
- **THEN** system displays all non-archived offers owned by the user

#### Scenario: Search offers by title
- **WHEN** user enters "Laravel" in the search input
- **THEN** system filters offers to show only those with "Laravel" in the title

#### Scenario: No offers exist
- **WHEN** user navigates to /offers with no offers
- **THEN** system displays an empty state message

### Requirement: Create new offer
The system SHALL provide a Create page at /offers/create with a form containing all offer fields. The form SHALL use Flux UI components (input, textarea, select). Required_skills and soft_skills SHALL be entered as tags with add/remove functionality. The form SHALL submit to store the offer and redirect to the show page.

#### Scenario: Submit valid offer form
- **WHEN** user fills all required fields and clicks submit
- **THEN** system creates the offer and redirects to the show page

#### Scenario: Add skill tags
- **WHEN** user types "PHP" and presses Enter in the required_skills field
- **THEN** system adds a "PHP" badge tag and clears the input

#### Scenario: Remove skill tag
- **WHEN** user clicks the dismiss button on a "PHP" skill badge
- **THEN** system removes the "PHP" tag from the list

#### Scenario: Form validation errors
- **WHEN** user submits form with empty title
- **THEN** system displays validation error under the title field

### Requirement: View offer details
The system SHALL provide a Show page at /offers/{offer} displaying all offer fields with formatted labels. Required_skills and soft_skills SHALL be displayed as colored badges. The page SHALL include Edit, Archive, and Submit CV buttons. The page SHALL display a list of submitted applications with candidate name and submission date.

#### Scenario: Display offer details
- **WHEN** user navigates to /offers/{offer}
- **THEN** system displays all offer fields with proper formatting

#### Scenario: Display skill badges
- **WHEN** offer has required_skills ["PHP", "Laravel", "MySQL"]
- **THEN** system displays three colored badges for each skill

#### Scenario: Edit button navigation
- **WHEN** user clicks the Edit button
- **THEN** system navigates to /offers/{offer}/edit

#### Scenario: Archive button triggers confirmation
- **WHEN** user clicks the Archive button
- **THEN** system displays a confirmation modal before archiving

#### Scenario: Submit CV button navigation
- **WHEN** user clicks the Submit CV button
- **THEN** system navigates to /offers/{offer}/submit

#### Scenario: Display submitted applications
- **WHEN** offer has submitted applications
- **THEN** system displays a list of applications with candidate name and submission date

### Requirement: Edit existing offer
The system SHALL provide an Edit page at /offers/{offer}/edit with a pre-filled form matching the Create page layout. The form SHALL submit via PUT to update the offer and redirect to the show page.

#### Scenario: Load pre-filled form
- **WHEN** user navigates to /offers/{offer}/edit
- **THEN** system displays form with all fields populated with current values

#### Scenario: Update offer successfully
- **WHEN** user modifies fields and clicks submit
- **THEN** system updates the offer and redirects to the show page

#### Scenario: Update only some fields
- **WHEN** user changes only the title
- **THEN** system updates only the title field

### Requirement: Archive offer with soft delete
The system SHALL archive an offer via DELETE /offers/{offer} which sets deleted_at timestamp. Archived offers SHALL NOT appear in the active offers list. The system SHALL display a confirmation modal before archiving.

#### Scenario: Archive offer successfully
- **WHEN** user confirms archive in the modal
- **THEN** system sets deleted_at timestamp and redirects to the offers index

#### Scenario: Archived offer not in active list
- **WHEN** an offer is archived
- **THEN** the offer does not appear on /offers page

### Requirement: View archived offers
The system SHALL provide an Archived page at /offers/archived showing only soft-deleted offers. Each archived offer SHALL have Restore and Force Delete buttons. The page SHALL display a confirmation modal before force delete.

#### Scenario: Display archived offers
- **WHEN** user navigates to /offers/archived
- **THEN** system displays only soft-deleted offers

#### Scenario: No archived offers
- **WHEN** user navigates to /offers/archived with no archived offers
- **THEN** system displays an empty state message

### Requirement: Restore archived offer
The system SHALL restore an archived offer via POST /offers/{offer}/restore which clears deleted_at timestamp. The restored offer SHALL reappear in the active offers list.

#### Scenario: Restore offer successfully
- **WHEN** user clicks Restore on an archived offer
- **THEN** system clears deleted_at and redirects to the restored offer's show page

#### Scenario: Restored offer appears in active list
- **WHEN** an offer is restored
- **THEN** the offer appears on /offers page

### Requirement: Force delete offer
The system SHALL permanently delete an archived offer via DELETE /offers/{offer}/force. This action SHALL NOT be reversible. The system SHALL display a confirmation modal before force deleting.

#### Scenario: Force delete offer successfully
- **WHEN** user confirms force delete in the modal
- **THEN** system permanently removes the offer from the database

#### Scenario: Force delete only works on archived offers
- **WHEN** user attempts to force delete an active (non-archived) offer
- **THEN** system returns a 404 error

### Requirement: Policy-based authorization
The system SHALL enforce that RH agents can only manage their own offers. The OfferPolicy SHALL check user_id ownership on all actions. Unauthorized access attempts SHALL receive a 403 Forbidden response.

#### Scenario: User accesses own offer
- **WHEN** user navigates to /offers/{offer} where offer.user_id matches auth()->id()
- **THEN** system displays the offer

#### Scenario: User accesses another user's offer
- **WHEN** user navigates to /offers/{offer} where offer.user_id does not match auth()->id()
- **THEN** system returns a 403 Forbidden response

#### Scenario: User creates offer
- **WHEN** user submits a valid offer form
- **THEN** system sets offer.user_id to auth()->id()

### Requirement: FormRequest validation
The system SHALL use OfferRequest form request class for store and update validation. Validation rules SHALL enforce: title required|string|max:255, description required|string|max:5000, required_skills required|array|min:1, soft_skills array, min_experience_level nullable|in:junior,confirmed,senior,expert, employment_type nullable|in:full_time,part_time,contract,internship,temporary.

#### Scenario: Store validation passes
- **WHEN** user submits offer with valid data
- **THEN** OfferRequest validates successfully

#### Scenario: Store validation fails on required_skills empty array
- **WHEN** user submits offer with required_skills as empty array
- **THEN** OfferRequest returns validation error

#### Scenario: Update validation with optional fields
- **WHEN** user updates offer without providing soft_skills
- **THEN** OfferRequest allows the update (soft_skills is nullable)

### Requirement: Sidebar navigation
The system SHALL add an "Offers" navigation item with document-text icon in the sidebar under the Platform group. The sidebar SHALL include a sub-link for "Archives" that navigates to /offers/archived.

#### Scenario: Display Offers link in sidebar
- **WHEN** user views the sidebar
- **THEN** system displays an "Offers" link with document-text icon

#### Scenario: Navigate to offers index
- **WHEN** user clicks the "Offers" link
- **THEN** system navigates to /offers

#### Scenario: Navigate to archived offers
- **WHEN** user clicks the "Archives" sub-link
- **THEN** system navigates to /offers/archived

### Requirement: Responsive design
All offer pages SHALL be responsive and display correctly on mobile, tablet, and desktop viewports. Forms SHALL stack vertically on mobile. Tables/cards SHALL adapt to screen width.

#### Scenario: Mobile viewport rendering
- **WHEN** user views /offers on a 375px width viewport
- **THEN** system displays offers as stacked cards

#### Scenario: Desktop viewport rendering
- **WHEN** user views /offers on a 1280px width viewport
- **THEN** system displays offers in a grid layout

### Requirement: Feature tests
The system SHALL include Pest feature tests covering: full CRUD operations, archive/restore/force delete, FormRequest validation, Policy enforcement, user scoping (users cannot access other users' offers). Tests SHALL use OfferFactory for data generation.

#### Scenario: Test create offer
- **WHEN** test runs "user can create offer with valid data"
- **THEN** test passes with offer created in database

#### Scenario: Test unauthorized access
- **WHEN** test runs "user cannot access another user's offer"
- **THEN** test passes with 403 response

#### Scenario: Test archive and restore cycle
- **WHEN** test runs "user can archive and restore offer"
- **THEN** test passes with offer archived then restored
