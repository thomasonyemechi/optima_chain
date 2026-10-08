<?php

it('renders the demand forecasting and public verification landing page', function () {
    $response = $this->get('/');

    $response
        ->assertSee('Data-driven demand forecasting')
        ->assertSee('instant bank credit scoring')
        ->assertSee('AI demand forecasting')
        ->assertSee('Discrepancy tracking')
        ->assertSee('Verifiable credit scorecards')
        ->assertSee('Short-supply protection')
        ->assertSee('Distributor submits weekly demand')
        ->assertSee('Verify the record behind the score.')
        ->assertSee('data-verification-form', false)
        ->assertSee('data-verify-base="'.url('/verify').'"', false)
        ->assertSee(route('login'), false)
        ->assertSee('Privacy policy')
        ->assertSee('Terms of service');
});
