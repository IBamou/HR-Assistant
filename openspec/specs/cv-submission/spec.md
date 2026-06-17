# CV Submission

## Purpose

Provide HR agents with a complete CV submission workflow — upload a PDF CV against a job offer, extract candidate information via AI (async), review pre-filled data, and create the candidate/application records.

## Requirements

### Requirement: Submit CV for offer
The system SHALL provide a Submit CV page at /offers/{offer}/submit where an RH agent uploads a PDF CV. The system SHALL dispatch an async extraction job (ExtractCandidateInfoJob), poll for results, display pre-filled fields for review, and create all necessary records on submission.

#### Scenario: Access submit page
- **WHEN** user navigates to /offers/{offer}/submit
- **THEN** system displays the submit form with the offer title

#### Scenario: Upload PDF triggers async extraction
- **WHEN** user selects a PDF file and clicks "Upload & Extract"
- **THEN** system stores the file, dispatches ExtractCandidateInfoJob to the queue, clears stale cache, and starts polling every 2 seconds

#### Scenario: Extraction completes and pre-fills form
- **WHEN** ExtractCandidateInfoJob finishes and cached result is available within 60 seconds
- **THEN** system pre-fills all candidate fields (name, email, phone, address, summary, skills, experience, education, certifications, languages, projects)

#### Scenario: Extraction fails or times out
- **WHEN** ExtractCandidateInfoJob fails or cache is not set within 60 seconds
- **THEN** system displays an error or timeout message, leaves fields empty for manual entry, and shows a retry button

#### Scenario: Retry extraction
- **WHEN** extraction fails and user clicks "Retry Extraction"
- **THEN** system clears stale cache and re-dispatches ExtractCandidateInfoJob

#### Scenario: Submit creates all records
- **WHEN** user reviews pre-filled fields and clicks Submit
- **THEN** system finds or creates Candidate by email, stores PDF to private disk, creates Document, chunks text, stores chunks, creates Application, and dispatches AnalyseCVJob

#### Scenario: Submit with existing candidate email
- **WHEN** user submits a CV with an email that already exists in the candidates table
- **THEN** system links to the existing Candidate record (updateOrCreate updates name/phone if changed)

#### Scenario: Redirect after submission
- **WHEN** submission completes successfully
- **THEN** system redirects to the offer show page with a success flash message

#### Scenario: Validation rejects missing PDF
- **WHEN** user submits without selecting a PDF file
- **THEN** system displays validation error for the cv field

#### Scenario: Validation rejects missing name
- **WHEN** user submits with empty name field
- **THEN** system displays validation error for the name field

#### Scenario: Validation rejects missing email
- **WHEN** user submits with empty email field
- **THEN** system displays validation error for the email field

#### Scenario: User cannot submit CV for another user's offer
- **WHEN** user navigates to /offers/{offer}/submit where offer.user_id does not match auth()->id()
- **THEN** system returns a 403 Forbidden response

#### Scenario: Extraction returns low text from LlamaParse/PdfExtractor
- **WHEN** extracted text is empty or shorter than 50 characters
- **THEN** system displays a warning "Could not extract text from this PDF. The file may be a scanned image. Please enter candidate info manually." and leaves fields empty for manual entry

#### Scenario: Invalid PDF file type
- **WHEN** user selects a file that is not a valid PDF (e.g. renamed .txt)
- **THEN** system rejects the upload with validation error "The cv must be a PDF file."

#### Scenario: Duplicate application rejected with error
- **WHEN** user submits a CV for an offer where the candidate already has an application
- **THEN** system rejects the submission with a validation error "You have already submitted a CV for this offer."

### Requirement: Candidate deduplication by email
The system SHALL find an existing Candidate by email before creating a new one. If a Candidate with the submitted email exists, the system SHALL reuse that record and update name/phone if changed.

#### Scenario: New candidate created
- **WHEN** user submits a CV with an email not in the candidates table
- **THEN** system creates a new Candidate record

#### Scenario: Existing candidate found
- **WHEN** user submits a CV with an email that exists in the candidates table
- **THEN** system uses the existing Candidate record and does not create a duplicate

### Requirement: Document and chunk storage
The system SHALL store the uploaded PDF to disk and create a Document record. The system SHALL chunk the extracted text and store each chunk in the document_chunks table with chunk_index and content.

#### Scenario: PDF stored to disk
- **WHEN** user uploads a PDF CV
- **THEN** system stores the file on the local disk (storage/app/private/pdfs/) with a unique filename and creates a Document record

#### Scenario: Text chunked and stored
- **WHEN** CV text is extracted
- **THEN** system splits text into chunks and stores each chunk in document_chunks with sequential chunk_index values

#### Scenario: Document chunk count updated
- **WHEN** all chunks are stored
- **THEN** system updates the Document.chunk_count to match the number of stored chunks

### Requirement: Application record creation
The system SHALL create an Application record linking candidate_id, offer_id, and document_id. A unique constraint SHALL prevent duplicate applications (same candidate + same offer).

#### Scenario: Application created on submit
- **WHEN** user submits a CV for an offer
- **THEN** system creates an Application record linking the candidate, offer, and document

### Requirement: AnalyseCVJob dispatch
The system SHALL dispatch AnalyseCVJob after successful submission. The job SHALL receive the application_id. The job is a placeholder for the future cv-analysis spec.

#### Scenario: Job dispatched on submit
- **WHEN** submission completes successfully
- **THEN** system dispatches AnalyseCVJob with the application_id

### Requirement: Submit page UI
The system SHALL display a back button to the offer show page, the offer title as heading, a PDF upload field, a loading indicator during extraction, pre-filled editable fields (name, email, phone), and a Submit button.

#### Scenario: Loading indicator during extraction
- **WHEN** user selects a PDF file
- **THEN** system shows a loading indicator while the AI agent extracts candidate info

#### Scenario: Pre-filled fields editable
- **WHEN** extraction completes
- **THEN** system displays name, email, phone fields pre-filled and editable by the user
