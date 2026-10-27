<?php

namespace App\Conversions;

use App\Models\FileConversion;
use Illuminate\Http\UploadedFile;

/**
 * One tool of /convertir-archivos (PDF a Word, Unir PDF, OCR…). The page edits its options as
 * wire:model="options.*"; prepare() checks them against the uploaded files before queueing, and
 * process() runs in the ConvertFile job. Tools are registered in config('conversions.tools').
 */
abstract class ConversionTool
{
    public const FLATTENED_NOTICE = 'Las firmas electrónicas y anotaciones se conservaron como parte de la página: se ven igual, pero ya no son firmas verificables en el visor. Valida el documento con su código de verificación.';

    /** The program doing the work, recorded in the register even when it fails. */
    public ?string $engine = null;

    public function __construct(public readonly string $format, public readonly array $config) {}

    public static function for(string $format): self
    {
        $config = config('conversions.tools.'.$format) ?? throw new \InvalidArgumentException('Herramienta desconocida: '.$format);

        return new $config['class']($format, $config);
    }

    /** @return array<string, self> the tools this server can run, in display order */
    public static function available(): array
    {
        return array_filter(
            array_map(fn (string $format) => self::for($format), array_combine($formats = array_keys(config('conversions.tools')), $formats)),
            fn (self $tool) => $tool->enabled(),
        );
    }

    public function enabled(): bool
    {
        return true;
    }

    /** Name of a result type on its download button: "Descargar WAV". */
    public static function downloadLabel(string $extension): string
    {
        return ['docx' => 'Word', 'pdf' => 'PDF', 'zip' => 'ZIP', 'mp3' => 'MP3', 'm4a' => 'M4A', 'wav' => 'WAV', 'ogg' => 'OGG', 'flac' => 'FLAC'][strtolower($extension)] ?? 'archivo';
    }

    public function label(): string
    {
        return $this->config['label'];
    }

    public function multiple(): bool
    {
        return $this->config['multiple'] ?? false;
    }

    public function acceptsPdf(): bool
    {
        return $this->config['accept'] === ['pdf'];
    }

    /** Whether the page reads the PDF's page count on upload, to show it and check page options against it. */
    public function needsPageCount(): bool
    {
        return false;
    }

    public function needsAudioProbe(): bool
    {
        return false;
    }

    /** Extension of the result; a tool may still deliver another one (a split with one part is a PDF, not a ZIP). */
    abstract public function resultExtension(): string;

    /** Appended to the first input's name to name the result, e.g. "_unido". */
    public function resultSuffix(): string
    {
        return '';
    }

    /** @return array<string, mixed> initial values of the options the page edits */
    public function defaults(): array
    {
        return [];
    }

    /** @return array<string, mixed> validation rules for the options, keyed without the "options." prefix */
    public function rules(): array
    {
        return [];
    }

    /**
     * Checks the options against the uploaded files and returns what is stored in file_conversions.options.
     * Throws ValidationException keyed "options.<name>" or "file" so the page shows the message in place.
     *
     * @param  list<UploadedFile>  $files
     * @return array<string, mixed>
     */
    public function prepare(array $options, array $files, ?int $pageCount): array
    {
        return [];
    }

    /** Seconds the queued job may run. */
    public function timeout(array $options): int
    {
        return 360;
    }

    /** Writes result.<extension> into $directory, next to the stored inputs. */
    abstract public function process(FileConversion $conversion, string $directory): ToolResult;

    protected static function flattenedNotice(int $flattened): ?string
    {
        return $flattened > 0 ? self::FLATTENED_NOTICE : null;
    }

    /** @param list<int> $pages */
    protected static function pageList(array $pages): string
    {
        $shown = implode(', ', array_slice($pages, 0, 10)).(count($pages) > 10 ? '…' : '');

        return (count($pages) === 1 ? 'la página ' : 'las páginas ').$shown;
    }
}
