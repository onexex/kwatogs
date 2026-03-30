<?php

namespace App\Exports;

use App\Models\empDetail;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EmployeeInformationExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function collection()
    {
          return empDetail::with([
                'user',
                'employeeInformation',
                'company',
                'classification',
                'department',
                'position',
                'immediateSupervisor'
            ])
            ->when($this->request->date_from, fn($q) =>
                $q->whereDate('empDateHired', '>=', $this->request->date_from)
            )
            ->when($this->request->date_to, fn($q) =>
                $q->whereDate('empDateHired', '<=', $this->request->date_to)
            )
            ->when($this->request->classification_id, fn($q) =>
                $q->where('classification_id', $this->request->classification_id)
            )
            ->when($this->request->company_id, fn($q) =>
                $q->where('company_id', $this->request->company_id)
            )
            ->when($this->request->department_id, fn($q) =>
                $q->where('department_id', $this->request->department_id)
            )
            ->when($this->request->position_id, fn($q) =>
                $q->where('position_id', $this->request->position_id)
            )
            ->get()
            ->map(function ($employee) {
                return [
                    $employee->empID,
                    $employee->user?->fname . ' ' . $employee->user?->lname,
                    $employee->user?->suffix,
                    $employee->employeeInformation?->gender,
                    $employee->employeeInformation?->citizenship,
                    optional($employee->employeeInformation?->empBdate)->format('F d, Y'),
                    $this->civilStatus($employee),
                    $employee->employeeInformation?->empPContact,
                    $employee->employeeInformation?->empEmail,
                    $employee->employeeInformation?->empAddStreet,
                    $employee->company?->comp_name,
                    $employee->classification?->class_desc,
                    $employee->department?->dep_name,
                    $employee->position?->pos_desc,
                    $employee->immediateSupervisor?->fname . ' ' . $employee->immediateSupervisor?->lname,
                    $employee->empStatus == '1' ? 'Employed' : 'Resigned',
                    optional($employee->empDateHired)->format('F d, Y'),
                    optional($employee->empDateRegular)->format('F d, Y'),
                    $employee->empBasic,
                    $employee->empAllowance,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'No',
            'Employee Name',
            'Suffix',
            'Gender',
            'Citizenship',
            'Date of Birth',
            'Civil Status',
            'Phone Number',
            'Email',
            'Address',
            'Company',
            'Classification',
            'Department',
            'Position',
            'Immediate Superior',
            'Status',
            'Date Hired',
            'Date Regular',
            'Basic Salary',
            'Allowance',
        ];
    }

    private function civilStatus($employee)
    {
        return match($employee->employeeInformation?->empCStatus) {
            '0' => 'Single',
            '1' => 'Married',
            '2' => 'Divorced',
            default => 'N/A'
        };
    }
}
