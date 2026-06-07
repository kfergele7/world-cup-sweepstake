<?php

namespace App\Http\Controllers;

use App\Models\Sweepstake;
use App\Models\SweepstakeMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SweepstakeMemberController extends Controller
{
    public function store(Request $request, Sweepstake $sweepstake): RedirectResponse
    {
        $this->ensureAdmin($request, $sweepstake);

        if ($sweepstake->isLockedForChanges()) {
            return back()->withErrors([
                'member' => 'Entrants cannot be added after the draw.',
            ]);
        }

        $attributes = $this->validatedMemberAttributes($request, $sweepstake);

        SweepstakeMember::create([
            'sweepstake_id' => $sweepstake->id,
            'name' => $attributes['name'],
            'email' => $this->normaliseEmail($attributes['email'] ?? null),
            'join_token' => Str::random(40),
            'source' => SweepstakeMember::SOURCE_MANUAL,
            'is_paid' => $request->boolean('is_paid'),
            'paid_at' => $request->boolean('is_paid') ? now() : null,
        ]);

        return back()->with('status', 'Entrant added.');
    }

    public function update(Request $request, Sweepstake $sweepstake, SweepstakeMember $member): RedirectResponse
    {
        $this->ensureAdmin($request, $sweepstake);
        $this->ensureMemberBelongsToSweepstake($member, $sweepstake);

        if ($sweepstake->isLockedForChanges()) {
            return back()->withErrors([
                'member' => 'Entrants cannot be edited after the draw.',
            ]);
        }

        $attributes = $this->validatedMemberAttributes($request, $sweepstake, $member);

        $member->update([
            'name' => $attributes['name'],
            'email' => $this->normaliseEmail($attributes['email'] ?? null),
        ]);

        return back()->with('status', 'Entrant updated.');
    }

    public function updatePayment(Request $request, Sweepstake $sweepstake, SweepstakeMember $member): RedirectResponse
    {
        $this->ensureAdmin($request, $sweepstake);
        $this->ensureMemberBelongsToSweepstake($member, $sweepstake);

        if ($sweepstake->isLockedForChanges()) {
            return back()->withErrors([
                'member' => 'Entrant payment status is locked after the draw.',
            ]);
        }

        $attributes = $request->validate([
            'is_paid' => ['required', 'boolean'],
        ]);

        $isPaid = (bool) $attributes['is_paid'];

        $member->update([
            'is_paid' => $isPaid,
            'paid_at' => $isPaid ? now() : null,
        ]);

        return back()->with('status', $isPaid ? 'Entrant marked as paid.' : 'Entrant marked as unpaid.');
    }

    public function destroy(Request $request, Sweepstake $sweepstake, SweepstakeMember $member): RedirectResponse
    {
        $this->ensureAdmin($request, $sweepstake);
        $this->ensureMemberBelongsToSweepstake($member, $sweepstake);

        if ($sweepstake->isLockedForChanges()) {
            return back()->withErrors([
                'member' => 'Entrants cannot be removed after the draw.',
            ]);
        }

        $member->delete();

        return back()->with('status', 'Entrant removed.');
    }

    private function ensureAdmin(Request $request, Sweepstake $sweepstake): void
    {
        abort_unless($sweepstake->user_id === $request->user()->id, 403);
    }

    private function ensureMemberBelongsToSweepstake(SweepstakeMember $member, Sweepstake $sweepstake): void
    {
        abort_unless($member->sweepstake_id === $sweepstake->id, 404);
    }

    /**
     * @return array{name: string, email?: string|null}
     */
    private function validatedMemberAttributes(Request $request, Sweepstake $sweepstake, ?SweepstakeMember $member = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('sweepstake_members', 'email')
                    ->where('sweepstake_id', $sweepstake->id)
                    ->ignore($member?->id),
            ],
        ], [
            'name.required' => 'Please enter an entrant name.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already used by another entrant in this sweepstake.',
        ]);
    }

    private function normaliseEmail(?string $email): ?string
    {
        return $email ? Str::lower($email) : null;
    }
}
