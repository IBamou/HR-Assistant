import os
import tempfile
from pathlib import Path
from typing import Any

from fastapi import FastAPI, File, UploadFile, HTTPException  # type: ignore[import-untyped]
from fastapi.responses import JSONResponse  # type: ignore[import-untyped]

from docling.document_converter import DocumentConverter  # type: ignore[import-untyped]

app: FastAPI = FastAPI(title="Docling PDF Extractor")

converter: DocumentConverter = DocumentConverter()


@app.get("/health")
async def health() -> dict[str, str]:
    return {"status": "ok"}


@app.post("/parse", response_model=None)
async def parse_pdf(
    file: UploadFile = File(...),
) -> dict[str, bool | str] | JSONResponse:
    filename: str | None = file.filename

    if not filename or not filename.lower().endswith(".pdf"):
        raise HTTPException(status_code=400, detail="Invalid file type")

    assert filename is not None
    suffix: str = Path(filename).suffix

    with tempfile.NamedTemporaryFile(delete=False, suffix=suffix) as tmp:
        content: bytes = await file.read()
        tmp.write(content)
        tmp_path: str = tmp.name

    try:
        result: Any = converter.convert(tmp_path)
        markdown: str = result.document.export_to_markdown()
        return {"success": True, "content": markdown}
    except Exception as e:
        return JSONResponse(
            status_code=500,
            content={"success": False, "error": str(e)},
        )
    finally:
        os.unlink(tmp_path)
