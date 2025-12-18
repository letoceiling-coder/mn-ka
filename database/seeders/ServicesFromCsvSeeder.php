<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Service;
use App\Models\Chapter;
use App\Models\ProjectCase;
use App\Models\Media;
use App\Models\Folder;
use App\Services\MediaImportService;
use Illuminate\Support\Facades\File;

class ServicesFromCsvSeeder extends Seeder
{
    private MediaImportService $mediaService;
    private ?int $servicesFolderId = null;
    private ?int $iconsFolderId = null;

    public function __construct()
    {
        $this->mediaService = new MediaImportService();
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Получаем путь к CSV файлу
        $csvPath = env('SERVICES_CSV_PATH') ?? $this->findCsvFile();
        
        // Для локальной разработки - проверяем стандартный путь
        if (!$csvPath || !file_exists($csvPath)) {
            $windowsPath = 'C:\Users\dsc-2\Downloads\111_extracted\services.csv';
            if (file_exists($windowsPath)) {
                $csvPath = $windowsPath;
            }
        }
        
        if (!$csvPath || !file_exists($csvPath)) {
            $this->command->error("CSV файл не найден!");
            $this->command->info("Укажите путь к файлу через .env (SERVICES_CSV_PATH)");
            $this->command->info("Или поместите файл services.csv в одну из стандартных директорий:");
            $this->command->info("  - " . base_path('services.csv'));
            $this->command->info("  - " . base_path('storage/app/services.csv'));
            $this->command->info("  - " . storage_path('app/services.csv'));
            return;
        }

        // Получаем путь к папке с изображениями
        // Сначала пробуем рядом с CSV
        $imagesPath = dirname($csvPath) . '/images';
        if (!is_dir($imagesPath)) {
            // Пробуем стандартные пути на сервере
            $possibleImagePaths = [
                base_path('images'),
                base_path('storage/app/images'),
                storage_path('app/images'),
                public_path('images'),
            ];
            
            foreach ($possibleImagePaths as $path) {
                if (is_dir($path)) {
                    $imagesPath = $path;
                    break;
                }
            }
            
            if (!is_dir($imagesPath)) {
                $this->command->warn("Папка с изображениями не найдена. Пробовались пути:");
                foreach ($possibleImagePaths as $path) {
                    $this->command->warn("  - {$path}");
                }
                $imagesPath = null;
            }
        }

        $this->command->info("Используется CSV файл: {$csvPath}");
        if ($imagesPath) {
            $this->command->info("Папка с изображениями: {$imagesPath}");
        }

        // Очистка старых данных (опционально, через флаг)
        $clearExisting = env('CLEAR_SERVICES_BEFORE_SEED', true); // По умолчанию очищаем
        
        if ($clearExisting) {
            $this->command->warn("Очистка существующих услуг, разделов и случаев...");
            $this->clearExistingData();
        }

        // Подготовка папок для медиа
        $this->prepareMediaFolders();

        $this->command->info("Чтение CSV файла...");
        
        try {
            $rows = $this->readCsvFile($csvPath);
            $this->command->info("Найдено строк: " . count($rows));
            
            $stats = [
                'services' => 0,
                'chapters' => 0,
                'cases' => 0,
                'images' => 0,
                'icons' => 0,
            ];
            
            foreach ($rows as $rowIndex => $row) {
                try {
                    // Парсим строку CSV (разделитель ;)
                    $serviceId = $this->cleanValue($row[0] ?? '');
                    $serviceName = $this->cleanValue($row[1] ?? '');
                    $slug = $this->cleanValue($row[2] ?? '');
                    $description = $this->cleanValue($row[3] ?? '');
                    $htmlContent = $this->cleanValue($row[4] ?? '');
                    $chapterId = $this->cleanValue($row[5] ?? '');
                    $chapterName = $this->cleanValue($row[6] ?? '');
                    $imageId = $this->cleanValue($row[7] ?? '');
                    $imagePath = $this->cleanValue($row[8] ?? '');
                    $imageUrl = $this->cleanValue($row[9] ?? '');
                    $iconId = $this->cleanValue($row[10] ?? '');
                    $iconPath = $this->cleanValue($row[11] ?? '');
                    $iconUrl = $this->cleanValue($row[12] ?? '');
                    $order = (int)($row[13] ?? 0);
                    $isActive = (int)($row[14] ?? 1) === 1;
                    
                    // Пропускаем пустые строки
                    if (empty($serviceName)) {
                        continue;
                    }

                    // Создаем/обновляем раздел
                    $chapter = null;
                    if (!empty($chapterName)) {
                        $chapter = $this->createOrUpdateChapter($chapterName, (int)$chapterId);
                        $stats['chapters']++;
                    }

                    // Загружаем изображение
                    $imageMedia = null;
                    if (!empty($imagePath) && $imagesPath) {
                        $imageFileName = basename($imagePath);
                        $imageFullPath = $imagesPath . '/services/' . $imageFileName;
                        if (!file_exists($imageFullPath)) {
                            // Пробуем найти файл без учета регистра
                            $imageFullPath = $this->findFileCaseInsensitive($imagesPath . '/services', $imageFileName);
                        }
                        if ($imageFullPath && file_exists($imageFullPath)) {
                            $imageMedia = $this->uploadImage($imageFullPath, 'services');
                            if ($imageMedia) {
                                $stats['images']++;
                            }
                        } else {
                            $this->command->warn("    ⚠ Изображение не найдено: {$imageFileName}");
                        }
                    }

                    // Загружаем иконку
                    $iconMedia = null;
                    if (!empty($iconPath) && $imagesPath) {
                        $iconFileName = basename($iconPath);
                        $iconFullPath = $imagesPath . '/icons/' . $iconFileName;
                        if (!file_exists($iconFullPath)) {
                            // Пробуем найти файл без учета регистра
                            $iconFullPath = $this->findFileCaseInsensitive($imagesPath . '/icons', $iconFileName);
                        }
                        if ($iconFullPath && file_exists($iconFullPath)) {
                            $iconMedia = $this->uploadImage($iconFullPath, 'icons');
                            if ($iconMedia) {
                                $stats['icons']++;
                            }
                        } else {
                            $this->command->warn("    ⚠ Иконка не найдена: {$iconFileName}");
                        }
                    }

                    // Создаем/обновляем услугу
                    $service = $this->createOrUpdateService(
                        $serviceName,
                        $slug,
                        $description,
                        $htmlContent,
                        $chapter?->id,
                        $imageMedia?->id,
                        $iconMedia?->id,
                        $order,
                        $isActive
                    );
                    $stats['services']++;
                    
                    $this->command->info("✓ Услуга: {$serviceName}" . ($chapter ? " (Раздел: {$chapterName})" : ""));
                    
                } catch (\Exception $e) {
                    $this->command->error("Ошибка в строке " . ($rowIndex + 2) . ": " . $e->getMessage());
                    continue;
                }
            }
            
            $this->command->info("\n📊 Статистика импорта:");
            $this->command->info("  Услуг: {$stats['services']}");
            $this->command->info("  Разделов: {$stats['chapters']}");
            $this->command->info("  Изображений: {$stats['images']}");
            $this->command->info("  Иконок: {$stats['icons']}");
            
            $this->command->info("\n✅ Импорт завершен успешно!");
            
        } catch (\Exception $e) {
            $this->command->error("Ошибка при импорте: " . $e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }

    /**
     * Очистить существующие данные
     */
    private function clearExistingData(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Удаляем связи
        DB::table('product_service')->truncate();
        DB::table('cases_service')->truncate();
        
        // Удаляем случаи
        ProjectCase::truncate();
        
        // Удаляем услуги
        Service::truncate();
        
        // Удаляем разделы
        Chapter::truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->command->info("✅ Данные очищены");
    }

    /**
     * Подготовить папки для медиа
     */
    private function prepareMediaFolders(): void
    {
        // Находим или создаем папку "Услуги"
        $servicesFolder = Folder::firstOrCreate(
            ['slug' => 'services'],
            [
                'name' => 'Услуги',
                'slug' => 'services',
                'protected' => false,
            ]
        );
        $this->servicesFolderId = $servicesFolder->id;

        // Находим или создаем папку "Иконки"
        $iconsFolder = Folder::firstOrCreate(
            ['slug' => 'icons'],
            [
                'name' => 'Иконки',
                'slug' => 'icons',
                'protected' => false,
            ]
        );
        $this->iconsFolderId = $iconsFolder->id;
    }

    /**
     * Читать CSV файл
     */
    private function readCsvFile(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');
        
        if ($handle === false) {
            throw new \Exception("Не удалось открыть CSV файл: {$path}");
        }

        // Пропускаем заголовок
        fgetcsv($handle, 0, ';');

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) > 0) {
                $rows[] = $row;
            }
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Загрузить изображение
     */
    private function uploadImage(string $filePath, string $folderType): ?Media
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $folderId = $folderType === 'services' ? $this->servicesFolderId : $this->iconsFolderId;
        
        try {
            $media = $this->mediaService->uploadImageFromPath($filePath, $folderId);
            return $media ?: null;
        } catch (\Exception $e) {
            $this->command->warn("Не удалось загрузить изображение {$filePath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Создать или обновить услугу
     */
    private function createOrUpdateService(
        string $name,
        string $slug,
        string $description,
        string $htmlContent,
        ?int $chapterId,
        ?int $imageId,
        ?int $iconId,
        int $order,
        bool $isActive
    ): Service {
        // Обрезаем название до 255 символов
        $name = mb_substr($name, 0, 255);
        
        // Если slug пустой, генерируем из названия
        if (empty($slug) || $slug === '/') {
            $slug = Str::slug($name);
        } else {
            $slug = trim($slug, '/');
        }
        
        // Обрезаем slug до 255 символов
        if (mb_strlen($slug) > 255) {
            $slug = mb_substr($slug, 0, 252) . '-' . substr(md5($name), 0, 2);
        }
        
        // Если slug уже существует, но это не та же услуга - добавляем суффикс
        // Но только если slug был сгенерирован, а не взят из CSV
        // Для slug из CSV используем как есть (updateOrCreate обработает)
        
        // Парсим описание (может быть JSON)
        $descriptionData = null;
        if (!empty($description)) {
            $decoded = json_decode($description, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $descriptionData = $decoded;
            } else {
                $descriptionData = ['ru' => $description];
            }
        }
        
        $service = Service::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'slug' => $slug,
                'description' => $descriptionData,
                'html_content' => !empty($htmlContent) ? $htmlContent : null,
                'chapter_id' => $chapterId,
                'image_id' => $imageId,
                'icon_id' => $iconId,
                'order' => $order,
                'is_active' => $isActive,
            ]
        );
        
        return $service;
    }

    /**
     * Создать или обновить раздел
     */
    private function createOrUpdateChapter(string $name, ?int $id = null): Chapter
    {
        if ($id && $chapter = Chapter::find($id)) {
            $chapter->update(['name' => $name]);
            return $chapter;
        }

        return Chapter::firstOrCreate(
            ['name' => $name],
            [
                'name' => $name,
                'order' => 0,
                'is_active' => true,
            ]
        );
    }

    /**
     * Найти CSV файл в стандартных местах
     */
    private function findCsvFile(): ?string
    {
        $possiblePaths = [
            base_path('services.csv'),
            base_path('storage/app/services.csv'),
            base_path('database/seeders/services.csv'),
            '/home/d/dsc23ytp/stroy/public_html/services.csv',
            '/home/d/dsc23ytp/stroy/public_html/storage/app/services.csv',
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return null;
    }

    /**
     * Очистить значение от NaN и пустых строк
     */
    private function cleanValue($value): string
    {
        if (is_null($value) || $value === 'NaN' || $value === 'nan' || (is_string($value) && strtolower(trim($value)) === 'nan')) {
            return '';
        }
        
        if (is_float($value) && is_nan($value)) {
            return '';
        }
        
        return trim((string)$value);
    }

    /**
     * Найти файл без учета регистра
     */
    private function findFileCaseInsensitive(string $directory, string $filename): ?string
    {
        if (!is_dir($directory)) {
            return null;
        }

        $files = scandir($directory);
        $lowerFilename = strtolower($filename);
        
        foreach ($files as $file) {
            if (strtolower($file) === $lowerFilename) {
                return $directory . '/' . $file;
            }
        }
        
        return null;
    }
}

