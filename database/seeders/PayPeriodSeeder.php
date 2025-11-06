<?php

namespace Database\Seeders;

use App\Models\PayPeriod;
use Illuminate\Database\Seeder;

class PayPeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $today = now();
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();
        $monthName = $today->format('F');
        $year = $today->year;
        $daysInMonth = $endOfMonth->day;

        $periods = [
            [
                'start_date' => $startOfMonth->copy(),
                'end_date' => $startOfMonth->copy()->addDays(14),
                'pay_date' => $startOfMonth->copy()->addDays(17),
                'label' => '1-15 '.$monthName.' '.$year,
            ],
            [
                'start_date' => $startOfMonth->copy()->addDays(15),
                'end_date' => $endOfMonth->copy(),
                'pay_date' => $startOfMonth->copy()->addMonth()->startOfMonth()->addDays(2),
                'label' => '16-'.$daysInMonth.' '.$monthName.' '.$year,
            ],
        ];

        foreach ($periods as $period) {
            PayPeriod::firstOrCreate($period);
        }
    }
}
