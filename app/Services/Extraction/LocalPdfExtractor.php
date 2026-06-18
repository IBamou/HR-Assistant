<?php

namespace App\Services\Extraction;

use App\DTOs\ExtractionResult;
use App\Services\Extraction\Contracts\Extractor;
use Smalot\PdfParser\Parser;

class LocalPdfExtractor implements Extractor
{
    public function isAvailable(): bool
    {
        return true;
    }

    public function extract(string $filePath): ExtractionResult
    {
        try {
            $parser = new Parser;
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            $text = $this->clean($text);

            return ExtractionResult::completed($text, 'LocalPdfExtractor');
        } catch (\Exception $e) {
            return ExtractionResult::failed('LocalPdfExtractor', $e->getMessage());
        }
    }

    private function clean(string $text): string
    {
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = preg_replace('/^\h+/m', '', $text);

        return trim($text);
    }
}
