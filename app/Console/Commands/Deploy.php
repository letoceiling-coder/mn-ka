<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class Deploy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy 
                            {--message= : Кастомное сообщение для коммита}
                            {--skip-build : Пропустить npm run build}
                            {--dry-run : Показать что будет сделано без выполнения}
                            {--insecure : Отключить проверку SSL сертификата (для разработки)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Деплой проекта: сборка, коммит в git, отправка на сервер';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Начало процесса деплоя...');
        $this->newLine();

        $dryRun = $this->option('dry-run');

        try {
            // Шаг 1: Сборка фронтенда
            if (!$this->option('skip-build')) {
                $this->buildFrontend($dryRun);
            } else {
                $this->warn('⚠️  Пропущена сборка фронтенда (--skip-build)');
            }

            // Шаг 2: Проверка git статуса
            $hasChanges = $this->checkGitStatus($dryRun);
            
            if (!$hasChanges && !$dryRun) {
                $this->warn('⚠️  Нет изменений для коммита.');
                if (!$this->confirm('Продолжить деплой без изменений?', false)) {
                    $this->info('Деплой отменен.');
                    return 0;
                }
            }

            // Шаг 3: Добавление изменений в git
            if ($hasChanges) {
                $this->addChangesToGit($dryRun);
                
                // Шаг 4: Создание коммита
                $commitMessage = $this->createCommit($dryRun);
                
                // Шаг 5: Отправка в репозиторий
                $this->pushToRepository($dryRun);
            }

            // Шаг 6: Отправка POST запроса на сервер
            if (!$dryRun) {
                $this->sendDeployRequest();
            } else {
                $this->info('📤 [DRY-RUN] Отправка POST запроса на сервер пропущена');
            }

            $this->newLine();
            $this->info('✅ Деплой успешно завершен!');
            return 0;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Ошибка деплоя: ' . $e->getMessage());
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            return 1;
        }
    }

    /**
     * Сборка фронтенда
     */
    protected function buildFrontend(bool $dryRun): void
    {
        $this->info('📦 Шаг 1: Сборка фронтенда...');
        
        if ($dryRun) {
            $this->line('  [DRY-RUN] Выполнение: npm run build');
            return;
        }

        $process = Process::run('npm run build');

        if (!$process->successful()) {
            throw new \Exception("Ошибка сборки фронтенда:\n" . $process->errorOutput());
        }

        // Проверяем наличие изменений в public/build
        if (!File::exists(public_path('build'))) {
            throw new \Exception('Директория public/build не найдена после сборки');
        }

        $this->info('  ✅ Сборка завершена успешно');
        $this->newLine();
    }

    /**
     * Проверка git статуса
     */
    protected function checkGitStatus(bool $dryRun): bool
    {
        $this->info('📋 Шаг 2: Проверка статуса git...');
        
        if ($dryRun) {
            $this->line('  [DRY-RUN] Выполнение: git status');
            return true;
        }

        $process = Process::run('git status --porcelain');
        
        if (!$process->successful()) {
            throw new \Exception("Ошибка проверки git статуса:\n" . $process->errorOutput());
        }

        $output = trim($process->output());
        $hasChanges = !empty($output);

        if ($hasChanges) {
            $this->line('  📝 Найдены изменения:');
            $this->line($output);
        } else {
            $this->line('  ℹ️  Изменений не обнаружено');
        }

        $this->newLine();
        return $hasChanges;
    }

    /**
     * Добавление изменений в git
     */
    protected function addChangesToGit(bool $dryRun): void
    {
        $this->info('➕ Шаг 3: Добавление изменений в git...');
        
        if ($dryRun) {
            $this->line('  [DRY-RUN] Выполнение: git add .');
            $this->line('  [DRY-RUN] Выполнение: git add public/build');
            return;
        }

        $process = Process::run('git add .');
        
        if (!$process->successful()) {
            throw new \Exception("Ошибка добавления файлов в git:\n" . $process->errorOutput());
        }

        // Убеждаемся что public/build добавлен
        $process2 = Process::run('git add public/build');
        
        if (!$process2->successful()) {
            $this->warn('  ⚠️  Предупреждение: не удалось добавить public/build');
        }

        $this->info('  ✅ Файлы добавлены в git');
        $this->newLine();
    }

    /**
     * Создание коммита
     */
    protected function createCommit(bool $dryRun): string
    {
        $this->info('💾 Шаг 4: Создание коммита...');
        
        $customMessage = $this->option('message');
        $commitMessage = $customMessage ?: 'Deploy: ' . now()->format('Y-m-d H:i:s');
        
        if ($dryRun) {
            $this->line("  [DRY-RUN] Выполнение: git commit -m \"{$commitMessage}\"");
            return $commitMessage;
        }

        $process = Process::run(['git', 'commit', '-m', $commitMessage]);

        if (!$process->successful()) {
            // Возможно, коммит уже существует или нет изменений
            $errorOutput = $process->errorOutput();
            if (strpos($errorOutput, 'nothing to commit') !== false) {
                $this->warn('  ⚠️  Нет изменений для коммита');
                return $commitMessage;
            }
            throw new \Exception("Ошибка создания коммита:\n" . $errorOutput);
        }

        $this->info("  ✅ Коммит создан: {$commitMessage}");
        $this->newLine();
        return $commitMessage;
    }

    /**
     * Отправка в репозиторий
     */
    protected function pushToRepository(bool $dryRun): void
    {
        $this->info('📤 Шаг 5: Отправка в репозиторий...');
        
        // Определяем текущую ветку
        $branchProcess = Process::run('git rev-parse --abbrev-ref HEAD');
        $branch = trim($branchProcess->output()) ?: 'main';
        
        if ($dryRun) {
            $this->line("  [DRY-RUN] Выполнение: git push origin {$branch}");
            return;
        }

        $process = Process::run("git push origin {$branch}");

        if (!$process->successful()) {
            throw new \Exception("Ошибка отправки в репозиторий:\n" . $process->errorOutput());
        }

        $this->info("  ✅ Изменения отправлены в ветку: {$branch}");
        $this->newLine();
    }

    /**
     * Отправка POST запроса на сервер
     */
    protected function sendDeployRequest(): void
    {
        $this->info('🌐 Шаг 6: Отправка запроса на сервер...');
        
        $serverUrl = env('SERVER_URL');
        $deployToken = env('DEPLOY_TOKEN');

        if (!$serverUrl) {
            throw new \Exception('SERVER_URL не настроен в .env');
        }

        if (!$deployToken) {
            throw new \Exception('DEPLOY_TOKEN не настроен в .env');
        }

        // Получаем текущий commit hash
        $commitProcess = Process::run('git rev-parse HEAD');
        $commitHash = trim($commitProcess->output()) ?: 'unknown';

        // Формируем правильный URL (убираем дублирование пути)
        $deployUrl = rtrim($serverUrl, '/');
        // Проверяем, содержит ли URL уже путь /api/deploy
        if (!str_contains($deployUrl, '/api/deploy')) {
            $deployUrl .= '/api/deploy';
        }

        $this->line("  📡 URL: {$deployUrl}");
        $this->line("  🔑 Commit: " . substr($commitHash, 0, 7));

        try {
            $httpClient = Http::timeout(300); // 5 минут таймаут

            // Отключить проверку SSL для локальной разработки (если указана опция)
            if ($this->option('insecure') || env('APP_ENV') === 'local') {
                $httpClient = $httpClient->withoutVerifying();
                if ($this->option('insecure')) {
                    $this->warn('  ⚠️  Проверка SSL сертификата отключена (--insecure)');
                } else {
                    $this->line('  ℹ️  Проверка SSL отключена (локальное окружение)');
                }
            }

            $response = $httpClient->withHeaders([
                    'X-Deploy-Token' => $deployToken,
                    'Content-Type' => 'application/json',
                ])
                ->post($deployUrl, [
                    'commit_hash' => $commitHash,
                    'deployed_by' => get_current_user(),
                    'timestamp' => now()->toDateTimeString(),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $this->newLine();
                $this->info('  ✅ Сервер ответил успешно:');
                $this->line("     PHP: {$data['data']['php_path']} (v{$data['data']['php_version']})");
                $this->line("     Git Pull: {$data['data']['git_pull']}");
                $this->line("     Composer: {$data['data']['composer_install']}");
                
                if (isset($data['data']['migrations'])) {
                    $migrations = $data['data']['migrations'];
                    if ($migrations['status'] === 'success') {
                        $this->line("     Миграции: {$migrations['message']}");
                    } else {
                        $this->warn("     Миграции: ошибка - {$migrations['error']}");
                    }
                }
                
                $this->line("     Время выполнения: {$data['data']['duration_seconds']}с");
                $this->line("     Дата: {$data['data']['deployed_at']}");
            } else {
                $errorData = $response->json();
                throw new \Exception(
                    "Ошибка деплоя на сервере (HTTP {$response->status()}): " . 
                    ($errorData['message'] ?? $response->body())
                );
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \Exception("Не удалось подключиться к серверу: " . $e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception("Ошибка отправки запроса: " . $e->getMessage());
        }

        $this->newLine();
    }
}

