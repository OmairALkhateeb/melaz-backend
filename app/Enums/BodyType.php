<?php

namespace App\Enums;

enum BodyType: string
{
    case Sedan = 'sedan';
    case Suv = 'suv';
    case Hatchback = 'hatchback';
    case Coupe = 'coupe';
    case Convertible = 'convertible';
    case Pickup = 'pickup';
    case Van = 'van';
    case Wagon = 'wagon';
    case Crossover = 'crossover';

    public function label(): string
    {
        return (string) __('enums.body_type.'.$this->value);
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
