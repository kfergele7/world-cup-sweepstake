<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'country_code',
    'flag',
    'fifa_ranking',
    'ranking_points',
    'qualified_for_2026',
    'confederation',
])]
class Team extends Model
{
    use HasFactory;

    /**
     * @var array<string, string>
     */
    private const FIFA_CODE_TO_ISO2 = [
        'ALG' => 'DZ',
        'ARG' => 'AR',
        'AUS' => 'AU',
        'AUT' => 'AT',
        'BEL' => 'BE',
        'BIH' => 'BA',
        'BRA' => 'BR',
        'CAN' => 'CA',
        'CIV' => 'CI',
        'COD' => 'CD',
        'COL' => 'CO',
        'CPV' => 'CV',
        'CRO' => 'HR',
        'CZE' => 'CZ',
        'CUW' => 'CW',
        'ECU' => 'EC',
        'EGY' => 'EG',
        'ESP' => 'ES',
        'FRA' => 'FR',
        'GER' => 'DE',
        'GHA' => 'GH',
        'HAI' => 'HT',
        'IRN' => 'IR',
        'IRQ' => 'IQ',
        'JOR' => 'JO',
        'JPN' => 'JP',
        'KOR' => 'KR',
        'KSA' => 'SA',
        'MAR' => 'MA',
        'MEX' => 'MX',
        'NED' => 'NL',
        'NOR' => 'NO',
        'NZL' => 'NZ',
        'PAN' => 'PA',
        'PAR' => 'PY',
        'POR' => 'PT',
        'QAT' => 'QA',
        'RSA' => 'ZA',
        'SEN' => 'SN',
        'SUI' => 'CH',
        'SWE' => 'SE',
        'TUN' => 'TN',
        'TUR' => 'TR',
        'URU' => 'UY',
        'USA' => 'US',
        'UZB' => 'UZ',
    ];

    /**
     * @var array<string, string>
     */
    private const FOOTBALL_NATION_LABELS = [
        'ENG' => 'ENG',
        'NIR' => 'NI',
        'SCO' => 'SCO',
        'WAL' => 'WAL',
    ];

    protected function casts(): array
    {
        return [
            'fifa_ranking' => 'integer',
            'qualified_for_2026' => 'boolean',
            'ranking_points' => 'decimal:2',
        ];
    }

    public function sweepstakeTeams(): HasMany
    {
        return $this->hasMany(SweepstakeTeam::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TeamAssignment::class);
    }

    public function displayFlag(): ?string
    {
        $storedFlag = $this->validStoredFlag();

        if ($storedFlag) {
            return $storedFlag;
        }

        $code = str($this->country_code)->upper()->toString();

        if (isset(self::FOOTBALL_NATION_LABELS[$code])) {
            return self::FOOTBALL_NATION_LABELS[$code];
        }

        $iso2 = strlen($code) === 2 ? $code : (self::FIFA_CODE_TO_ISO2[$code] ?? null);

        if (! $iso2 || strlen($iso2) !== 2) {
            return null;
        }

        return mb_chr(127397 + ord($iso2[0])).mb_chr(127397 + ord($iso2[1]));
    }

    private function validStoredFlag(): ?string
    {
        if (! $this->flag) {
            return null;
        }

        $flag = str($this->flag)->trim()->toString();

        if ($flag === '' || str_contains($flag, '🏴')) {
            return null;
        }

        return $flag;
    }
}
