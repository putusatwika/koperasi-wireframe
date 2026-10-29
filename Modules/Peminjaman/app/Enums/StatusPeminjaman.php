<?php

namespace Modules\Peminjaman\Enums;

enum StatusPeminjaman: string
{
    case Draft = 'DRAFT';
    case Submitted = 'SUBMITTED';
    case InReview = 'IN_REVIEW';
    case ReadyForApproval = 'READY_FOR_APPROVAL';
    case CancellationRequested = 'CANCELLATION_REQUESTED';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case Disbursed = 'DISBURSED';
    case Cancelled = 'CANCELLED';
    case Lunas = 'LUNAS';
}
