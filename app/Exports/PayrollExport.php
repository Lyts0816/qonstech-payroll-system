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
				'ID' => $employee['id'] ?? '',
				'Name' => $employee['first_name'] . ' ' . ($employee['middle_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''),
				'Employment Type' => $employee['EmployeeStatus'] ?? '',
				'Gross Pay' => $employee['GrossPay'] ?? 0,
				'Deductions' => $employee['TotalDeductions'] ?? 0,
				'Net Pay' => $employee['NetPay'] ?? 0,
				'Payroll Month' => $employee['PayrollMonth'] ?? '',
				'Payroll Year' => $employee['PayrollYear'] ?? '',
				'Total Hours' => $employee['TotalHours'] ?? 0,
				'Basic Pay' => $employee['BasicPay'] ?? 0,
				'Regular Holiday Pay' => $employee['RegularHolidayPay'] ?? 0,
				'Position' => $employee['position'] ?? '',
				'Monthly Salary' => $employee['monthlySalary'] ?? 0,
				'Sunday Pay' => $employee['SundayPay'] ?? 0,
				'Hourly Rate' => $employee['hourlyRate'] ?? 0,
				'Salary Type' => $employee['SalaryType'] ?? '',
				'Regular Status' => $employee['RegularStatus'] ?? '',
				'Payroll Frequency' => $employee['PayrollFrequency'] ?? '',
			];
		});

		return $collection;
	}

	public function headings(): array
	{
		return [
			'ID',
			'Name',
			'Employment Type',
			'Gross Pay',
			'Deductions',
			'Net Pay',
			'Payroll Month',
			'Payroll Year',
			'Total Hours',
			'Basic Pay',
			'Regular Holiday Pay',
			'Position',
			'Monthly Salary',
			'Sunday Pay',
			'Hourly Rate',
			'Salary Type',
			'Regular Status',
			'Payroll Frequency'
			// Add more headings as needed
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
