<?php

namespace App\Domain;

enum ProjectType: string
{
    case Commercial = 'commercial';
    case Personal = 'personal';
    case Learning = 'learning';
}
