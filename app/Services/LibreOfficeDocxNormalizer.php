<?php

namespace App\Services;

/**
 * Fixes known defects of LibreOffice's writer_pdf_import DOCX output (docs/plan-pdf-word-acrobat-xi.md §9):
 * offsets are page coordinates but anchored to column/paragraph (≈1 cm shift), and white
 * background shapes are painted over the text they sit behind. Only word/document.xml changes.
 */
class LibreOfficeDocxNormalizer
{
    private const WP = 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing';

    private const VML = 'urn:schemas-microsoft-com:vml';

    public function normalize(string $path): void
    {
        $archive = new \ZipArchive;
        if ($archive->open($path) !== true) {
            throw new \RuntimeException('LibreOffice no generó un documento Word válido.');
        }

        try {
            $xml = $archive->getFromName('word/document.xml');
            $document = new \DOMDocument;
            if ($xml === false || ! $document->loadXML($xml, LIBXML_NONET | LIBXML_PARSEHUGE)) {
                throw new \RuntimeException('LibreOffice no generó un documento Word válido.');
            }

            $changed = $this->anchorToPage($document) + $this->whiteShapesBehind($document);
            if ($changed > 0 && ! $archive->addFromString('word/document.xml', $document->saveXML())) {
                throw new \RuntimeException('No se pudo normalizar el documento Word.');
            }
        } finally {
            $archive->close();
        }
    }

    private function anchorToPage(\DOMDocument $document): int
    {
        $changed = 0;
        foreach (['positionH' => 'column', 'positionV' => 'paragraph'] as $name => $from) {
            foreach ($document->getElementsByTagNameNS(self::WP, $name) as $position) {
                if ($position->getAttribute('relativeFrom') === $from) {
                    $position->setAttribute('relativeFrom', 'page');
                    $changed++;
                }
            }
        }

        foreach ($document->getElementsByTagNameNS(self::VML, 'shape') as $shape) {
            $style = $shape->getAttribute('style');
            if (! str_contains($style, 'position:absolute') || str_contains($style, 'mso-position-horizontal-relative')) {
                continue;
            }
            $shape->setAttribute('style', rtrim($style, ';').';mso-position-horizontal-relative:page;mso-position-vertical-relative:page');
            $changed++;
        }

        return $changed;
    }

    /**
     * Vector shapes filled white go behind the text, keeping their relative order. Coloured or
     * black fills are left alone: they may be deliberate masks or redactions.
     */
    private function whiteShapesBehind(\DOMDocument $document): int
    {
        $changed = 0;
        foreach ($document->getElementsByTagNameNS(self::VML, 'shape') as $shape) {
            $isVector = $shape->hasAttribute('coordsize') || $shape->hasAttribute('path');
            $hasContent = $shape->getElementsByTagNameNS(self::VML, 'textbox')->length > 0
                || $shape->getElementsByTagNameNS(self::VML, 'imagedata')->length > 0;
            $filled = strtolower($shape->getAttribute('filled')) !== 'f' && strtolower($shape->getAttribute('filled')) !== 'false';
            $colour = strtolower($shape->getAttribute('fillcolor') ?: 'white');

            if (! $isVector || $hasContent || ! $filled || ! in_array($colour, ['white', '#fff', '#ffffff'], true)) {
                continue;
            }

            $style = preg_replace('/;?z-index:-?\d+/', '', $shape->getAttribute('style'));
            $shape->setAttribute('style', rtrim($style, ';').';z-index:'.(-2147483000 + $changed));
            $changed++;
        }

        return $changed;
    }
}
