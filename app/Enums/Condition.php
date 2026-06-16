<?php

namespace App\Enums;

enum Condition: string
{
    case New = 'new';
    case Used = 'used';

    public function label(): string
    {
        return (string) __('enums.condition.'.$this->value);
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
