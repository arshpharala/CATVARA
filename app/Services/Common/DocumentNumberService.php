<?php

namespace App\Services\Common;

use Illuminate\Support\Facades\DB;
use App\Models\Common\DocumentSequence;

class DocumentNumberService
{
    public function generate(
        int $companyId,
        string $documentType,
        ?string $channel = null,
        ?int $year = null,
        int $padding = 6
    ): string {
        return DB::transaction(function () use (
            $companyId,
            $documentType,
            $channel,
            $year,
            $padding
        ) {
            /** @var DocumentSequence $sequence */
            $sequence = DocumentSequence::where([
                'company_id' => $companyId,
                'document_type' => $documentType,
                'channel' => $channel,
                'year' => $year,
            ])->lockForUpdate()->first();

            if (!$sequence) {
                throw new \RuntimeException(
                    "Document sequence not configured for {$documentType}"
                );
            }

            $sequence->increment('current_number');

            $number = str_pad(
                (string) $sequence->current_number,
                $padding,
                '0',
                STR_PAD_LEFT
            );

            $formatted = $sequence->prefix
                . $number
                . ($sequence->postfix ? $sequence->postfix : '');

            return $formatted;
        });
    }
}
