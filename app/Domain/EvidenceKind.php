<?php

namespace App\Domain;

enum EvidenceKind: string
{
    case Repository = 'repository';
    case Demo = 'demo';
    case Document = 'document';
    case Statement = 'statement';
}
