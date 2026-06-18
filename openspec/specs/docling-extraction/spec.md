# Docling PDF Extraction

## Purpose

Provide a self-hosted PDF extraction capability using Docling via a Python FastAPI microservice. This serves as the primary extraction method, falling back to cloud (LlamaParse) or local (smalot/pdfparser) extraction.

## Requirements

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
