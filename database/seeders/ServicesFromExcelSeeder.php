<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Service;
use App\Models\Chapter;
use App\Models\ProjectCase;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ServicesFromExcelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $excelPath = 'C:\Users\dsc-2\Downloads\Telegram Desktop\feed.xlsx';
        
        if (!file_exists($excelPath)) {
            $this->command->error("Файл не найден: {$excelPath}");
            return;
        }

        $this->command->info("Чтение Excel файла...");
        
        try {
            $spreadsheet = IOFactory::load($excelPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Пропускаем заголовок (первая строка)
            array_shift($rows);
            
            $this->command->info("Найдено строк: " . count($rows));
            
            $currentService = null;
            $currentChapter = null;
            $currentChapterId = null;
            $order = 0;
            $chapterOrder = 0;
            $caseOrder = 0;
            
            foreach ($rows as $rowIndex => $row) {
                $serviceName = trim($row[0] ?? '');
                $chapterName = trim($row[1] ?? '');
                $caseName = trim($row[2] ?? '');
                $description = trim($row[3] ?? '');
                $htmlText = trim($row[4] ?? '');
                $detailedText = trim($row[5] ?? '');
                
                // Если есть название услуги - создаем новую услугу
                if (!empty($serviceName)) {
                    $currentService = $this->createOrUpdateService($serviceName, $description, $htmlText, $detailedText, $order);
                    $order++;
                    $this->command->info("Создана/обновлена услуга: {$serviceName}");
                }
                
                // Если есть название раздела - создаем/находим раздел
                if (!empty($chapterName)) {
                    $currentChapter = $this->createOrUpdateChapter($chapterName, $chapterOrder);
                    $currentChapterId = $currentChapter->id;
                    $chapterOrder++;
                    $caseOrder = 0; // Сбрасываем порядок случаев для нового раздела
                    $this->command->info("Создан/найден раздел: {$chapterName}");
                }
                
                // Если есть название случая - создаем случай и связываем с разделом
                if (!empty($caseName) && $currentChapterId) {
                    $this->createOrUpdateCase($caseName, $currentChapterId, $description, $htmlText, $detailedText, $caseOrder);
                    $caseOrder++;
                    $this->command->info("Создан/обновлен случай: {$caseName} для раздела: {$currentChapter->name}");
                }
            }
            
            $this->command->info("Импорт завершен успешно!");
            
        } catch (\Exception $e) {
            $this->command->error("Ошибка при импорте: " . $e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
    
    private function createOrUpdateService($name, $description, $htmlText, $detailedText, $order)
    {
        $slug = Str::slug($name);
        
        // Проверяем уникальность slug
        $counter = 1;
        $originalSlug = $slug;
        while (Service::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        $descriptionData = [];
        if (!empty($description)) {
            $descriptionData['ru'] = $description;
        }
        if (!empty($detailedText)) {
            $descriptionData['detailed'] = $detailedText;
        }
        
        return Service::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'slug' => $slug,
                'description' => !empty($descriptionData) ? $descriptionData : null,
                'order' => $order,
                'is_active' => true,
            ]
        );
    }
    
    private function createOrUpdateChapter($name, $order)
    {
        return Chapter::firstOrCreate(
            ['name' => $name],
            [
                'name' => $name,
                'order' => $order,
                'is_active' => true,
            ]
        );
    }
    
    private function createOrUpdateCase($name, $chapterId, $description, $htmlText, $detailedText, $order)
    {
        $slug = Str::slug($name);
        
        // Проверяем уникальность slug
        $counter = 1;
        $originalSlug = $slug;
        while (ProjectCase::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        $descriptionData = [];
        if (!empty($description)) {
            $descriptionData['ru'] = $description;
        }
        if (!empty($detailedText)) {
            $descriptionData['detailed'] = $detailedText;
        }
        
        $htmlData = null;
        if (!empty($htmlText)) {
            $htmlData = ['content' => $htmlText];
        }
        
        return ProjectCase::updateOrCreate(
            [
                'name' => $name,
                'chapter_id' => $chapterId,
            ],
            [
                'name' => $name,
                'slug' => $slug,
                'description' => !empty($descriptionData) ? $descriptionData : null,
                'html' => $htmlData,
                'chapter_id' => $chapterId,
                'order' => $order,
                'is_active' => true,
            ]
        );
    }
}

