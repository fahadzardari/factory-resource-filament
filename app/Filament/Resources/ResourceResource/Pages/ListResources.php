<?php

namespace App\Filament\Resources\ResourceResource\Pages;

use App\Filament\Resources\ResourceResource;
use App\Imports\ResourceImport;
use App\Exports\ResourceImportTemplate;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;

class ListResources extends ListRecords
{
    protected static string $resource = ResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->action(function () {
                    return Excel::download(new ResourceImportTemplate(), 'resource_import_template.xlsx');
                })
                ->modalHeading('Download Import Template')
                ->modalDescription('Download this template, fill it with your resource data, and then use the Import button to upload it.')
                ->requiresConfirmation(false),
            
            Actions\Action::make('import')
                ->label('Import Resources')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('📥 Import Resources from File')
                ->modalDescription('Upload an Excel or CSV file to create multiple resources at once. Make sure your file follows the template format.')
                ->modalIcon('heroicon-o-document-arrow-up')
                ->form([
                    Forms\Components\Placeholder::make('instructions')
                        ->content('📋 **Before importing:**
                        
1. Download the template using the "Download Template" button
2. Fill in all required fields: name, sku, base_unit
3. Make sure SKU codes are unique (no duplicates)
4. Save your file as .xlsx or .csv
5. Upload it below

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
                    
                    $import = new ResourceImport();
                    
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
                                ->body("✅ Successfully created: {$successCount} resources\n❌ Failed: {$failureCount} rows\n\nErrors:\n{$errorMessages}")
                                ->warning()
                                ->duration(15000)
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Import successful!')
                                ->body("Successfully created {$successCount} resources.")
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

