## ADDED Requirements

### Requirement: Recommandation enum
The system SHALL provide a `Recommandation` backed string enum with cases `Convoquer` (value: `convoquer`), `Attente` (value: `attente`), and `Rejeter` (value: `rejeter`). The enum SHALL have a `label()` method returning French display labels. The `Analysis` model SHALL cast the `recommendation` column to this enum.

#### Scenario: Convoquer has correct value and label
- **WHEN** Recommandation::Convoquer is used
- **THEN** its value is `convoquer` and label() returns `Convoquer`

#### Scenario: Attente has correct value and label
- **WHEN** Recommandation::Attente is used
- **THEN** its value is `attente` and label() returns `Attente`

#### Scenario: Rejeter has correct value and label
- **WHEN** Recommandation::Rejeter is used
- **THEN** its value is `rejeter` and label() returns `Rejeter`

### Requirement: CvScorerPrompt (AI prompt for CV scoring)
The system SHALL provide a `CvScorerPrompt` class with a `build(): string` method returning a prompt that instructs the AI to analyze a CV against a job offer. The output SHALL be valid JSON only with the schema: `matching_score` (0-100 integer), `extracted_skills` (array of strings), `missing_skills` (array of strings), `strengths` (string), `gaps` (string), `recommendation` (one of Convoquer, Attente, Rejeter), `justification` (string). Scoring rules: 70+ → Convoquer, 40-69 → Attente, <40 → Rejeter.

#### Scenario: Prompt contains JSON schema keys
- **WHEN** CvScorerPrompt::build() is called
- **THEN** the returned string contains `matching_score`, `extracted_skills`, `missing_skills`, `strengths`, `gaps`, `recommendation`, `justification`

#### Scenario: Prompt mentions offer context
- **WHEN** CvScorerPrompt::build() is called
- **THEN** the returned string contains instructions about the job offer requirements

### Requirement: CvScorer AI agent
The system SHALL provide a `CvScorer` agent implementing `Agent` (with `Promptable`) that uses `CvScorerPrompt` for its instructions. The agent SHALL follow the same pattern as `CandidateInfoExtractor`.

#### Scenario: CvScorer is instantiable
- **WHEN** CvScorer is constructed
- **THEN** it is an instance of Agent

### Requirement: AnalyseCVJob (AI-powered CV scoring)
The system SHALL rewrite `AnalyseCVJob` to load the `Application` with its `candidate` and `offer` relationships. It SHALL build a prompt combining the candidate's extracted CV text and the offer's requirements/description. It SHALL call `CvScorer` via `AiClient::prompt()`, parse the JSON response, and update the `Analysis` record with all scoring fields and `status = ProcessStatus::Processed`. On any failure, it SHALL set `status = ProcessStatus::Failed`. The job SHALL not use `release()` — single attempt.

#### Scenario: Stores analysis on success
- **WHEN** AnalyseCVJob runs with a valid Application containing extracted text
- **THEN** the Analysis record has status=Processed, matching_score set, recommendation set

#### Scenario: Stores failed status on AI error
- **WHEN** AnalyseCVJob runs and the AI call throws an exception
- **THEN** the Analysis record has status=Failed

#### Scenario: Handles missing candidate gracefully
- **WHEN** AnalyseCVJob runs and the Application has no candidate
- **THEN** the job returns without error

### Requirement: Analysis badges on offers.show
The offers.show view SHALL eager-load the `analysis` relationship on each application and display a badge per application indicating analysis status: pending (grey, "Analyzing..."), processed (colored badge with matching_score and recommendation label), failed (rose, "Failed"). Each application row SHALL be clickable to open a modal showing the full analysis detail: matching_score, recommendation badge, extracted_skills (indigo badges), missing_skills (rose badges), strengths, gaps, justification.

#### Scenario: Pending analysis shows grey badge
- **WHEN** an application has an analysis with status=Pending
- **THEN** the view shows a grey "Analyzing..." badge

#### Scenario: Processed analysis shows score badge
- **WHEN** an application has an analysis with status=Processed
- **THEN** the view shows a colored badge with the score and recommendation label

#### Scenario: Failed analysis shows failed badge
- **WHEN** an application has an analysis with status=Failed
- **THEN** the view shows a rose "Failed" badge
