<?php

namespace App\Imports;

use App\Models\Resource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;

class ResourceImport implements ToCollection, WithHeadingRow, SkipsOnFailure
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
                'sku' => ['required', 'string', 'max:50', 'unique:resources,sku'],
                'category' => ['nullable', 'string', 'max:100'],
                'base_unit' => ['required', 'string', 'max:20'],
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
                Resource::create([
                    'name' => $row['name'],
                    'sku' => $row['sku'],
                    'category' => $row['category'] ?? null,
                    'base_unit' => $row['base_unit'],
                    'description' => $row['description'] ?? null,
                ]);
                
                $this->successCount++;
            } catch (\Exception $e) {
                $this->failureCount++;
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => ['Failed to create resource: ' . $e->getMessage()],
                ];
            }
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
