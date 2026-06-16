<?php

namespace App\Enums;

enum Transmission: string
{
    case Automatic = 'automatic';
    case Manual = 'manual';
    case Cvt = 'cvt';
    case SemiAutomatic = 'semi_automatic';

    public function label(): string
    {
        return (string) __('enums.transmission.'.$this->value);
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
