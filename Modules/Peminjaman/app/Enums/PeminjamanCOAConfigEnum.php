<?php

namespace Modules\Peminjaman\Enums;

enum PeminjamanCOAConfigEnum: string
{
    case LoanReceivable = 'LOAN_RECEIVABLE';
    case DisbursementCash = 'DISBURSEMENT_CASH';
    case AdminIncome = 'ADMIN_INCOME';
    case ProvisionIncome = 'PROVISION_INCOME';
    case InterestIncome = 'INTEREST_INCOME';
}
