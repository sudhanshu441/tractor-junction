<?php

namespace Tests\Unit;

use App\Domain\Finance\Services\EmiCalculator;
use Tests\TestCase;

/**
 * The EMI figure is the number a farmer plans a season around, so it is checked
 * against hand-worked values rather than against the implementation.
 */
class EmiCalculatorTest extends TestCase
{
    private EmiCalculator $emi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emi = new EmiCalculator;
    }

    public function test_it_matches_the_standard_amortisation_formula(): void
    {
        // ₹5,00,000 over 60 months at 12% → EMI ≈ ₹11,122.22
        $result = $this->emi->calculate(600000, 100000, 12.0, 60);

        $this->assertSame(500000.0, $result['loan_amount']);
        $this->assertEqualsWithDelta(11122.22, $result['emi'], 0.5);
        $this->assertEqualsWithDelta(167333.0, $result['total_interest'], 50.0);
        $this->assertEqualsWithDelta(667333.0, $result['total_payable'], 50.0);
    }

    public function test_a_zero_interest_scheme_divides_the_principal_evenly(): void
    {
        $result = $this->emi->calculate(240000, 0, 0.0, 24);

        $this->assertSame(10000.0, $result['emi']);
        $this->assertSame(0.0, $result['total_interest']);
    }

    public function test_a_down_payment_covering_the_whole_price_leaves_nothing_to_pay(): void
    {
        $result = $this->emi->calculate(400000, 400000, 11.5, 36);

        $this->assertSame(0.0, $result['loan_amount']);
        $this->assertSame(0.0, $result['emi']);
    }

    public function test_a_half_yearly_instalment_is_larger_than_a_monthly_one(): void
    {
        $monthly = $this->emi->calculate(500000, 0, 12.0, 60, 'monthly');
        $halfYearly = $this->emi->calculate(500000, 0, 12.0, 60, 'half_yearly');

        $this->assertGreaterThan($monthly['emi'], $halfYearly['emi']);
        $this->assertSame(10, $halfYearly['instalments']);
    }

    public function test_the_amortisation_schedule_pays_the_loan_off_exactly(): void
    {
        $schedule = $this->emi->amortisation(500000, 0, 12.0, 24);

        $this->assertCount(2, $schedule);                       // two calendar years
        $this->assertEqualsWithDelta(0.0, end($schedule)['balance'], 1.0);
        $this->assertEqualsWithDelta(
            500000.0,
            array_sum(array_column($schedule, 'principal')),
            1.0,
        );
    }

    public function test_eligibility_scales_with_income(): void
    {
        $poor = $this->emi->eligibility(120000, 900000, 200000);
        $rich = $this->emi->eligibility(900000, 900000, 200000);

        $this->assertFalse($poor['eligible']);
        $this->assertTrue($rich['eligible']);
        $this->assertGreaterThan($poor['max_loan'], $rich['max_loan']);
    }
}
