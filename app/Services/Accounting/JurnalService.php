<?php

namespace App\Services\Accounting;

use App\Models\Jurnal;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class JurnalService
{
    public function create(array $jurnalData): Jurnal
    {
        return DB::transaction(function () use ($jurnalData): Jurnal {
            if (! in_array($jurnalData['position'] ?? null, ['DEBIT', 'CREDIT'], true)) {
                throw new InvalidArgumentException('The jurnal position must be DEBIT or CREDIT.');
            }

            if (! is_numeric($jurnalData['amount'] ?? null) || (float) $jurnalData['amount'] <= 0) {
                throw new InvalidArgumentException('The jurnal amount must contain a positive amount.');
            }

            $jurnal = new Jurnal;
            $jurnal->fill($jurnalData);
            $jurnal->saveOrFail();

            return $jurnal;
        });
    }
}
