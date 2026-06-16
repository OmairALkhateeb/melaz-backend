<?php

namespace App\Enums;

/**
 * Neighborhoods / areas within Damascus governorate, Syria.
 *
 * The backed value is a Latin slug; Arabic and English labels are
 * resolved at display time from the translation files (enums.city.*).
 */
enum City: string
{
    // Western Damascus
    case Muhajirin = 'muhajirin';
    case Maliki = 'maliki';
    case RuknAlDin = 'rukn_al_din';
    case Salihiyah = 'salihiyah';
    case Afif = 'afif';
    case JisrAbyad = 'jisr_abyad';
    case Shaalan = 'shaalan';
    case AbuRummaneh = 'abu_rummaneh';
    case Rawda = 'rawda';
    case Mazzeh = 'mazzeh';
    case MazzehVillat = 'mazzeh_villat';
    case KafrSousseh = 'kafr_sousseh';
    case Baramkeh = 'baramkeh';
    case Mazraa = 'mazraa';

    // Central / Old City
    case Marjeh = 'marjeh';
    case Qassaa = 'qassaa';
    case BabTouma = 'bab_touma';
    case BabSharqi = 'bab_sharqi';
    case Qaymariyya = 'qaymariyya';
    case Shaghour = 'shaghour';
    case Amara = 'amara';
    case SouqSaruja = 'souq_saruja';
    case Qanawat = 'qanawat';
    case Hariqa = 'hariqa';

    // Southern Damascus
    case Midan = 'midan';
    case Qadam = 'qadam';
    case NahrAisha = 'nahr_aisha';
    case Zahira = 'zahira';
    case Tadamon = 'tadamon';
    case BabMusalla = 'bab_musalla';
    case BabSrija = 'bab_srija';

    // Northern / Eastern Damascus
    case Jobar = 'jobar';
    case Qaboun = 'qaboun';
    case Barzeh = 'barzeh';
    case Adawi = 'adawi';
    case Tijara = 'tijara';
    case Dummar = 'dummar';
    case DummarProject = 'dummar_project';

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
