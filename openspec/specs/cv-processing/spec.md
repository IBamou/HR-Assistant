# CV Processing

## Purpose

Provide PDF-to-structured-data extraction services for CV documents. The system extracts markdown text from PDFs using a chain of extractors (Docling → LlamaParse → LocalPdfExtractor) and then uses AI (Groq) to extract structured candidate information via plain prompting. Text is chunked for downstream analysis.

## Requirements

### Requirement: LlamaParseExtractor (cloud PDF extraction)
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

### Requirement: LocalPdfExtractor (fallback extraction)
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

### Requirement: Chunker service
The system SHALL provide a Chunker service that splits text into chunks. The service SHALL split by sentences using the regex pattern /(?<=[.!?])\s+/. The service SHALL build chunks of approximately $chunkSize words (default 500) with $overlap word overlap (default 100). The service SHALL never split mid-sentence.

#### Scenario: Split text into chunks
- **WHEN** Chunker receives a text with 1500 words
- **THEN** service returns approximately 3-4 chunks of ~500 words each

#### Scenario: Respect sentence boundaries
- **WHEN** Chunker receives text with multiple sentences
- **THEN** service does not split any chunk mid-sentence

#### Scenario: Apply overlap between chunks
- **WHEN** Chunker splits text into chunks
- **THEN** consecutive chunks share approximately 100 words of overlap

#### Scenario: Handle short text
- **WHEN** Chunker receives text shorter than chunkSize
- **THEN** service returns a single chunk containing all the text

#### Scenario: Handle empty string
- **WHEN** Chunker receives an empty string
- **THEN** service returns an empty array

### Requirement: CandidateInfoExtractor AI agent (plain prompting)
The system SHALL provide a CandidateInfoExtractor agent implementing Agent (without HasStructuredOutput). The agent SHALL use plain prompting with instructions that describe the expected JSON output shape. The agent SHALL use the Groq provider with the meta-llama/llama-4-scout-17b-16e-instruct model. The system SHALL use a dedicated CandidateInfoExtractorPrompt class that builds a comprehensive prompt with explicit extraction instructions. The system SHALL parse the raw text response via parseJsonResponse() which strips markdown code blocks and extracts JSON.

#### Scenario: Extract full candidate data from CV text
- **WHEN** CandidateInfoExtractor receives CV markdown text
- **THEN** agent returns JSON with: name, email, phone, address, summary, skills, experience (array), education, certifications, languages, projects (array), other_sections

#### Scenario: Groq returns markdown-wrapped JSON
- **WHEN** CandidateInfoExtractor receives a response wrapped in ```json blocks
- **THEN** parseJsonResponse() strips markdown and extracts valid JSON

#### Scenario: AI provider unavailable
- **WHEN** CandidateInfoExtractor is called and the AI provider times out or returns an error
- **THEN** agent throws an exception (caller handles the error and displays a warning to the user)

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

#### Scenario: Very large CV text truncated
- **WHEN** CV text exceeds MAX_INPUT_LENGTH (30000 characters)
- **THEN** job truncates text and appends truncation notice before sending to Groq

### Requirement: AiClient (centralized AI configuration)
The system SHALL provide an AiClient service that reads provider, model, and timeout from config/ai.php. The client SHALL create the appropriate AI agent client (groq with API key and base URL) and call prompt() on it. This abstraction allows changing provider/model without modifying extraction code.

#### Scenario: Prompt returns AI response
- **WHEN** AiClient::prompt() is called with an agent and input text
- **THEN** client configures Groq with model and timeout from config and returns the raw text response
