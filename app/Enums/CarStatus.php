<?php

namespace App\Enums;

enum CarStatus: string
{
    case Available = 'available';
    case Sold = 'sold';
    case Hidden = 'hidden';

    public function label(): string
    {
        return (string) __('enums.car_status.'.$this->value);
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
