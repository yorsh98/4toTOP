<?php

return [
    'disk' => env('CONVERSIONS_DISK', 'local'),
    'public_limit' => 2,
    'pdf_max_kb' => 20 * 1024,
    // Measured: 1,285 pages convert in ~80 s with ~2.6 GB of RAM in pdf2docx.
    'pdf_max_pages' => (int) env('CONVERSIONS_PDF_MAX_PAGES', 1500),
    'audio_max_kb' => 50 * 1024,
    'download_hours' => 24,
    'ffmpeg' => env('CONVERSIONS_FFMPEG', 'ffmpeg'),
    // pdf2docx: paragraph/table reconstruction with header/footer, closest to Acrobat XI output.
    // libreoffice: visually faithful positioned text boxes, less comfortable to edit.
    'pdf_engine' => env('CONVERSIONS_PDF_ENGINE', 'pdf2docx'),
    'python' => env('CONVERSIONS_PYTHON', 'python3'),
    'libreoffice' => env('CONVERSIONS_LIBREOFFICE', 'libreoffice'),
    'pdfinfo' => env('CONVERSIONS_PDFINFO', 'pdfinfo'),
    'pdftotext' => env('CONVERSIONS_PDFTOTEXT', 'pdftotext'),
    'pdfimages' => env('CONVERSIONS_PDFIMAGES', 'pdfimages'),
    // Empty disables OCR: PDFs without selectable text are then rejected.
    'ocrmypdf' => env('CONVERSIONS_OCRMYPDF'),
    'ocr_languages' => env('CONVERSIONS_OCR_LANGUAGES', 'spa'),
];
