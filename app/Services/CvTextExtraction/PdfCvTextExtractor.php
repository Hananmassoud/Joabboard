<?php

namespace App\Services\CvTextExtraction;

use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class PdfCvTextExtractor
{
    public function extractFromPublicDiskPath(string $cvPath): string
    {
        // Convert a stored PDF file into plain text.
        // This text is used by the AI services for matching and skill extraction.
        $absolutePath = Storage::disk('public')->path($cvPath);

        $parser = new Parser();
        $pdf = $parser->parseFile($absolutePath);
        $text = (string) $pdf->getText();

        // Clean text: normalize spaces and newlines (reduces AI token usage and improves accuracy).
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace("/\r\n|\r/", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}

