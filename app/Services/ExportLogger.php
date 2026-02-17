<?php

namespace App\Services;

use App\Models\Estimation;
use App\Models\Report;
use App\Models\User;

class ExportLogger
{
    /**
     * Log an export event to the reports table.
     */
    public static function log(
        User $user,
        string $reportType,
        string $sheetName,
        string $filename,
        ?Estimation $estimation = null,
        ?int $fileSize = null,
    ): Report {
        return Report::create([
            'user_id' => $user->id,
            'estimation_id' => $estimation?->id,
            'report_type' => $reportType,
            'sheet_name' => $sheetName,
            'filename' => $filename,
            'file_size' => $fileSize,
        ]);
    }
}
