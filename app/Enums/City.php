<?php

namespace App\Enums;

enum City: string
{
    // KSA
    case Riyadh = 'riyadh';
    case Jeddah = 'jeddah';
    case Mecca = 'mecca';
    case Medina = 'medina';
    case Dammam = 'dammam';
    case Khobar = 'khobar';
    case Dhahran = 'dhahran';
    case Taif = 'taif';
    case Tabuk = 'tabuk';
    case Abha = 'abha';
    case Khamis = 'khamis_mushait';
    case Jubail = 'jubail';
    case Hail = 'hail';
    case Najran = 'najran';
    case Yanbu = 'yanbu';
    case Qassim = 'qassim';
    case Buraidah = 'buraidah';

    // UAE
    case Dubai = 'dubai';
    case AbuDhabi = 'abu_dhabi';
    case Sharjah = 'sharjah';
    case Ajman = 'ajman';

    public function label(): string
    {
        return (string) __('enums.city.'.$this->value);
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
