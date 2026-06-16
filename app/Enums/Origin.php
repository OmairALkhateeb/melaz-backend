<?php

namespace App\Enums;

enum Origin: string
{
    case Gcc = 'gcc';
    case American = 'american';
    case European = 'european';
    case Japanese = 'japanese';
    case Korean = 'korean';
    case Canadian = 'canadian';
    case Other = 'other';

    public function label(): string
    {
        return (string) __('enums.origin.'.$this->value);
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
