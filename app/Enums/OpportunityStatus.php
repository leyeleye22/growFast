<?php



namespace App\Enums;

enum OpportunityStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Expired = 'expired';
    case Archived = 'archived';
}
