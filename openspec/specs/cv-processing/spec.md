# CV Processing

## Purpose

Provide PDF-to-structured-data extraction services for CV documents. The system extracts markdown text from PDFs using LlamaParse (primary) or PdfExtractor (fallback), then uses AI (Groq) to extract structured candidate information via plain prompting. Text is chunked for downstream analysis.

## Requirements

### Requirement: LlamaParseService (primary PDF extraction)
The system SHALL provide a LlamaParseService that extracts markdown text from PDF files via the LlamaParse v2 API. The service SHALL upload files via multipart POST to /api/v1/beta/files, start a parse job via POST /api/v2/parse with tier=cost_effective, and poll for completion via GET /api/v2/parse/{id}?expand=markdown_full. The service SHALL fall back to PdfExtractor if LlamaParse is unavailable or times out.

#### Scenario: Extract markdown from PDF
- **WHEN** LlamaParseService receives a valid PDF file path and the API is available
- **THEN** service returns extracted markdown text with status=success

#### Scenario: LlamaParse unavailable
- **WHEN** LlamaParseService is called and the API key is missing or empty
- **THEN** service returns status=unavailable

#### Scenario: LlamaParse times out
- **WHEN** LlamaParseService is called and the parse job does not complete within 60 seconds
- **THEN** service returns status=timeout with an error message

### Requirement: PdfExtractor service (fallback extraction)
The system SHALL provide a PdfExtractor service that reads a PDF file and returns extracted text. The service SHALL use smalot/pdfparser. The service SHALL clean extracted text by collapsing whitespace and trimming. PdfExtractor is the fallback when LlamaParse is unavailable or fails.

#### Scenario: Extract text from PDF
- **WHEN** PdfExtractor receives a valid PDF file path
- **THEN** service returns cleaned text content from the PDF

#### Scenario: Handle empty PDF
- **WHEN** PdfExtractor receives a PDF with no text content
- **THEN** service returns an empty string

#### Scenario: Handle non-PDF file
- **WHEN** PdfExtractor receives a file that is not a valid PDF
- **THEN** service throws an exception (caller handles the error)

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

### Requirement: ExtractCandidateInfoJob (async extraction pipeline)
The system SHALL provide ExtractCandidateInfoJob that orchestrates the full extraction pipeline: LlamaParse (extract markdown from PDF) → optional truncation safeguard → Groq (extract structured candidate data) → cache result. The job SHALL run on the queue. Results SHALL be stored in cache with key cv_extraction_{md5 of path} and 300s TTL.

#### Scenario: Successful full pipeline
- **WHEN** ExtractCandidateInfoJob runs with a valid PDF path and LlamaParse is available
- **THEN** job runs LlamaParse → truncateText if needed → Groq → stores result in cache

#### Scenario: LlamaParse unavailable, falls back to PdfExtractor
- **WHEN** ExtractCandidateInfoJob runs and LlamaParse returns unavailable
- **THEN** job falls back to PdfExtractor → truncateText → Groq → stores result in cache

#### Scenario: Very large CV text truncated
- **WHEN** CV text exceeds MAX_INPUT_LENGTH (30000 characters)
- **THEN** job truncates text and appends truncation notice before sending to Groq

### Requirement: AiClient (centralized AI configuration)
The system SHALL provide an AiClient service that reads provider, model, and timeout from config/ai.php. The client SHALL create the appropriate AI agent client (groq with API key and base URL) and call prompt() on it. This abstraction allows changing provider/model without modifying extraction code.

#### Scenario: Prompt returns AI response
- **WHEN** AiClient::prompt() is called with an agent and input text
- **THEN** client configures Groq with model and timeout from config and returns the raw text response
