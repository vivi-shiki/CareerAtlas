<?php

namespace App\Domain;

enum ContentStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
}
