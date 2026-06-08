<?php

namespace Tests\Unit;

use App\Models\Team;
use PHPUnit\Framework\TestCase;

class TeamFlagTest extends TestCase
{
    public function test_standard_country_codes_render_flag_emoji(): void
    {
        $this->assertSame('🇦🇷', $this->team('Argentina', 'ARG')->displayFlag());
        $this->assertSame('🇵🇹', $this->team('Portugal', 'POR')->displayFlag());
        $this->assertSame('🇯🇵', $this->team('Japan', 'JPN')->displayFlag());
    }

    public function test_football_specific_codes_render_safe_labels_not_black_flags(): void
    {
        $this->assertSame('ENG', $this->team('England', 'ENG')->displayFlag());
        $this->assertSame('SCO', $this->team('Scotland', 'SCO')->displayFlag());
        $this->assertSame('WAL', $this->team('Wales', 'WAL')->displayFlag());
        $this->assertSame('NI', $this->team('Northern Ireland', 'NIR')->displayFlag());

        $this->assertNotSame('🏴', $this->team('England', 'ENG')->displayFlag());
        $this->assertNotSame('🏴', $this->team('Scotland', 'SCO')->displayFlag());
    }

    public function test_broken_stored_black_flags_are_ignored(): void
    {
        $this->assertSame('ENG', $this->team('England', 'ENG', '🏴')->displayFlag());
        $this->assertNull($this->team('Unknown Team', 'ZZZ', '🏴')->displayFlag());
    }

    public function test_unknown_country_codes_render_no_flag(): void
    {
        $this->assertNull($this->team('Unknown Team', 'ZZZ')->displayFlag());
    }

    private function team(string $name, string $countryCode, ?string $flag = null): Team
    {
        return new Team([
            'name' => $name,
            'country_code' => $countryCode,
            'flag' => $flag,
        ]);
    }
}
