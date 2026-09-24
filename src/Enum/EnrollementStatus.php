<?php

namespace App\Enum;

enum EnrollementStatus: string
{
    case NOT_STARTED = 'not_started';
    case PAUSED = 'paused';
    case IN_PROGRESS = 'in_progress';
    case FINISHED = 'finished';
}
