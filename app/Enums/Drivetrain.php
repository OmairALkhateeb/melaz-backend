<?php

namespace App\Enums;

enum Drivetrain: string
{
    case Fwd = 'fwd';
    case Rwd = 'rwd';
    case Awd = 'awd';
    case FourWd = 'four_wd';

    public function label(): string
    {
        return (string) __('enums.drivetrain.'.$this->value);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}
