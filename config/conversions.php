<?php

use App\Conversions\AudioCutTool;
use App\Conversions\AudioToMp3Tool;
use App\Conversions\CompressPdfTool;
use App\Conversions\ImagesToPdfTool;
use App\Conversions\MergePdfTool;
use App\Conversions\OcrPdfTool;
use App\Conversions\OrganizePdfTool;
use App\Conversions\PageNumbersTool;
use App\Conversions\PdfToWordTool;
use App\Conversions\RotatePdfTool;
use App\Conversions\SplitPdfTool;
use App\Conversions\WordToPdfTool;

return [
    'disk' => env('CONVERSIONS_DISK', 'local'),
    'public_limit' => 2,
    // Measured: 1,285 pages convert in ~80 s with ~2.6 GB of RAM in pdf2docx.
    'pdf_max_pages' => (int) env('CONVERSIONS_PDF_MAX_PAGES', 1500),
    'download_hours' => 24,
    'ffmpeg' => env('CONVERSIONS_FFMPEG', 'ffmpeg'),
    'ffprobe' => env('CONVERSIONS_FFPROBE', 'ffprobe'),
    'audio_max_seconds' => (int) env('CONVERSIONS_AUDIO_MAX_SECONDS', 4 * 3600),
    'audio_cut_max_segments' => 200,
    // pdf2docx: paragraph/table reconstruction with header/footer, closest to Acrobat XI output.
    // libreoffice: visually faithful positioned text boxes, less comfortable to edit.
    'pdf_engine' => env('CONVERSIONS_PDF_ENGINE', 'pdf2docx'),
    'tesseract' => env('CONVERSIONS_TESSERACT', 'tesseract'),
    'python' => env('CONVERSIONS_PYTHON', 'python3'),
    'libreoffice' => env('CONVERSIONS_LIBREOFFICE', 'libreoffice'),
    'ghostscript' => env('CONVERSIONS_GHOSTSCRIPT', 'gs'),
    'pdfinfo' => env('CONVERSIONS_PDFINFO', 'pdfinfo'),
    'pdftotext' => env('CONVERSIONS_PDFTOTEXT', 'pdftotext'),
    'pdfimages' => env('CONVERSIONS_PDFIMAGES', 'pdfimages'),
    // Empty disables OCR: the OCR tool is hidden and scanned PDFs are rejected by PDF to Word.
    'ocrmypdf' => env('CONVERSIONS_OCRMYPDF'),
    'ocr_languages' => env('CONVERSIONS_OCR_LANGUAGES', 'spa'),
    // Measured with 4 jobs, deskew and rotation: 40 real court pages scanned at 300 dpi in 42 s (~1 s/page,
    // 380 MB of RAM), so 1,000 pages take ~18 min. More jobs (cores) shorten it.
    'ocr_max_pages' => (int) env('CONVERSIONS_OCR_MAX_PAGES', 1000),
    'ocr_jobs' => (int) env('CONVERSIONS_OCR_JOBS', 4),
    // Seconds; OCR jobs get this plus a margin as queue timeout, so DB_QUEUE_RETRY_AFTER must be larger.
    'ocr_timeout' => (int) env('CONVERSIONS_OCR_TIMEOUT', 1800),

    /*
     * The tools offered in /convertir-archivos, in display order. "format" (the key) is what file_conversions
     * stores. max_kb is per file; 50 MB is also Livewire's temporary upload limit (config/livewire.php), and
     * scans need it. "multiple" tools take several files uploaded one by one, within max_files and max_total_kb.
     * "aliases" are accepted extensions left out of the list shown to users (oga is Ogg, asf is how WMA is detected).
     */
    'tools' => [
        'pdf' => ['label' => 'PDF a Word', 'group' => 'Convertir', 'icon' => 'fa-file-word', 'class' => PdfToWordTool::class, 'accept' => ['pdf'], 'max_kb' => 20 * 1024],
        'word2pdf' => ['label' => 'Word a PDF', 'group' => 'Convertir', 'icon' => 'fa-file-pdf', 'class' => WordToPdfTool::class, 'accept' => ['doc', 'docx', 'odt', 'rtf'], 'max_kb' => 20 * 1024],
        'img2pdf' => ['label' => 'Imagen a PDF', 'group' => 'Convertir', 'icon' => 'fa-file-image', 'class' => ImagesToPdfTool::class, 'accept' => ['jpg', 'jpeg', 'png'], 'max_kb' => 20 * 1024, 'multiple' => true, 'min_files' => 1, 'max_files' => 50, 'max_total_kb' => 100 * 1024],
        'm4a' => ['label' => 'M4A a MP3', 'group' => 'Convertir', 'icon' => 'fa-file-audio', 'class' => AudioToMp3Tool::class, 'accept' => ['m4a'], 'max_kb' => 50 * 1024],
        'merge' => ['label' => 'Unir PDF', 'group' => 'Organizar', 'icon' => 'fa-object-group', 'class' => MergePdfTool::class, 'accept' => ['pdf'], 'max_kb' => 20 * 1024, 'multiple' => true, 'min_files' => 2, 'max_files' => 20, 'max_total_kb' => 100 * 1024],
        'split' => ['label' => 'Dividir PDF', 'group' => 'Organizar', 'icon' => 'fa-scissors', 'class' => SplitPdfTool::class, 'accept' => ['pdf'], 'max_kb' => 20 * 1024],
        'rotate' => ['label' => 'Rotar PDF', 'group' => 'Organizar', 'icon' => 'fa-rotate-right', 'class' => RotatePdfTool::class, 'accept' => ['pdf'], 'max_kb' => 50 * 1024],
        'organize' => ['label' => 'Organizar PDF', 'group' => 'Organizar', 'icon' => 'fa-table-cells', 'class' => OrganizePdfTool::class, 'accept' => ['pdf'], 'max_kb' => 50 * 1024],
        'compress' => ['label' => 'Comprimir PDF', 'group' => 'Optimizar', 'icon' => 'fa-compress', 'class' => CompressPdfTool::class, 'accept' => ['pdf'], 'max_kb' => 50 * 1024],
        'ocr' => ['label' => 'OCR: PDF con texto buscable', 'group' => 'Optimizar', 'icon' => 'fa-magnifying-glass', 'class' => OcrPdfTool::class, 'accept' => ['pdf'], 'max_kb' => 50 * 1024],
        'pagenum' => ['label' => 'Números de página', 'group' => 'Editar', 'icon' => 'fa-list-ol', 'class' => PageNumbersTool::class, 'accept' => ['pdf'], 'max_kb' => 50 * 1024],
        'audiocut' => ['label' => 'Cortar audio', 'group' => 'Editar', 'icon' => 'fa-scissors', 'class' => AudioCutTool::class, 'accept' => ['mp3', 'm4a', 'wav', 'ogg', 'oga', 'opus', 'flac', 'aac', 'wma', 'asf', 'amr'], 'aliases' => ['oga', 'asf'], 'max_kb' => 50 * 1024],
    ],
];
