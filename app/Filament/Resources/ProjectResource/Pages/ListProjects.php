<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Imports\ProjectImport;
use App\Exports\ProjectImportTemplate;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->action(function () {
                    return Excel::download(new ProjectImportTemplate(), 'project_import_template.xlsx');
                })
                ->modalHeading('Download Import Template')
                ->modalDescription('Download this template, fill it with your project data, and then use the Import button to upload it.')
                ->requiresConfirmation(false),
            
            Actions\Action::make('import')
                ->label('Import Projects')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('📥 Import Projects from File')
                ->modalDescription('Upload an Excel or CSV file to create multiple projects at once. Make sure your file follows the template format.')
                ->modalIcon('heroicon-o-document-arrow-up')
                ->form([
                    Forms\Components\Placeholder::make('instructions')
                        ->content('📋 **Before importing:**
                        
1. Download the template using the "Download Template" button
2. Fill in all required fields: name, code, start_date, status
3. Make sure project codes are unique (no duplicates)
4. Use date format: YYYY-MM-DD (e.g., 2026-01-15)
5. Status must be: pending, active, or completed
6. End date must be after or equal to start date
7. Save your file as .xlsx or .csv
8. Upload it below

The system will validate each row and show you detailed errors if anything is wrong.')
                        ->columnSpanFull(),
                    
                    Forms\Components\FileUpload::make('file')
                        ->label('Upload File')
                        ->helperText('Supported formats: Excel (.xlsx, .xls) or CSV (.csv)')
                        ->acceptedFileTypes([
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv',
                            'text/plain',
                        ])
                        ->required()
                        ->maxSize(5120) // 5MB
                        ->directory('imports'),
                ])
                ->action(function (array $data) {
                    $file = storage_path('app/public/' . $data['file']);
                    
                    $import = new ProjectImport();
                    
                    try {
                        Excel::import($import, $file);
                        
                        $successCount = $import->getSuccessCount();
                        $failureCount = $import->getFailureCount();
                        
                        if ($import->hasErrors()) {
                            $errorMessages = collect($import->getErrors())
                                ->map(function ($error) {
                                    $rowNum = $error['row'];
                                    $errors = implode(', ', $error['errors']);
                                    return "Row {$rowNum}: {$errors}";
                                })
                                ->join("\n");
                            
                            Notification::make()
                                ->title("Import completed with errors")
                                ->body("✅ Successfully created: {$successCount} projects\n❌ Failed: {$failureCount} rows\n\nErrors:\n{$errorMessages}")
                                ->warning()
                                ->duration(15000)
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Import successful!')
                                ->body("Successfully created {$successCount} projects.")
                                ->success()
                                ->send();
                        }
                        
                        // Delete the uploaded file
                        @unlink($file);
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import failed')
                            ->body('An error occurred: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            
            Actions\CreateAction::make(),
        ];
    }
}
