## ADDED Requirements

### Requirement: DoclingService (self-hosted PDF extraction)
The system SHALL provide a DoclingService that calls a self-hosted FastAPI microservice to extract markdown text from PDF files. The service SHALL use the Laravel HTTP client (`Http::attach()`) to send a multipart POST request to the configured `services.docling.url` + `/parse` endpoint. The service SHALL time out after 120 seconds. The endpoint SHALL return `{success: boolean, content: string}`.

#### Scenario: Extract markdown from PDF via Docling
- **WHEN** DoclingService receives a valid PDF file path and the microservice is available
- **THEN** service returns content with status=completed

#### Scenario: Docling microservice unavailable
- **WHEN** DoclingService is called and the microservice returns a 5xx error or connection fails
- **THEN** service returns status=unavailable

#### Scenario: Docling microservice invalid response
- **WHEN** DoclingService receives a non-JSON response or missing content field
- **THEN** service returns status=failed with an error message

### Requirement: Extractor interface and implementations
The system SHALL provide a `Contracts\Extractor` interface with `isAvailable(): bool` and `extract(string $filePath): ExtractionResult` methods. Three implementations SHALL exist: `DoclingExtractor`, `LlamaParseExtractor`, and `LocalPdfExtractor`. Each SHALL be independently testable.

#### Scenario: DoclingExtractor isAvailable returns true when healthy
- **WHEN** DoclingExtractor::isAvailable() is called and the microservice responds to GET /health with 200
- **THEN** method returns true

#### Scenario: DoclingExtractor isAvailable returns false when unhealthy
- **WHEN** DoclingExtractor::isAvailable() is called and the microservice is unreachable
- **THEN** method returns false

#### Scenario: LlamaParseExtractor isAvailable checks API key
- **WHEN** LlamaParseExtractor::isAvailable() is called and LLAMAPARSE_API_KEY is set
- **THEN** method returns true

#### Scenario: LlamaParseExtractor returns pending on async start
- **WHEN** LlamaParseExtractor::extract() is called and the parse job starts successfully
- **THEN** method returns ExtractionResult with status=pending and no content

#### Scenario: LocalPdfExtractor extracts text from PDF
- **WHEN** LocalPdfExtractor::extract() receives a valid PDF file path
- **THEN** method returns ExtractionResult with status=completed and cleaned text

### Requirement: ExtractionResult DTO
The system SHALL provide an `ExtractionResult` DTO with typed properties: `content: string`, `extractorName: string`, `errorMessage: ?string`. The DTO SHALL expose named constructors `completed()`, `pending()`, `failed()`, and helper method `isEmpty(): bool`. Status constants SHALL be: `COMPLETED`, `PENDING`, `FAILED`, `UNAVAILABLE`.

#### Scenario: ExtractionResult::completed returns completed result
- **WHEN** ExtractionResult::completed('text', 'DoclingExtractor') is called
- **THEN** instance has status=COMPLETED, content='text', extractorName='DoclingExtractor'

#### Scenario: ExtractionResult::isEmpty returns true for empty content
- **WHEN** ExtractionResult::completed('', 'LocalPdfExtractor') is called
- **THEN** isEmpty() returns true

#### Scenario: ExtractionResult::pending returns pending result
- **WHEN** ExtractionResult::pending('LlamaParseExtractor') is called
- **THEN** instance has status=PENDING and content=''

### Requirement: ExtractionOrchestrator (chain of extractors)
The system SHALL provide an `ExtractionOrchestrator` that accepts an ordered list of extractors in its constructor. The `extract(string $filePath): ExtractionResult` method SHALL iterate through extractors: skip unavailable ones, return the first completed result. If an extractor returns pending, the orchestrator SHALL stop the chain and return pending. If an extractor returns failed, the orchestrator SHALL log a warning and try the next extractor. If all extractors fail, the orchestrator SHALL return failed.

#### Scenario: Orchestrator returns first successful extraction
- **WHEN** Orchestrator runs with DoclingExtractor(available, success) and LlamaParseExtractor(available)
- **THEN** returns DoclingExtractor's result and never calls LlamaParseExtractor

#### Scenario: Orchestrator skips unavailable extractors
- **WHEN** Orchestrator runs with DoclingExtractor(unavailable) and LlamaParseExtractor(available, success)
- **THEN** returns LlamaParseExtractor's result

#### Scenario: Orchestrator stops chain on pending
- **WHEN** Orchestrator runs with DoclingExtractor(unavailable) and LlamaParseExtractor(pending)
- **THEN** returns pending and never calls next extractor

#### Scenario: Orchestrator tries next on failure
- **WHEN** Orchestrator runs with DoclingExtractor(failed) and LlamaParseExtractor(available, success)
- **THEN** returns LlamaParseExtractor's result

#### Scenario: Orchestrator returns failed when all extractors fail
- **WHEN** Orchestrator runs with all extractors returning failed
- **THEN** returns ExtractionResult with status=FAILED

### Requirement: Docling microservice (Python FastAPI)
The system SHALL provide a Python FastAPI microservice in `docling-service/` with a `POST /parse` endpoint that accepts a multipart PDF upload and returns extracted markdown text. The endpoint SHALL use Docling to parse the PDF. The endpoint SHALL time out after 300 seconds for large documents. The microservice SHALL also expose a `GET /health` endpoint returning `{status: "ok"}`.

#### Scenario: Parse valid PDF
- **WHEN** POST /parse receives a valid PDF file
- **THEN** returns 200 with `{success: true, content: "<markdown_text>"}`

#### Scenario: Parse invalid file
- **WHEN** POST /parse receives a non-PDF file
- **THEN** returns 400 with `{success: false, error: "Invalid file type"}`

#### Scenario: Health check
- **WHEN** GET /health is called
- **THEN** returns 200 with `{status: "ok"}`
