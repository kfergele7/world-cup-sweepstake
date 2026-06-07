<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $teams = [
            ['name' => 'France', 'country_code' => 'FRA', 'fifa_ranking' => 1, 'confederation' => 'UEFA'],
            ['name' => 'Spain', 'country_code' => 'ESP', 'fifa_ranking' => 2, 'confederation' => 'UEFA'],
            ['name' => 'Argentina', 'country_code' => 'ARG', 'fifa_ranking' => 3, 'confederation' => 'CONMEBOL'],
            ['name' => 'England', 'country_code' => 'ENG', 'fifa_ranking' => 4, 'confederation' => 'UEFA'],
            ['name' => 'Portugal', 'country_code' => 'POR', 'fifa_ranking' => 5, 'confederation' => 'UEFA'],
            ['name' => 'Brazil', 'country_code' => 'BRA', 'fifa_ranking' => 6, 'confederation' => 'CONMEBOL'],
            ['name' => 'Netherlands', 'country_code' => 'NED', 'fifa_ranking' => 7, 'confederation' => 'UEFA'],
            ['name' => 'Morocco', 'country_code' => 'MAR', 'fifa_ranking' => 8, 'confederation' => 'CAF'],
            ['name' => 'Belgium', 'country_code' => 'BEL', 'fifa_ranking' => 9, 'confederation' => 'UEFA'],
            ['name' => 'Germany', 'country_code' => 'GER', 'fifa_ranking' => 10, 'confederation' => 'UEFA'],
            ['name' => 'Croatia', 'country_code' => 'CRO', 'fifa_ranking' => 11, 'confederation' => 'UEFA'],
            ['name' => 'Colombia', 'country_code' => 'COL', 'fifa_ranking' => 13, 'confederation' => 'CONMEBOL'],
            ['name' => 'Senegal', 'country_code' => 'SEN', 'fifa_ranking' => 14, 'confederation' => 'CAF'],
            ['name' => 'Mexico', 'country_code' => 'MEX', 'fifa_ranking' => 15, 'confederation' => 'CONCACAF'],
            ['name' => 'United States', 'country_code' => 'USA', 'fifa_ranking' => 16, 'confederation' => 'CONCACAF'],
            ['name' => 'Uruguay', 'country_code' => 'URU', 'fifa_ranking' => 17, 'confederation' => 'CONMEBOL'],
            ['name' => 'Japan', 'country_code' => 'JPN', 'fifa_ranking' => 18, 'confederation' => 'AFC'],
            ['name' => 'Switzerland', 'country_code' => 'SUI', 'fifa_ranking' => 19, 'confederation' => 'UEFA'],
            ['name' => 'Iran', 'country_code' => 'IRN', 'fifa_ranking' => 21, 'confederation' => 'AFC'],
            ['name' => 'Turkiye', 'country_code' => 'TUR', 'fifa_ranking' => 22, 'confederation' => 'UEFA'],
            ['name' => 'Ecuador', 'country_code' => 'ECU', 'fifa_ranking' => 23, 'confederation' => 'CONMEBOL'],
            ['name' => 'Austria', 'country_code' => 'AUT', 'fifa_ranking' => 24, 'confederation' => 'UEFA'],
            ['name' => 'South Korea', 'country_code' => 'KOR', 'fifa_ranking' => 25, 'confederation' => 'AFC'],
            ['name' => 'Australia', 'country_code' => 'AUS', 'fifa_ranking' => 27, 'confederation' => 'AFC'],
            ['name' => 'Canada', 'country_code' => 'CAN', 'fifa_ranking' => 30, 'confederation' => 'CONCACAF'],
            ['name' => 'Norway', 'country_code' => 'NOR', 'fifa_ranking' => 32, 'confederation' => 'UEFA'],
            ['name' => 'Egypt', 'country_code' => 'EGY', 'fifa_ranking' => 33, 'confederation' => 'CAF'],
            ['name' => 'Algeria', 'country_code' => 'ALG', 'fifa_ranking' => 36, 'confederation' => 'CAF'],
            ['name' => 'Sweden', 'country_code' => 'SWE', 'fifa_ranking' => 37, 'confederation' => 'UEFA'],
            ['name' => 'Paraguay', 'country_code' => 'PAR', 'fifa_ranking' => 38, 'confederation' => 'CONMEBOL'],
            ['name' => 'Ivory Coast', 'country_code' => 'CIV', 'fifa_ranking' => 39, 'confederation' => 'CAF'],
            ['name' => 'Czechia', 'country_code' => 'CZE', 'fifa_ranking' => 41, 'confederation' => 'UEFA'],
            ['name' => 'Scotland', 'country_code' => 'SCO', 'fifa_ranking' => 43, 'confederation' => 'UEFA'],
            ['name' => 'Tunisia', 'country_code' => 'TUN', 'fifa_ranking' => 47, 'confederation' => 'CAF'],
            ['name' => 'Panama', 'country_code' => 'PAN', 'fifa_ranking' => 51, 'confederation' => 'CONCACAF'],
            ['name' => 'Qatar', 'country_code' => 'QAT', 'fifa_ranking' => 53, 'confederation' => 'AFC'],
            ['name' => 'DR Congo', 'country_code' => 'COD', 'fifa_ranking' => 55, 'confederation' => 'CAF'],
            ['name' => 'Iraq', 'country_code' => 'IRQ', 'fifa_ranking' => 56, 'confederation' => 'AFC'],
            ['name' => 'Uzbekistan', 'country_code' => 'UZB', 'fifa_ranking' => 57, 'confederation' => 'AFC'],
            ['name' => 'Saudi Arabia', 'country_code' => 'KSA', 'fifa_ranking' => 58, 'confederation' => 'AFC'],
            ['name' => 'South Africa', 'country_code' => 'RSA', 'fifa_ranking' => 60, 'confederation' => 'CAF'],
            ['name' => 'Jordan', 'country_code' => 'JOR', 'fifa_ranking' => 64, 'confederation' => 'AFC'],
            ['name' => 'Cape Verde', 'country_code' => 'CPV', 'fifa_ranking' => 70, 'confederation' => 'CAF'],
            ['name' => 'Bosnia and Herzegovina', 'country_code' => 'BIH', 'fifa_ranking' => 71, 'confederation' => 'UEFA'],
            ['name' => 'Ghana', 'country_code' => 'GHA', 'fifa_ranking' => 73, 'confederation' => 'CAF'],
            ['name' => 'Haiti', 'country_code' => 'HAI', 'fifa_ranking' => 79, 'confederation' => 'CONCACAF'],
            ['name' => 'Curacao', 'country_code' => 'CUW', 'fifa_ranking' => 81, 'confederation' => 'CONCACAF'],
            ['name' => 'New Zealand', 'country_code' => 'NZL', 'fifa_ranking' => 88, 'confederation' => 'OFC'],
        ];

        foreach ($teams as $team) {
            Team::updateOrCreate(
                ['country_code' => $team['country_code']],
                [
                    ...$team,
                    'flag' => null,
                    'ranking_points' => null,
                    'qualified_for_2026' => true,
                ]
            );
        }
    }
}
