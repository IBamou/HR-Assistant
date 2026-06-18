## 1. Docling Microservice

- [x] 1.1 Create `docling-service/` directory with `main.py`, `requirements.txt`, and `Dockerfile`
- [x] 1.2 Implement `POST /parse` endpoint (multipart PDF upload → Docling → markdown response)
- [x] 1.3 Implement `GET /health` endpoint
- [x] 1.4 Test microservice locally with sample PDFs

## 2. Extraction Infrastructure (PHP)

- [x] 2.1 Create `Contracts\Extractor` interface with `isAvailable()` and `extract()` methods
- [x] 2.2 Create `DTOs\ExtractionResult` with status constants, named constructors, and helpers
- [x] 2.3 Create `Exceptions\ExtractionFailedException`
- [x] 2.4 Register `ExtractionResult` and related classes in `AppServiceProvider` if needed

## 3. Extractor Implementations

- [x] 3.1 Implement `DoclingExtractor` — calls FastAPI `/parse` via `Http::attach()` with 120s timeout
- [x] 3.2 Implement `LlamaParseExtractor` — wraps existing `LlamaParseService`, manages own cache keys, returns PENDING for async jobs
- [x] 3.3 Implement `LocalPdfExtractor` — rename `PdfExtractor`, implement `Extractor` interface
- [x] 3.4 Delete old `app/Services/Extraction/PdfExtractor.php`

## 4. Extraction Orchestrator

- [x] 4.1 Implement `ExtractionOrchestrator` with ordered extractors constructor injection
- [x] 4.2 Implement chain logic: skip unavailable, stop on pending, try next on failure, return first success
- [x] 4.3 Register `ExtractionOrchestrator` in service container with default chain order
- [x] 4.4 Add `docling` config to `config/services.php` with URL from env

## 5. Refactor ExtractCandidateInfoJob

- [x] 5.1 Remove `LLAMAPARSE_JOB_KEY`, `extractText()`, and all LlamaParse cache/poll code
- [x] 5.2 Replace with `app(ExtractionOrchestrator::class)->extract($this->cvPath)` call
- [x] 5.3 Handle pending → release(5), failed → cache error, empty → cache warning
- [x] 5.4 Update `.env` and `.env.example` with `DOCLING_SERVICE_URL`

## 6. Debug Route & Cleanup

- [x] 6.1 Update `routes/web.php` debug route to test Docling alongside existing extractors
- [x] 6.2 Update `app/Services/Extraction/LlamaParseService.php` PHPDoc if needed
- [x] 6.3 Verify no remaining references to `PdfExtractor` class

## 7. Tests

- [x] 7.1 Write `DoclingExtractorTest` — mock HTTP facade for success, unavailable, invalid response
- [x] 7.2 Write `LlamaParseExtractorTest` — mock HTTP + cache for async flow
- [x] 7.3 Write `LocalPdfExtractorTest` — port existing `PdfExtractorTest`, add interface conformance
- [x] 7.4 Write `ExtractionOrchestratorTest` — test chain logic (first success, skip unavailable, pending stops chain, all fail)
- [x] 7.5 Update `ExtractCandidateInfoJobTest` — mock Orchestrator instead of LlamaParseService
- [x] 7.6 Run full test suite and fix any regressions

## 8. Lint & Final Verification

- [x] 8.1 Run `vendor/bin/pint` to format all modified PHP files
- [x] 8.2 Run PHPStan and resolve any new errors
- [x] 8.3 Run `php artisan test --compact` and confirm all tests pass
