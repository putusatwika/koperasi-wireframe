<?php

namespace Modules\Pembayaran\Enums;

enum PembayaranCOAConfigEnum: string
{
    case PaymentCash = 'PAYMENT_CASH';
    case LoanReceivable = 'LOAN_RECEIVABLE';
    case InterestIncome = 'INTEREST_INCOME';
    case PenaltyIncome = 'PENALTY_INCOME';
}
