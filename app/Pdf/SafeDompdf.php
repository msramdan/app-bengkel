<?php

namespace App\Pdf;

use DOMDocument;
use Dompdf\Dompdf;
use ValueError;

/**
 * DomPDF 3 parses every document with masterminds/html5. On PHP 8 that parser
 * throws ValueError from Scanner::columnOffset() for some laporan HTML, which
 * turns PDF export into a 500. Libxml can still load that same HTML.
 */
class SafeDompdf extends Dompdf
{
    public function loadHtml($str, $encoding = null)
    {
        try {
            parent::loadHtml($str, $encoding);
        } catch (ValueError $e) {
            if (! $this->isScannerOffsetError($e)) {
                throw $e;
            }

            $this->loadHtmlUsingLibxml($str);
        }
    }

    public function loadHtmlUsingLibxml(string $str): void
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = true;

        $useInternalErrors = libxml_use_internal_errors(true);

        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$str, LIBXML_NOWARNING | LIBXML_NOERROR);
            libxml_clear_errors();
        } finally {
            libxml_use_internal_errors($useInternalErrors);
        }

        $this->loadDOM($document, false);
    }

    private function isScannerOffsetError(ValueError $e): bool
    {
        return str_contains($e->getMessage(), 'strrpos()')
            && str_contains(str_replace('\\', '/', $e->getFile()), '/HTML5/Parser/Scanner.php');
    }
}
