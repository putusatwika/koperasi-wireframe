<?php

namespace Modules\Simpanan\Enums;

enum SimpananCOAConfigEnum: string
{
    case SavingsCash = 'SAVINGS_CASH';
    case SavingsLiability = 'SAVINGS_LIABILITY';
    case SavingsInterestExpense = 'SAVINGS_INTEREST_EXPENSE';
}
