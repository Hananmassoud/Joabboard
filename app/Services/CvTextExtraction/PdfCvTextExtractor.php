<?php

namespace App\Services\CvTextExtraction;

use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class PdfCvTextExtractor
{
    public function extractFromPublicDiskPath(string $cvPath): string
    {
        $absolutePath = Storage::disk('public')->path($cvPath);

        $parser = new Parser();
        $pdf = $parser->parseFile($absolutePath);
        $text = (string) $pdf->getText();

        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace("/\r\n|\r/", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}

