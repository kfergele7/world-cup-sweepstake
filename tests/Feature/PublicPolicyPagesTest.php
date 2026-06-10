<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPolicyPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_footer_appears_on_public_auth_and_admin_pages(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('&copy; '.now()->year.' SweepKit', false)
            ->assertSee('Privacy Policy')
            ->assertSee(route('privacy'), false)
            ->assertSee('Terms')
            ->assertSee(route('terms'), false)
            ->assertSee(route('feedback'), false)
            ->assertSee('Built by')
            ->assertSee('Element Seven')
            ->assertSee('https://elementseven.co', false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('&copy; '.now()->year.' SweepKit', false)
            ->assertSee('Privacy Policy')
            ->assertSee('Terms')
            ->assertSee('Built by');

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'password',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('&copy; '.now()->year.' SweepKit', false)
            ->assertSee('Privacy Policy')
            ->assertSee('Terms')
            ->assertSee('Built by');
    }

    public function test_privacy_policy_page_explains_project_data_use(): void
    {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('SweepKit is a tool for private groups')
            ->assertSee('Admin name and email address')
            ->assertSee('Entrant name and email address')
            ->assertSee('entry fee, currency and other setup details')
            ->assertSee('Prize labels, positions and amounts')
            ->assertSee('Paid or unpaid status')
            ->assertSee('Selected teams, removed teams and custom pot settings')
            ->assertSee('Draw results, draw history')
            ->assertSee('Basic technical and log data')
            ->assertSee('does not currently process payments')
            ->assertSee('manual tracking only')
            ->assertSee('draw notifications, cancellation notices, re-run notices')
            ->assertSee('private tokens')
            ->assertSee('These pages are intended for the relevant entrant and organiser')
            ->assertSee('private beta')
            ->assertSee(route('feedback'), false)
            ->assertSee(route('terms'), false);
    }

    public function test_terms_page_exists_with_organiser_responsibility_wording(): void
    {
        $this->get(route('terms'))
            ->assertOk()
            ->assertSee('Terms')
            ->assertSee('SweepKit helps private groups organise and manage their own sweepstakes.')
            ->assertSee('The organiser is responsible for making sure their sweepstake follows local rules, workplace rules and any relevant gambling or lottery laws.')
            ->assertSee('SweepKit does not collect entry fees or run the sweepstake on your behalf.')
            ->assertSee('does not process payments, hold funds or pay out prizes')
            ->assertSee('only add entrants who have agreed to take part')
            ->assertSee('must not use SweepKit unlawfully')
            ->assertSee('Access may be restricted or removed')
            ->assertSee(route('feedback'), false)
            ->assertSee(route('privacy'), false);
    }

    public function test_feedback_page_renders_public_support_details_safely(): void
    {
        config(['support.email' => 'support@example.test']);

        $this->get(route('feedback'))
            ->assertOk()
            ->assertSee('Send feedback')
            ->assertSee('Found a bug or have feedback?')
            ->assertSee('support@example.test')
            ->assertSee('mailto:support@example.test', false)
            ->assertSee('Do not send passwords, payment details');
    }
}
