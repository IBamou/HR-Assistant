## Why

HR agents need a way to submit candidate CVs against specific job offers. Currently there is no intake pipeline — CVs arrive as loose PDFs with no structured storage, no candidate records, and no link to offers. Without this, the AI analysis step (future) has no data to work with. This change builds the foundational ingestion layer: upload → extract → store → link.

## What Changes

- New CV submission page at `/offers/{offer}/submit` with PDF upload and async extraction
- **AI flow**: `CandidateInfoExtractor` agent (laravel/ai, plain prompting — NO `HasStructuredOutput`). Groq provider with `meta-llama/llama-4-scout-17b-16e-instruct`. Extracts full candidate data: name, email, phone, address, summary, skills, experience, education, certifications, languages, projects. Prompt lives in `CandidateInfoExtractorPrompt` class.
- **Extraction pipeline**: LlamaParse v2 API (primary, markdown output) → PdfExtractor (fallback) → Groq AI extraction → cache result
- **Async flow**: `ExtractCandidateInfoJob` dispatched to queue. Livewire polls cache every 2s for results (60s timeout). Retry button on failure clears stale cache and re-dispatches.
- Volt form displays pre-filled fields (name, email, phone, summary, skills, projects, education, languages, experience, certifications) — HR can review and edit
- PDF stored to local disk (`storage/app/private/pdfs/`) with a Document record tracking file metadata
- Extracted text chunked via `Chunker` service and stored in `document_chunks` for later AI analysis
- Candidate records deduplicated by email (find-or-create via `updateOrCreate`)
- Application record links candidate + offer + document
- `AnalyseCVJob` dispatched as placeholder for future CV analysis spec
- 4 new database tables: candidates, applications, documents, document_chunks
- 4 new Eloquent models with relationships
- 3 new service classes: LlamaParseService, PdfExtractor, Chunker
- 1 new AI agent: CandidateInfoExtractor (plain prompting)
- 1 new prompt class: CandidateInfoExtractorPrompt
- 1 new job: ExtractCandidateInfoJob

## Capabilities

### New Capabilities
- `cv-submission`: CV upload, async extraction pipeline (LlamaParse → Groq), AI-powered candidate info extraction, text chunking, candidate/application/document storage, and submission workflow with polling
- `cv-processing`: LlamaParseService, PdfExtractor service, Chunker service, CandidateInfoExtractor AI agent, ExtractCandidateInfoJob — reusable components for CV ingestion pipeline

### Modified Capabilities
- `offer-crud`: Add "Submit CV" button and link on the offer show page, display submitted applications list

## Impact

- **Database**: 4 new tables (candidates, applications, documents, document_chunks)
- **Models**: 4 new models (Candidate, Application, Document, DocumentChunk) + User model (offers relationship already exists)
- **Services**: LlamaParseService (LlamaParse v2 API), PdfExtractor (smalot/pdfparser fallback), Chunker (string splitting), AiClient (centralized AI config), CandidateInfoExtractor (laravel/ai plain prompting agent)
- **Storage**: PDF files stored on local disk (`storage/app/private/pdfs/`)
- **Routes**: 1 new Volt route (`offers/{offer}/submit`), 1 debug route (`info`)
- **Dependencies**: `smalot/pdfparser` for PDF fallback, LlamaParse API (cloud service, no package), Groq API (via laravel/ai)
- **Jobs**: AnalyseCVJob (placeholder), ExtractCandidateInfoJob (async extraction pipeline)
- **Config**: `config/ai.php` (provider, model, timeout), `config/services.php` (llamaindex key/url), `.env` (GROQ_API_KEY, GROQ_BASE_URL, LLAMAPARSE_API_KEY, LLAMAPARSE_BASE_URL)
- **Frontend**: 1 new Volt page with file upload, upload+extract button, polling state, timeout/error handling, retry button, pre-filled candidate form with multiple sections
