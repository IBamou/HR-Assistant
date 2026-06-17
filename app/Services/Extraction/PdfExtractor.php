<?php

namespace App\Services\Extraction;

use Smalot\PdfParser\Parser;

class PdfExtractor
{
    public function extract(string $filePath): string
    {
        $parser = new Parser;
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        return $this->clean($text);
    }

    private function clean(string $text): string
    {
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = preg_replace('/^\h+/m', '', $text);

        return trim($text);
    }
}
