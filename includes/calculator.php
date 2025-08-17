<?php

class LoanCalculator {

    /**
     * Calculates the total repayable amount for a bullet repayment loan.
     * Interest is compounded monthly.
     *
     * @param float $principal The principal loan amount.
     * @param float $annualRate The annual interest rate (e.g., 12.5 for 12.5%).
     * @param int $tenureMonths The loan tenure in months.
     * @return float The total amount to be repaid at the end of the tenure.
     */
    public static function calculateBulletRepayment(float $principal, float $annualRate, int $tenureMonths): float {
        if ($principal <= 0 || $annualRate < 0 || $tenureMonths <= 0) {
            return $principal;
        }

        $monthlyRate = ($annualRate / 100) / 12;

        // Formula for compound interest: A = P(1 + r)^n
        $totalAmount = $principal * pow((1 + $monthlyRate), $tenureMonths);

        return round($totalAmount, 2);
    }

    /**
     * Calculates the Equated Monthly Installment (EMI) for a loan.
     *
     * @param float $principal The principal loan amount.
     * @param float $annualRate The annual interest rate (e.g., 12.5 for 12.5%).
     * @param int $tenureMonths The loan tenure in months.
     * @return float The monthly EMI amount.
     */
    public static function calculateEMI(float $principal, float $annualRate, int $tenureMonths): float {
        if ($principal <= 0 || $tenureMonths <= 0) {
            return 0;
        }
        // If interest rate is 0, EMI is just principal / tenure
        if ($annualRate == 0) {
            return round($principal / $tenureMonths, 2);
        }

        $monthlyRate = ($annualRate / 100) / 12;

        // EMI formula: E = P * r * (1 + r)^n / ((1 + r)^n - 1)
        $numerator = $principal * $monthlyRate * pow(1 + $monthlyRate, $tenureMonths);
        $denominator = pow(1 + $monthlyRate, $tenureMonths) - 1;

        if ($denominator == 0) {
            // This case is unlikely with positive rates and tenure, but good to have.
            return 0;
        }

        $emi = $numerator / $denominator;

        return round($emi, 2);
    }

    /**
     * Generates the full repayment schedule for an EMI loan.
     *
     * @param float $principal The principal loan amount.
     * @param float $annualRate The annual interest rate.
     * @param int $tenureMonths The loan tenure in months.
     * @param string $loanStartDateStr The start date of the loan ('Y-m-d').
     * @return array An array of associative arrays, each representing an installment.
     */
    public static function generateEMISchedule(float $principal, float $annualRate, int $tenureMonths, string $loanStartDateStr): array {
        if ($principal <= 0 || $tenureMonths <= 0) {
            return [];
        }

        $emi = self::calculateEMI($principal, $annualRate, $tenureMonths);
        $monthlyRate = ($annualRate / 100) / 12;
        $schedule = [];
        $outstandingPrincipal = $principal;
        $loanDate = new DateTime($loanStartDateStr);

        for ($month = 1; $month <= $tenureMonths; $month++) {
            $interestComponent = round($outstandingPrincipal * $monthlyRate, 2);
            $principalComponent = round($emi - $interestComponent, 2);

            // On the last month, adjust the EMI to match the remaining balance to avoid rounding errors
            if ($month == $tenureMonths) {
                $principalComponent = $outstandingPrincipal;
                $emi = $outstandingPrincipal + $interestComponent;
            }

            $outstandingPrincipal -= $principalComponent;

            // Calculate due date for this installment
            $dueDate = clone $loanDate;
            $dueDate->modify("+$month month");

            $schedule[] = [
                'due_date' => $dueDate->format('Y-m-d'),
                'installment_amount' => $emi,
                'principal_component' => $principalComponent,
                'interest_component' => $interestComponent,
                'outstanding_balance' => $outstandingPrincipal > 0 ? round($outstandingPrincipal, 2) : 0,
            ];
        }

        return $schedule;
    }
}
?>
