<?php

namespace App\Domain\Finance\Services;

/**
 * Loan arithmetic.
 *
 * Farm income arrives at harvest, not monthly, so quarterly, half-yearly and
 * yearly repayment are first-class options here rather than a footnote.
 */
class EmiCalculator
{
    public const FREQUENCIES = [
        'monthly' => 12,
        'quarterly' => 4,
        'half_yearly' => 2,
        'yearly' => 1,
    ];

    /**
     * @return array{loan_amount: float, emi: float, total_interest: float, total_payable: float,
     *               instalments: int, periods_per_year: int, rate_per_period: float}
     */
    public function calculate(
        float $price,
        float $downPayment,
        float $annualRate,
        int $tenureMonths,
        string $frequency = 'monthly',
    ): array {
        $principal = max(0, $price - $downPayment);
        $periodsPerYear = self::FREQUENCIES[$frequency] ?? 12;
        $instalments = max(1, (int) round($tenureMonths / 12 * $periodsPerYear));

        if ($principal <= 0) {
            return [
                'loan_amount' => 0.0, 'emi' => 0.0, 'total_interest' => 0.0, 'total_payable' => 0.0,
                'instalments' => $instalments, 'periods_per_year' => $periodsPerYear, 'rate_per_period' => 0.0,
            ];
        }

        $ratePerPeriod = $annualRate / 100 / $periodsPerYear;

        // A zero-interest scheme is a real product, and the standard formula
        // divides by zero on it.
        if ($ratePerPeriod <= 0) {
            return [
                'loan_amount' => round($principal, 2),
                'emi' => round($principal / $instalments, 2),
                'total_interest' => 0.0,
                'total_payable' => round($principal, 2),
                'instalments' => $instalments,
                'periods_per_year' => $periodsPerYear,
                'rate_per_period' => 0.0,
            ];
        }

        $growth = (1 + $ratePerPeriod) ** $instalments;
        $emi = $principal * $ratePerPeriod * $growth / ($growth - 1);
        $totalPayable = $emi * $instalments;

        return [
            'loan_amount' => round($principal, 2),
            'emi' => round($emi, 2),
            'total_interest' => round($totalPayable - $principal, 2),
            'total_payable' => round($totalPayable, 2),
            'instalments' => $instalments,
            'periods_per_year' => $periodsPerYear,
            'rate_per_period' => $ratePerPeriod,
        ];
    }

    /**
     * Period-by-period breakdown, grouped by year for display.
     *
     * @return array<int, array{year: int, principal: float, interest: float, balance: float}>
     */
    public function amortisation(
        float $price,
        float $downPayment,
        float $annualRate,
        int $tenureMonths,
        string $frequency = 'monthly',
    ): array {
        $result = $this->calculate($price, $downPayment, $annualRate, $tenureMonths, $frequency);
        $balance = $result['loan_amount'];

        if ($balance <= 0) {
            return [];
        }

        $rows = [];
        $startYear = (int) date('Y');
        $perYear = $result['periods_per_year'];

        for ($i = 0; $i < $result['instalments']; $i++) {
            $interest = $balance * $result['rate_per_period'];
            $principalPaid = min($balance, $result['emi'] - $interest);
            $balance = max(0, $balance - $principalPaid);

            $index = intdiv($i, $perYear);
            $rows[$index] ??= ['year' => $startYear + $index, 'principal' => 0.0, 'interest' => 0.0, 'balance' => 0.0];
            $rows[$index]['principal'] += $principalPaid;
            $rows[$index]['interest'] += $interest;
            $rows[$index]['balance'] = $balance;
        }

        return array_map(fn (array $row) => [
            'year' => $row['year'],
            'principal' => round($row['principal'], 2),
            'interest' => round($row['interest'], 2),
            'balance' => round($row['balance'], 2),
        ], array_values($rows));
    }

    /**
     * A quick yes/no before asking for twenty fields.
     *
     * @return array{eligible: bool, reasons: array<int, string>, max_loan: float}
     */
    public function eligibility(float $annualIncome, float $machineryPrice, float $downPayment, ?int $age = null): array
    {
        $reasons = [];

        // Lenders here typically cap the instalment near half of monthly income.
        $maxEmi = ($annualIncome / 12) * 0.5;
        $requested = max(0, $machineryPrice - $downPayment);
        $maxLoan = $maxEmi * 48;

        if ($annualIncome < 100000) {
            $reasons[] = __('Most lenders need an annual income above ₹1,00,000.');
        }

        if ($downPayment < $machineryPrice * 0.15) {
            $reasons[] = __('Lenders usually expect at least 15% as down payment.');
        }

        if ($requested > $maxLoan) {
            $reasons[] = __('The amount you need is high for this income. A larger down payment would help.');
        }

        if ($age !== null && ($age < 21 || $age > 65)) {
            $reasons[] = __('Applicants are normally between 21 and 65 years old.');
        }

        return [
            'eligible' => $reasons === [],
            'reasons' => $reasons,
            'max_loan' => round(max(0, $maxLoan), -3),
        ];
    }
}
