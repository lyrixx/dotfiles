<?php

namespace LivingReport\Edit;

enum TargetKind
{
    case Card;
    case ChecklistItem;
    case Sublot;
    case TodoItem;
    case VigilanceRow;

    public function label(): string
    {
        return match ($this) {
            self::Card => 'carte',
            self::ChecklistItem => 'élément',
            self::Sublot => 'sous-lot',
            self::TodoItem => 'à faire',
            self::VigilanceRow => 'vigilance',
        };
    }
}
