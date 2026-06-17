## Context

HR-Assistant is an HR tool where agents manage job offers and analyze candidate CVs. The offer CRUD is complete (16/16 tests passing). The next step is building the CV ingestion pipeline: upload a PDF, extract markdown via LlamaParse, extract structured candidate data via Groq AI, chunk the text for later analysis, and link everything to an offer.

Currently there is no intake system — CVs have no structured storage, no candidate records, and no link to offers. This change builds that foundation so the future CV analysis spec can consume the stored data.

## Goals / Non-Goals

**Goals:**
- Upload a PDF CV and extract markdown via LlamaParse v2 API (primary)
- Fall back to `smalot/pdfparser` if LlamaParse unavailable
- Use Groq AI (plain prompting) to extract full structured candidate data from markdown text
- Store PDF to disk with a Document record
- Chunk extracted text and store in `document_chunks` table
- Find-or-create Candidate by email (no duplicates)
- Create Application linking candidate + offer + document
- Dispatch placeholder `AnalyseCVJob` for future analysis spec
- Async extraction via queue job with cache-based polling

**Non-Goals:**
- CV analysis (scoring, recommendations) — that's the `cv-analysis` spec
- Vector search or embeddings — plain text chunks for now
- Candidate management UI (list, edit, delete) — just the intake flow
- Multi-file upload — one PDF per submission
- Email or notifications

## Decisions

### 1. PDF extraction: LlamaParse v2 primary, `smalot/pdfparser` fallback

**Choice:** Use LlamaParse v2 API (POST /api/v2/parse with tier=cost_effective, GET with expand=markdown_full) to extract markdown text from PDFs. Fall back to `smalot/pdfparser` if LlamaParse is unavailable, times out, or returns error.

**Why:** LlamaParse uses AI-powered OCR and layout analysis — much better at extracting text from complex CV layouts, tables, and mixed formats. Returns clean markdown that's ideal for AI input. `smalot` is a pure PHP fallback for basic text-only PDFs.

**Pipeline:**
1. Upload file via curl POST /api/v1/beta/files (multipart form)
2. Create parse job via POST /api/v2/parse with file_id, tier=cost_effective, page_ranges.max_pages=100
3. Poll GET /api/v2/parse/{job_id}?expand=markdown_full every 2s up to 60s max
4. Extract markdown_full string from response (fallback through multiple response formats)

**Alternative considered:** `tcpdf` — heavier, more focused on generation. PdfParser alone lacks OCR. LlamaParse gives superior results for structured CVs.

### 2. AI extraction: Groq plain prompting (NOT HasStructuredOutput)

**Choice:** Use Groq (meta-llama/llama-4-scout-17b-16e-instruct) with plain prompting. Agent implements Agent without HasStructuredOutput. The CandidateInfoExtractorPrompt class builds a comprehensive prompt describing the expected JSON shape. The response is parsed via parseJsonResponse() which strips markdown code blocks and extracts JSON.

**Why:** Groq does not reliably support structured output (HasStructuredOutput via JSON schema). Plain prompting with well-written instructions produces equivalent results. The prompt extracts ALL data: name, email, phone, address, summary, skills, experience (array), education, certifications, languages, projects (array), other_sections.

**Prompt class:** CandidateInfoExtractorPrompt::build() returns a heredoc with:
- Role description (AI assistant extracting CV data)
- Explicit instructions to extract EVERY entry completely, no abbreviation
- Output JSON schema description (not schema() method — natural language)

**Response parsing:** parseJsonResponse() handles:
- Direct JSON parsing
- Markdown-wrapped JSON (```json ... ```)
- Falls back gracefully on parse error

**Alternative considered:** HasStructuredOutput with opis/json-schema — works with OpenAI but not Groq. Regex extraction — unreliable for varied CV formats. Plain prompting gives the best balance of reliability and completeness.

### 3. Text chunking: Custom Chunker service (not vector DB)

**Choice:** Custom `Chunker` service that splits text by sentences into word-based chunks with overlap.

**Why:** We're not using embeddings or vector search yet. The chunks are for the analysis agent to read as context. Sentence-aware splitting preserves meaning. Word-based sizing with overlap gives the agent clean context windows.

**Alternative considered:** Laravel Scout with TNTSearch — adds vector search complexity we don't need yet. Plain chunks are sufficient for the current analysis approach.

### 4. File storage: local disk with timestamp prefix

**Choice:** Store PDFs using Laravel's local disk driver (storage/app/private/) in path format `pdfs/{timestamp}_{original_filename}`.

**Why:** Timestamp prefix prevents filename collisions. Simple flat structure is fine for now (HR tool, not file storage service). The `original_path` on Document record lets us find the file later. Using Laravel's filesystem abstraction allows future migration to S3 without code changes.

**Alternative considered:** `storage/app/pdfs/` — same path, but using Storage facade's local disk ensures consistent root resolution (config('filesystems.disks.local.root')) = storage/app/private.

### 5. Candidate deduplication: email unique constraint

**Choice:** `candidates.email` has a unique constraint. On submission, find-or-create by email using updateOrCreate (updates name/phone if changed).

**Why:** Email is the most reliable identifier for a person across CVs. One person = one record. If the same candidate applies to multiple offers, they share one Candidate record.

**Alternative considered:** Name-based matching — names have duplicates and spelling variations. Phone-based — not always present.

### 6. Async extraction with polling (not synchronous wire:change)

**Choice:** File selection via `wire:change` stores the file and enables the "Upload & Extract" button. Clicking the button dispatches ExtractCandidateInfoJob to the queue, clears stale cache, and starts polling via `wire:poll.2s="pollExtraction"` with a 60-second timeout. When results are ready, fields are pre-filled.

**Why:** PDF extraction (LlamaParse API call + Groq API call) can take 10-30 seconds. Running this synchronously in a Livewire request would time out. Offloading to a queue job keeps the UI responsive and allows Livewire to poll for results.

**Flow:**
1. User selects file → stored to disk, $cvPath set, $isUploaded = true
2. User clicks "Upload & Extract" → Cache::forget() clears stale data, ExtractCandidateInfoJob dispatched
3. Livewire polls every 2s: checks cache for cv_extraction_{md5(path)} key
4. If found → fields pre-filled, $isExtracting = false
5. If 60s elapsed → show timeout message + retry button
6. Retry → Cache::forget() + re-dispatch job

**Alternative considered:** Synchronous wire:change → would time out for large PDFs. WebSockets — overkill for this use case. Simple polling with wire:poll is the Laravel way.

### 7. Extraction pipeline: job orchestration

**Choice:** ExtractCandidateInfoJob (ShouldQueue) orchestrates the full pipeline:
1. LlamaParseService::parsePdf() → markdown text
2. If LlamaParse unavailable → PdfExtractor::extract()
3. truncateText() — safeguards against oversized input (MAX_INPUT_LENGTH = 30000)
4. CandidateInfoExtractor agent + AiClient::prompt() → raw JSON text
5. parseJsonResponse() → structured array
6. cache()->put() with 300s TTL

**Why:** A single job encapsulates the full pipeline. If any step fails, the job fails and can be retried. The cache key includes an MD5 of the file path, so re-uploading the same file overwrites the previous result.

### 8. AiClient: centralized AI configuration

**Choice:** Single AiClient service that reads provider, model, and timeout from config/ai.php. Used by all AI agents.

**Why:** Centralizes configuration — changing provider/model/timeout is a config change, not a code change. The client creates the appropriate LLM client (Groq with key and base URL) and calls prompt() with the agent and input.

**Alternative considered:** Configuring providers inline in each job — duplicated configuration. AiClient keeps it DRY.

## Risks / Trade-offs

- **PDF text quality** → Some PDFs are scanned images, not text. `smalot/pdfparser` only extracts text from text-based PDFs. Scanned PDFs will return empty or garbled text. Mitigation: Display a warning if extraction returns very little text. Document this limitation.

- **AI extraction accuracy** → The agent may extract wrong info from complex CV layouts. Mitigation: Pre-fill form so HR can review and correct before submitting.

- **No rollback for PDF storage** → If the database transaction fails after PDF is stored, the file remains orphaned. Mitigation: Use database transaction for all record creation. If it fails, the PDF stays on disk but has no Document record — acceptable for an internal tool.

- **Chunk quality** → Simple word-based chunking may split context awkwardly. Mitigation: Sentence-aware splitting preserves natural boundaries. Sufficient for the analysis agent's needs.

- **AnalyseCVJob is placeholder** → Job does nothing yet. Mitigation: The `cv-analysis` spec will implement the actual logic. The job dispatch establishes the contract now.
