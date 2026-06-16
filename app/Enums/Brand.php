<?php

namespace App\Enums;

/**
 * Comprehensive list of car manufacturers.
 *
 * The backed value is the canonical Latin brand name (e.g. "Toyota",
 * "Mercedes-Benz") so it stays compatible with the existing `brand`
 * column, the public API (which returns the raw string) and the
 * filter-options service. Arabic labels are resolved at display time
 * from the translation files, falling back to the Latin name.
 */
enum Brand: string
{
    // Japanese
    case Toyota = 'Toyota';
    case Lexus = 'Lexus';
    case Honda = 'Honda';
    case Acura = 'Acura';
    case Nissan = 'Nissan';
    case Infiniti = 'Infiniti';
    case Mazda = 'Mazda';
    case Mitsubishi = 'Mitsubishi';
    case Subaru = 'Subaru';
    case Suzuki = 'Suzuki';
    case Daihatsu = 'Daihatsu';
    case Isuzu = 'Isuzu';

    // Korean
    case Hyundai = 'Hyundai';
    case Kia = 'Kia';
    case Genesis = 'Genesis';
    case Daewoo = 'Daewoo';
    case SsangYong = 'SsangYong';

    // American
    case Ford = 'Ford';
    case Lincoln = 'Lincoln';
    case Chevrolet = 'Chevrolet';
    case GMC = 'GMC';
    case Cadillac = 'Cadillac';
    case Buick = 'Buick';
    case Chrysler = 'Chrysler';
    case Dodge = 'Dodge';
    case Jeep = 'Jeep';
    case Ram = 'Ram';
    case Tesla = 'Tesla';
    case Hummer = 'Hummer';
    case Rivian = 'Rivian';
    case Lucid = 'Lucid';

    // German
    case MercedesBenz = 'Mercedes-Benz';
    case BMW = 'BMW';
    case Audi = 'Audi';
    case Volkswagen = 'Volkswagen';
    case Porsche = 'Porsche';
    case Opel = 'Opel';
    case Mini = 'Mini';
    case Smart = 'Smart';
    case Maybach = 'Maybach';

    // British
    case LandRover = 'Land Rover';
    case RangeRover = 'Range Rover';
    case Jaguar = 'Jaguar';
    case Bentley = 'Bentley';
    case RollsRoyce = 'Rolls-Royce';
    case AstonMartin = 'Aston Martin';
    case McLaren = 'McLaren';
    case Lotus = 'Lotus';
    case MG = 'MG';

    // Italian
    case Ferrari = 'Ferrari';
    case Lamborghini = 'Lamborghini';
    case Maserati = 'Maserati';
    case AlfaRomeo = 'Alfa Romeo';
    case Fiat = 'Fiat';
    case Lancia = 'Lancia';
    case Abarth = 'Abarth';

    // French
    case Renault = 'Renault';
    case Peugeot = 'Peugeot';
    case Citroen = 'Citroen';
    case DS = 'DS';
    case Bugatti = 'Bugatti';
    case Alpine = 'Alpine';

    // Swedish
    case Volvo = 'Volvo';
    case Polestar = 'Polestar';
    case Koenigsegg = 'Koenigsegg';
    case Saab = 'Saab';

    // Spanish / Czech / Romanian
    case Seat = 'Seat';
    case Cupra = 'Cupra';
    case Skoda = 'Skoda';
    case Dacia = 'Dacia';

    // Chinese
    case BYD = 'BYD';
    case Geely = 'Geely';
    case Chery = 'Chery';
    case GWM = 'GWM';
    case Haval = 'Haval';
    case Changan = 'Changan';
    case Dongfeng = 'Dongfeng';
    case FAW = 'FAW';
    case Hongqi = 'Hongqi';
    case JAC = 'JAC';
    case Zeekr = 'Zeekr';
    case Nio = 'Nio';
    case Xpeng = 'Xpeng';
    case LiAuto = 'Li Auto';
    case LynkCo = 'Lynk & Co';
    case Tank = 'Tank';
    case Jetour = 'Jetour';
    case Bestune = 'Bestune';
    case BAIC = 'BAIC';
    case Foton = 'Foton';
    case Maxus = 'Maxus';
    case Wuling = 'Wuling';
    case Exeed = 'Exeed';

    // Indian / Malaysian / Russian
    case Tata = 'Tata';
    case Mahindra = 'Mahindra';
    case Proton = 'Proton';
    case Perodua = 'Perodua';
    case Lada = 'Lada';

    case Other = 'Other';

    public function label(): string
    {
        $key = 'enums.brand.'.$this->value;
        $translated = (string) __($key);

        // __() echoes the key back when no translation exists; fall back to
        // the canonical Latin brand name in that case.
        return $translated === $key ? $this->value : $translated;
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
