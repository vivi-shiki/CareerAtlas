<?php

namespace App\Domain;

enum RecordType: string
{
    case WorkContribution = 'work_contribution';
    case Learning = 'learning';
    case ProblemSolving = 'problem_solving';
}
