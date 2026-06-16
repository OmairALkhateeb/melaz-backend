<?php

namespace App\Enums;

enum Color: string
{
    case Black = 'black';
    case White = 'white';
    case Silver = 'silver';
    case Gray = 'gray';
    case Blue = 'blue';
    case Red = 'red';
    case Green = 'green';
    case Brown = 'brown';
    case Beige = 'beige';
    case PearlWhite = 'pearl_white';
    case Gold = 'gold';
    case Other = 'other';

    public function label(): string
    {
        return (string) __('enums.color.'.$this->value);
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
