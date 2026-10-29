<?php

namespace LivingReport\Model;

enum Tone: string
{
    case Crit = 'crit';
    case Info = 'info';
    case Muted = 'muted';
    case Ok = 'ok';
    case Warn = 'warn';
}
