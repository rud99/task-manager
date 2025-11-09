<?php

namespace App;

enum TaskStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Done = 'done';
}
