## Context

The app currently uses `LlamaParseService` directly in `ExtractCandidateInfoJob` for PDF extraction, with `PdfExtractor` as a hardcoded fallback. LlamaParse is a paid cloud dependency ($3/1K pages). The extraction logic is tightly coupled to the job, mixing cache management, polling, and fallback decisions. This design introduces a clean, interface-based extraction layer with three interchangeable extractors and an orchestrator.

## Goals / Non-Goals

**Goals:**
- Replace LlamaParse with a free self-hosted Docling microservice as the primary extractor
- Keep LlamaParse as an optional paid fallback
- Keep local `smalot/pdfparser` as the last-resort free fallback
- Formalize the extraction chain with an interface, DTOs, and an orchestrator
- Remove all LlamaParse cache/poll logic from `ExtractCandidateInfoJob`
- Make every extractor independently testable via the interface

**Non-Goals:**
- Changing the Groq-based AI extraction pipeline (CandidateInfoExtractor, prompts, AiClient)
- Changing database schema, models, migrations, or Volt templates
- Changing the frontend polling mechanism or cache key structure for CV results
- Adding vector search or RAG

## Decisions

1. **Separate Python microservice over embedded PHP PDF parsing** — Docling is a Python library with no PHP equivalent. A FastAPI microservice keeps the Laravel app pure PHP and allows independent scaling/deployment. The `docling-service/` directory lives in the same repo for monorepo-style development.

2. **Interface + DTOs over array returns** — The current code returns raw arrays with string keys (`['status' => ..., 'data' => ...]`). Using an `Extractor` interface and typed `ExtractionResult` DTO gives static analysis, IDE autocompletion, and prevents key mismatches.

3. **Orchestrator with ordered extractors over if/else chain** — The job currently has nested if/else blocks for fallback. An orchestrator that iterates a configurable list of extractors is cleaner, testable in isolation, and allows adding/removing extractors without touching the job.

4. **Http::attach() for Docling calls** — Laravel's HTTP client with multipart upload is simpler than a custom Guzzle client and follows existing app patterns.

5. **DoclingExtractor synchronous, LlamaParseExtractor async** — Docling returns parsed text directly (sync, 120s timeout). LlamaParse requires polling (async). The interface supports both: Docling returns `COMPLETED` immediately, LlamaParse returns `PENDING` with the caller releasing the job. The orchestrator stops the chain on `PENDING` because the job must yield back to the queue.

6. **PdfExtractor renamed to LocalPdfExtractor** — Simple rename to match the pattern and avoid ambiguity with the new DoclingExtractor.

## Risks / Trade-offs

- **[Risk] Docling microservice is a new runtime dependency** — Devs must run `uvicorn main:app --reload` in `docling-service/`. CI must start the service for tests. → **Mitigation**: Add `docker-compose.yml` for easy local setup; add CI step to start the service.
- **[Risk] Docling extraction quality may differ from LlamaParse** — Different parser, different output format. → **Mitigation**: Keep LlamaParse as fallback; debug route tests all extractors side by side.
- **[Risk] Python environment setup friction** — Team may not have Python 3.12+. → **Mitigation**: Provide `requirements.txt` and `Dockerfile`; document setup in README.
- **[Risk] 120s HTTP timeout may not be enough for large PDFs** — Docling can be slow on complex layouts. → **Mitigation**: Configurable timeout in config/services.php; microservice has 300s server-side timeout.
