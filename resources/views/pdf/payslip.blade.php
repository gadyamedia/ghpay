<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payslip Template</title>
<style>
  body {
    font-family: Arial, sans-serif;
    font-size: 10pt;
    color: #000;
    margin: 40px 26px;
  }
  .header {
    margin-bottom: 20px;
  }
  .logo {
    display: block;
    margin-bottom: 10px;
  }
  .logo-placeholder {
    font-weight: bold;
    font-size: 12pt;
    margin-bottom: 10px;
  }
  .header-table {
    width: 100%;
    border-collapse: collapse;
  }
  .header-table td {
    border: none;
    padding: 3px 0;
  }
  .bold { font-weight: bold; }
  .right { text-align: right; }

  .payslip-table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #000;
  }
  .payslip-table th, .payslip-table td {
    padding: 6px 8px;
  }
  .payslip-table th {
    background-color: #d9d9d9;
    text-align: center;
    font-weight: bold;
    border-bottom: 1px solid #000;
  }
  /* Column right borders up to before Gross Earnings */
  .col-earnings, .col-rate, .col-hours {
    border-right: 1px solid #000;
  }
  /* Column right borders extending through totals */
  .col-amount, .col-deductions {
    border-right: 1px solid #000;
  }
  .right { text-align: right; }
  .center { text-align: center; }
  .gray-row {
    background-color: #d9d9d9;
    font-weight: bold;
    border-top: 2px solid #000;
    border-bottom: 2px solid #000;
  }
</style>
</head>
<body>
  @php
    $snapshotData = $payslip->user_snapshot ?? [];
    $employee = data_get($snapshotData, 'name', $payslip->user->name);
    $position = data_get($snapshotData, 'detail.position', optional($payslip->user->detail)->position);
    $hourlyRate = (float) data_get($snapshotData, 'detail.hourly_rate', optional($payslip->user->detail)->hourly_rate ?? 0);

    $earnings = $payslip->earnings ? $payslip->earnings->values() : collect();
    $deductions = $payslip->deductions ? $payslip->deductions->values() : collect();
    $maxRows = max($earnings->count(), $deductions->count());

    $pesoSymbol = '<span style="font-family: DejaVu Sans;">&#x20B1;</span>';

    $hoursDisplay = function ($value): string {
      $hours = (float) ($value ?? 0);

      if (abs($hours) < 0.005) {
        return '';
      }

      return number_format($hours, 2, '.', ',');
    };
    $formatCurrency = function ($value) use ($pesoSymbol): \Illuminate\Support\HtmlString {
      $amount = (float) ($value ?? 0);

      if (abs($amount) < 0.005) {
        return new \Illuminate\Support\HtmlString('');
      }

      $formatted = number_format($amount, 2, '.', ',');

      return new \Illuminate\Support\HtmlString($pesoSymbol . ' ' . $formatted);
    };
    $formatRate = function ($earning) use ($hourlyRate, $formatCurrency): \Illuminate\Support\HtmlString {
      if (! $earning || $earning->hours === null) {
        return new \Illuminate\Support\HtmlString('');
      }

      $multiplier = $earning->rate ?: 1;
      $effectiveRate = $hourlyRate * $multiplier;

      return $formatCurrency($effectiveRate);
    };
    $formatAmount = function ($value) use ($formatCurrency): \Illuminate\Support\HtmlString {
      return $formatCurrency($value);
    };

    $periodStartDate = $payslip->payPeriod?->start_date ? \Illuminate\Support\Carbon::parse($payslip->payPeriod->start_date) : null;
    $periodEndDate = $payslip->payPeriod?->end_date ? \Illuminate\Support\Carbon::parse($payslip->payPeriod->end_date) : null;
    $periodStart = optional($periodStartDate)->format('M d, Y');
    $periodEnd = optional($periodEndDate)->format('M d, Y');

    $logoPath = public_path('images/logo.svg');
    $logoDataUri = file_exists($logoPath)
      ? 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logoPath))
      : null;
  @endphp

  <div class="header">
  @if ($logoDataUri)
    <img src="{{ $logoDataUri }}" alt="GH Business Outsourcing Inc Logo" class="logo" height="55" width="175">
  @else
    <div class="logo-placeholder">GH Business Outsourcing Inc.</div>
  @endif
    <table class="header-table">
      <tr>
        <td class="bold">Company:</td>
        <td>GH Business Outsourcing Inc.</td>
      </tr>
      <tr>
        <td class="bold">Employee:</td>
        <td>{{ $employee }}</td>
      </tr>
      <tr>
        <td class="bold">Position:</td>
        <td>{{ $position ?? '—' }}</td>
      </tr>
      <tr>
        <td class="bold">Pay Period:</td>
        <td>{{ $periodStart ? $periodStart . ' – ' . $periodEnd : '—' }}</td>
      </tr>
    </table>
  </div>

  <table class="payslip-table">
    <thead>
      <tr>
        <th class="col-earnings">Earnings</th>
        <th class="col-rate">Hourly Rate</th>
        <th class="col-hours">Hours</th>
        <th class="col-amount">Amount</th>
        <th class="col-deductions">Deductions</th>
        <th>Amount</th>
      </tr>
    </thead>
    <tbody>
      @for ($i = 0; $i < $maxRows; $i++)
          @php
              $earning = $earnings[$i] ?? null;
              $deduction = $deductions[$i] ?? null;
          @endphp
          <tr>
              <td class="col-earnings">{{ $earning->label ?? '' }}</td>
              <td class="col-rate right">{!! $formatRate($earning) !!}</td>
              <td class="col-hours right">{{ $hoursDisplay(optional($earning)->hours) }}</td>
              <td class="col-amount right">{!! $formatAmount(optional($earning)->amount) !!}</td>
              <td class="col-deductions">{{ $deduction->label ?? '' }}</td>
              <td class="right">{!! $formatAmount(optional($deduction)->amount) !!}</td>
          </tr>
      @endfor

      @if ($payslip->late_deduction > 0 || $payslip->absence_deduction > 0)
          @if ($payslip->late_deduction > 0)
              <tr>
                  <td class="col-earnings"></td>
                  <td class="col-rate"></td>
                  <td class="col-hours"></td>
                  <td class="col-amount"></td>
                  <td class="col-deductions">Late Deduction</td>
                  <td class="right">{!! $formatAmount($payslip->late_deduction ?? 0) !!}</td>
              </tr>
          @endif
          @if ($payslip->absence_deduction > 0)
              <tr>
                  <td class="col-earnings"></td>
                  <td class="col-rate"></td>
                  <td class="col-hours"></td>
                  <td class="col-amount"></td>
                  <td class="col-deductions">Absence Deduction</td>
                  <td class="right">{!! $formatAmount($payslip->absence_deduction ?? 0) !!}</td>
              </tr>
          @endif
      @endif

      <tr class="gray-row">
        <td colspan="3" class="center">Gross Earnings</td>
  <td class="col-amount right">{!! $formatAmount($payslip->gross_earnings ?? 0) !!}</td>
  <td class="col-deductions center">Total Deductions</td>
  <td class="right">{!! $formatAmount($payslip->total_deductions ?? 0) !!}</td>
      </tr>
      <tr class="gray-row">
        <td colspan="5" class="center">Net Salary</td>
  <td class="right">{!! $formatAmount($payslip->net_salary ?? 0) !!}</td>
      </tr>
    </tbody>
  </table>
</body>
</html>
