<?php



namespace App\Enums;

enum FundingType: string
{
    case Grant = 'grant';
    case Equity = 'equity';
    case Debt = 'debt';
    case Prize = 'prize';
    case Other = 'other';
}
