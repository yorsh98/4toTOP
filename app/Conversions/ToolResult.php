<?php

namespace App\Conversions;

final readonly class ToolResult
{
    public function __construct(public string $path, public ?string $notice = null) {}
}
