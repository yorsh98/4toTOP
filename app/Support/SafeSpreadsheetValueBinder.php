<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class SafeSpreadsheetValueBinder extends DefaultValueBinder
{
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value) && str_starts_with($value, '=')) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
