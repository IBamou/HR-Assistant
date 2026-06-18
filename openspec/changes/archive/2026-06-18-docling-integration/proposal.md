## Why

LlamaParse costs $3 per 1,000 pages and is a paid cloud dependency. Replacing it with a self-hosted Docling microservice cuts costs to zero, teaches microservice architecture (Python FastAPI + Docling), and improves the extraction layer with clean separation of concerns.

## What Changes

- New `docling-service/` directory with a self-hosted FastAPI microservice (Python + Docling)
- Refactor extraction into a clean architecture with an interface, DTOs, three extractors, and an orchestrator
- `ExtractionOrchestrator` chains Docling → LlamaParse → LocalPdfExtractor automatically
- `DoclingExtractor` — new primary extractor, calls FastAPI `/parse` endpoint
- `LlamaParseExtractor` — existing cloud API extracted into its own class (kept as paid fallback)
- `LocalPdfExtractor` — renamed from `PdfExtractor`, implements the same interface
- `ExtractionResult` DTO — typed result object with status constants
- `ExtractCandidateInfoJob` — simplified, delegates all extraction logic to the orchestrator
- Debug route updated to test Docling alongside existing extractors
- New config `services.docling.url` for microservice URL

## Capabilities

### New Capabilities
- `docling-extraction`: Self-hosted PDF extraction using a Python Docling microservice, accessible via a Laravel HTTP client adapter

### Modified Capabilities
- `cv-processing`: Extraction pipeline refactored from a single `LlamaParseService` + `PdfExtractor` into an interface-based chain of three extractors orchestrated by `ExtractionOrchestrator`. Fallback strategy formalized. Existing spec behavior preserved.

## Impact

- **New dependency**: Python 3.12+, Docling, FastAPI, Uvicorn (separate microservice, not in composer)
- **Modified files**: `ExtractCandidateInfoJob`, `routes/web.php`, `config/services.php`, `.env`, `.env.example`, `AppServiceProvider`
- **Deleted/moved**: `PdfExtractor.php` → `LocalPdfExtractor.php`
- **New files**: 10 PHP files (interface, DTOs, 3 extractors, orchestrator, exception) + 3 microservice files + tests
- **No changes**: Database schema, migrations, models, Volt/Blade templates, Groq prompts, `AiClient`, `CandidateInfoExtractor` agent
