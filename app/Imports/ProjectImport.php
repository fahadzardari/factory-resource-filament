<?php

namespace App\Imports;

use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Carbon\Carbon;

class ProjectImport implements ToCollection, WithHeadingRow, SkipsOnFailure
{
    protected array $errors = [];
    protected int $successCount = 0;
    protected int $failureCount = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because index starts at 0 and we have header row
            
            // Validate each row
            $validator = Validator::make($row->toArray(), [
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:50', 'unique:projects,code'],
                'location' => ['nullable', 'string', 'max:255'],
                'start_date' => ['required', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                'status' => ['required', 'string', 'in:pending,active,completed'],
                'description' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                $this->failureCount++;
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => $validator->errors()->all(),
                ];
                continue;
            }

            try {
                // Parse dates
                $startDate = $this->parseDate($row['start_date']);
                $endDate = $row['end_date'] ? $this->parseDate($row['end_date']) : null;
                
                if (!$startDate) {
                    throw new \Exception('Invalid start date format. Use YYYY-MM-DD or DD/MM/YYYY');
                }
                
                if ($endDate && $endDate < $startDate) {
                    throw new \Exception('End date must be after or equal to start date');
                }
                
                Project::create([
                    'name' => $row['name'],
                    'code' => $row['code'],
                    'location' => $row['location'] ?? null,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => strtolower($row['status']),
                    'description' => $row['description'] ?? null,
                ]);
                
                $this->successCount++;
            } catch (\Exception $e) {
                $this->failureCount++;
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => ['Failed to create project: ' . $e->getMessage()],
                ];
            }
        }
    }

    protected function parseDate($date)
    {
        if (!$date) {
            return null;
        }
        
        try {
            // Try various date formats
            if (is_numeric($date)) {
                // Excel serial date
                return Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($date);
            }
            
            // Try common formats
            $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'];
            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $date)->format('Y-m-d');
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            // Last resort: let Carbon parse it
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errors[] = [
                'row' => $failure->row(),
                'errors' => $failure->errors(),
            ];
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getFailureCount(): int
    {
        return $this->failureCount;
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }
}
