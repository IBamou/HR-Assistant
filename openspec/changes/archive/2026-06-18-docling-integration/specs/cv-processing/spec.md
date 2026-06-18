## MODIFIED Requirements

### Requirement: LlamaParseService → LlamaParseExtractor (primary cloud PDF extraction)
The system SHALL provide a `LlamaParseExtractor` implementing the `Extractor` interface that extracts markdown text from PDF files via the LlamaParse v2 API. The extractor SHALL upload files via multipart POST, start a parse job, poll for completion, and return an `ExtractionResult` DTO. The LlamaParseService class SHALL be kept as an internal dependency of `LlamaParseExtractor`. The extractor SHALL manage its own cache keys for job IDs.

#### Scenario: Extract markdown from PDF via LlamaParse
- **WHEN** LlamaParseExtractor receives a valid PDF file path and the API is available
- **THEN** extractor returns ExtractionResult with status=COMPLETED and extracted markdown

#### Scenario: LlamaParseExtractor isAvailable returns false when no API key
- **WHEN** LlamaParseExtractor::isAvailable() is called and LLAMAPARSE_API_KEY is missing
- **THEN** method returns false

#### Scenario: LlamaParseExtractor times out
- **WHEN** LlamaParseExtractor::extract() is called and the parse job does not complete within 60 seconds
- **THEN** extractor returns ExtractionResult with status=FAILED and error message

### Requirement: PdfExtractor → LocalPdfExtractor (fallback extraction)
The system SHALL provide a `LocalPdfExtractor` implementing the `Extractor` interface that reads a PDF file using smalot/pdfparser and returns extracted text via an `ExtractionResult` DTO. This replaces the previous `PdfExtractor` service.

#### Scenario: Extract text from PDF
- **WHEN** LocalPdfExtractor::extract() receives a valid PDF file path
- **THEN** method returns ExtractionResult with status=COMPLETED and cleaned text content

#### Scenario: Handle empty PDF
- **WHEN** LocalPdfExtractor receives a PDF with no text content
- **THEN** method returns ExtractionResult with status=COMPLETED and empty content (isEmpty() is true)

#### Scenario: Handle non-PDF file
- **WHEN** LocalPdfExtractor receives a file that is not a valid PDF
- **THEN** method returns ExtractionResult with status=FAILED

### Requirement: ExtractCandidateInfoJob (async extraction pipeline via orchestrator)
The system SHALL provide `ExtractCandidateInfoJob` that delegates all PDF extraction to `ExtractionOrchestrator`. The job SHALL call `app(ExtractionOrchestrator::class)->extract($this->cvPath)`. If the result is pending, the job SHALL release back to the queue with a 5-second delay. If the result is failed, the job SHALL cache the error and return. If the result is empty, the job SHALL cache a warning and return. Otherwise, the job SHALL proceed with truncation safeguard → Groq extraction → cache result. All LlamaParse job ID cache management is removed from the job.

#### Scenario: Successful full pipeline via orchestrator
- **WHEN** ExtractCandidateInfoJob runs and orchestrator returns completed result
- **THEN** job proceeds with truncateText → Groq → stores result in cache

#### Scenario: Orchestrator returns pending, job releases
- **WHEN** ExtractCandidateInfoJob runs and orchestrator returns pending
- **THEN** job releases back to queue with 5-second delay

#### Scenario: Orchestrator returns failed, job caches error
- **WHEN** ExtractCandidateInfoJob runs and orchestrator returns failed
- **THEN** job caches error in cache with key cv_extraction_error_{md5}

#### Scenario: Orchestrator returns empty, job caches warning
- **WHEN** ExtractCandidateInfoJob runs and orchestrator returns completed with empty content
- **THEN** job caches warning in cache

### Requirement: ExtractionOrchestrator (chain of extractors)
The system SHALL provide an `ExtractionOrchestrator` that chains DoclingExtractor → LlamaParseExtractor → LocalPdfExtractor in that order. The orchestrator SHALL be registered in the service container. The default order SHALL be configurable.

#### Scenario: Orchestrator runs default chain
- **WHEN** ExtractionOrchestrator::extract() is called
- **THEN** it tries DoclingExtractor first, then LlamaParseExtractor, then LocalPdfExtractor

### Requirement: ExtractionResult DTO
The system SHALL replace raw array returns with `ExtractionResult` DTO throughout the extraction pipeline.

#### Scenario: ExtractionResult provides status helpers
- **WHEN** any extractor returns an ExtractionResult
- **THEN** the caller can check isCompleted(), isPending(), isFailed(), isUnavailable(), isEmpty()
