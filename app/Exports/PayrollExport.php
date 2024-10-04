<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class PayrollExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
	protected $record;

	public function __construct($record)
	{
		$this->record = $record;
	}

	public function collection(): Collection
	{
		$collection = collect($this->record)->filter(function ($employee) {
			return is_array($employee);
		})->map(function ($employee) {
			return [
				'ID' => $employee['EmployeeID'] ?? '',
				'Name' => $employee['first_name'] . ' ' . ($employee['middle_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''),
				'Position' => $employee['position'] ?? '',
				'Project Site' => $employee['project'] ?? '',
				'Monthly Salary' => $employee['monthlySalary'] ?? 0,
				'Hourly Rate' => $employee['hourlyRate'] ?? 0,
				'Salary Type' => $employee['SalaryType'] ?? '',
				'Regular Status' => $employee['RegularStatus'] ?? '',
				'Regular Total Hours' => $employee['TotalHours'] ?? 0,
				'Regular O.T Hours' => $employee['TotalOvertimeHours'] ?? 0,
				'Sunday Hours' => $employee['TotalHoursSunday'] ?? 0,
				'Legal Holiday Hours' => $employee['TotalHrsRegularHol'] ?? 0,
				'Special Holiday Hours' => $employee['TotalHrsSpecialHol'] ?? 0,
				// 'Special Holiday Per Day' => $employee['TotalHrsSpecialHol'] ? ($employee['TotalHrsSpecialHol']/8) : 0,
				'Paid Amount For Regular Hours (Basic Pay)' => $employee['BasicPay'] ?? 0,
				'Paid Amount For O.T Hours 25%' => $employee['TotalOvertimePay'] ?? 0,
				'Paid Amount For Sunday Hours 30%' => $employee['SundayPay'] ?? 0,
				'Paid Amount For Legal Holiday' => $employee['RegularHolidayPay'] ?? 0,
				'Paid Amount For Special Holiday 30%' => $employee['SpecialHolidayPay'] ?? 0,
				'Other Allowance' => $employee['EarningPay'] ?? 0,
				'Gross Amount' => $employee['GrossPay'] ?? 0,
'TAXES' =>  0,
'SSS' => $employee['SSSDeduction'] ?? 0,
'PHIC' => $employee['PhilHealthDeduction'] ?? 0,
'HDMF' => $employee['PagIbigDeduction'] ?? 0,
// SSS LOAN
// HDMF LOAN
'Total Government Deduction' => $employee['TotalGovDeductions'] ?? 0,

'CASH ADVANCES' => $employee['DeductionFee'] ?? 0,
// SALARY ADJUSTMENT
				'Total Office Deduction & Adjustment' => $employee['TotalOfficeDeductions'] ?? 0,
				'Total Deductios & Adjustment' => $employee['TotalDeductions'] ?? 0,
				'NET PAY' => $employee['NetPay'] ?? 0,
				// 'Employment Type' => $employee['EmployeeStatus'] ?? '',
				// 'Payroll Month' => $employee['PayrollMonth'] ?? '',
				// 'Payroll Year' => $employee['PayrollYear'] ?? '',
				// 'Payroll Frequency' => $employee['PayrollFrequency'] ?? '',
			];
		});

		return $collection;
	}

	public function headings(): array
	{
		return [
			'ID',
			'Name',
			'Position',
			'Project Site' ,
			'Monthly Salary',
			'Hourly Rate' ,
			'Salary Type',
			'Regular Status',
			'Regular Total Hours',
			'Regular O.T Hours',
			'Sunday Hours',
			'Legal Holiday Hours',
			'Special Holiday Hours' ,
			// 'Special Holiday Per Day',
			'Paid Amount For Regular Hours (Basic Pay)' ,
			'Paid Amount For O.T Hours 25%',
			'Paid Amount For Sunday Hours 30%',
			'Paid Amount For Legal Holiday',
			'Paid Amount For Special Holiday 30%',
			'Other Allowance',
			'Gross Amount',
'TAXES' ,
'SSS',
'PHIC',
'HDMF',
// SSS LOAN
// HDMF LOAN
'Total Government Deduction',
 'CASH ADVANCES',
//'SALARY ADJUSTMENT',
'Total Office Deduction & Adjustment',
			'Total Deductios & Adjustment',
			'NET PAY',
		];
	}

	public function styles(Worksheet $sheet)
	{
		return [
			// Style the headings
			1 => ['font' => ['bold' => true]],
		];
	}
}
