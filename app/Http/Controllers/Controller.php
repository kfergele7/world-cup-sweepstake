<?php

namespace App\Http\Controllers;

use App\Models\Sweepstake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    private const SWEEPSTAKE_ADMIN_TABS = [
        'overview',
        'entrants',
        'teams',
        'pots',
        'draw-results',
        'settings-prizes',
    ];

    protected function redirectToSweepstakeTab(Request $request, Sweepstake $sweepstake, string $fallbackTab): RedirectResponse
    {
        $tab = $this->sweepstakeAdminTab($request, $fallbackTab);
        $url = route('sweepstakes.show', $sweepstake).'?'.http_build_query(['tab' => $tab]);

        return redirect()
            ->to($url)
            ->with('active_tab', $tab);
    }

    protected function sweepstakeAdminTab(Request $request, string $fallbackTab = 'overview'): string
    {
        $tab = $request->input('tab')
            ?: $request->query('tab')
            ?: $fallbackTab;

        return in_array($tab, self::SWEEPSTAKE_ADMIN_TABS, true) ? $tab : 'overview';
    }
}
