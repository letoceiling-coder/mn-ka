<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            MenuSeeder::class,
            QuizImagesSeeder::class,
            QuizSeeder::class,
            HowWorkBlockSettingsSeeder::class,
            FaqBlockSettingsSeeder::class,
            CopyMediaFilesSeeder::class,
            ProductsServicesOptionsCasesSeeder::class,
            AboutSettingsSeeder::class,
            ContactSettingsSeeder::class,
            RegisterAllMediaFilesSeeder::class, // Регистрируем все медиа файлы в таблицу media
            UpdateMediaFolderSeeder::class, // Обновляем папки для всех медиа файлов
        ]);
    }
}
