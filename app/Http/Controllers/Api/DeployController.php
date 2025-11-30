<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

class DeployController extends Controller
{
    protected $phpPath;
    protected $phpVersion;
    protected $basePath;

    public function __construct()
    {
        $this->basePath = base_path();
    }

    /**
     * Выполнить деплой на сервере
     */
    public function deploy(Request $request)
    {
        $startTime = microtime(true);
        Log::info('🚀 Начало деплоя', [
            'ip' => $request->ip(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        $result = [
            'success' => false,
            'message' => '',
            'data' => [],
        ];

        try {
            // Определяем PHP путь
            $this->phpPath = $this->getPhpPath();
            $this->phpVersion = $this->getPhpVersion();

            Log::info("Используется PHP: {$this->phpPath} (версия: {$this->phpVersion})");

            // Получаем текущий commit hash
            $oldCommitHash = $this->getCurrentCommitHash();

            // 0. Очистка файлов разработки в начале (на случай остатков)
            $this->cleanDevelopmentFiles();

            // 1. Git pull
            $gitPullResult = $this->handleGitPull();
            $result['data']['git_pull'] = $gitPullResult['status'];
            if (!$gitPullResult['success']) {
                throw new \Exception("Ошибка git pull: {$gitPullResult['error']}");
            }

            // 2. Composer install
            $composerResult = $this->handleComposerInstall();
            $result['data']['composer_install'] = $composerResult['status'];
            if (!$composerResult['success']) {
                throw new \Exception("Ошибка composer install: {$composerResult['error']}");
            }

            // 2.5. Очистка кешей после composer install (важно: удаляет кеш провайдеров dev-пакетов)
            $this->clearPackageDiscoveryCache();

            // 3. Миграции
            $migrationsResult = $this->runMigrations();
            $result['data']['migrations'] = $migrationsResult;
            if ($migrationsResult['status'] !== 'success') {
                throw new \Exception("Ошибка миграций: {$migrationsResult['error']}");
            }

            // 4. Очистка временных файлов разработки
            $this->cleanDevelopmentFiles();

            // 5. Очистка кешей
            $cacheResult = $this->clearAllCaches();
            $result['data']['cache_cleared'] = $cacheResult['success'];

            // 6. Оптимизация
            $optimizeResult = $this->optimizeApplication();
            $result['data']['optimized'] = $optimizeResult['success'];

            // 7. Финальная очистка файлов разработки (в конце, после всех операций)
            $this->cleanDevelopmentFiles();

            // 8. Запускаем Artisan команду для очистки hot файла (дополнительная проверка)
            try {
                $cleanHotProcess = Process::path($this->basePath)
                    ->run("{$this->phpPath} artisan clean:hot --force");

                if ($cleanHotProcess->successful()) {
                    Log::info('Файл public/hot очищен через Artisan команду');
                }
            } catch (\Exception $e) {
                Log::warning('Ошибка при запуске clean:hot', ['error' => $e->getMessage()]);
            }

            // Получаем новый commit hash
            $newCommitHash = $this->getCurrentCommitHash();

            // Формируем успешный ответ
            $result['success'] = true;
            $result['message'] = 'Деплой успешно завершен';
            $result['data'] = array_merge($result['data'], [
                'php_version' => $this->phpVersion,
                'php_path' => $this->phpPath,
                'old_commit_hash' => $oldCommitHash,
                'new_commit_hash' => $newCommitHash,
                'commit_changed' => $oldCommitHash !== $newCommitHash,
                'deployed_at' => now()->toDateTimeString(),
                'duration_seconds' => round(microtime(true) - $startTime, 2),
            ]);

            Log::info('✅ Деплой успешно завершен', $result['data']);

        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
            $result['data']['error'] = $e->getMessage();
            $result['data']['trace'] = config('app.debug') ? $e->getTraceAsString() : null;
            $result['data']['deployed_at'] = now()->toDateTimeString();
            $result['data']['duration_seconds'] = round(microtime(true) - $startTime, 2);

            Log::error('❌ Ошибка деплоя', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Определить путь к PHP
     */
    protected function getPhpPath(): string
    {
        // 1. Проверить явно указанный путь в .env
        $phpPath = env('PHP_PATH');
        if ($phpPath && $this->isPhpExecutable($phpPath)) {
            return $phpPath;
        }

        // 2. Попробовать автоматически найти PHP
        $possiblePaths = ['php8.2', 'php8.3', 'php8.1', 'php'];
        foreach ($possiblePaths as $path) {
            if ($this->isPhpExecutable($path)) {
                return $path;
            }
        }

        // 3. Fallback на 'php'
        return 'php';
    }

    /**
     * Проверить доступность PHP
     */
    protected function isPhpExecutable(string $path): bool
    {
        try {
            // Проверка через which (Unix-like)
            $result = shell_exec("which {$path} 2>/dev/null");
            if ($result && trim($result)) {
                return true;
            }

            // Проверка через exec (версия PHP)
            exec("{$path} --version 2>&1", $output, $returnCode);
            return $returnCode === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Получить версию PHP
     */
    protected function getPhpVersion(): string
    {
        try {
            exec("{$this->phpPath} --version 2>&1", $output, $returnCode);
            if ($returnCode === 0 && isset($output[0])) {
                preg_match('/PHP\s+(\d+\.\d+\.\d+)/', $output[0], $matches);
                return $matches[1] ?? 'unknown';
            }
        } catch (\Exception $e) {
            // Ignore
        }
        return 'unknown';
    }

    /**
     * Выполнить git pull
     */
    protected function handleGitPull(): array
    {
        try {
            // Настройка безопасной директории для git (решает проблему dubious ownership)
            $this->ensureGitSafeDirectory();

            // Проверяем статус git перед pull
            $statusProcess = Process::path($this->basePath)
                ->run('git status --porcelain');

            $hasChanges = !empty(trim($statusProcess->output()));

            // Если есть локальные изменения, сохраняем их в stash
            if ($hasChanges) {
                Log::info('Обнаружены локальные изменения, сохраняем в stash...');
                $stashProcess = Process::path($this->basePath)
                    ->run('git stash push -m "Auto-stash before deploy ' . now()->toDateTimeString() . '"');

                if (!$stashProcess->successful()) {
                    Log::warning('Не удалось сохранить изменения в stash', [
                        'error' => $stashProcess->errorOutput(),
                    ]);
                }
            }

            // Сбрасываем неотслеживаемые файлы, которые могут конфликтовать
            $this->cleanUntrackedFiles();

            // Получаем текущий commit перед обновлением для сравнения
            $beforeCommit = $this->getCurrentCommitHash();
            Log::info("📦 Commit до обновления: " . ($beforeCommit ?: 'не определен'));
            
            // Проверяем текущий статус Git
            $statusOutput = Process::path($this->basePath)
                ->run('git status --short 2>&1');
            Log::info("📊 Текущий статус Git: " . trim($statusOutput->output() ?: 'чисто'));

            // Выполняем git pull с дополнительной настройкой безопасной директории
            $safeDirectoryPath = escapeshellarg($this->basePath);
            
            // 1. Сначала получаем последние изменения из репозитория
            Log::info("📥 Выполняем git fetch origin main...");
            $fetchProcess = Process::path($this->basePath)
                ->env([
                    'GIT_CEILING_DIRECTORIES' => dirname($this->basePath),
                ])
                ->run("git -c safe.directory={$safeDirectoryPath} fetch origin main 2>&1");
            
            if (!$fetchProcess->successful()) {
                Log::warning('⚠️ Не удалось выполнить git fetch', [
                    'output' => $fetchProcess->output(),
                    'error' => $fetchProcess->errorOutput(),
                ]);
            } else {
                Log::info('✅ Git fetch выполнен успешно', [
                    'output' => trim($fetchProcess->output() ?: 'нет вывода'),
                ]);
            }

            // 2. Проверяем, есть ли новые коммиты
            $checkAheadProcess = Process::path($this->basePath)
                ->run("git rev-list HEAD..origin/main --count 2>&1");
            $commitsAhead = trim($checkAheadProcess->output() ?: '0');
            Log::info("📊 Новых коммитов для загрузки: {$commitsAhead}");

            // 3. Сбрасываем локальную ветку на origin/main (принудительное обновление)
            Log::info("🔄 Выполняем git reset --hard origin/main...");
            $process = Process::path($this->basePath)
                ->env([
                    'GIT_CEILING_DIRECTORIES' => dirname($this->basePath),
                ])
                ->run("git -c safe.directory={$safeDirectoryPath} reset --hard origin/main 2>&1");
            
            Log::info("Git reset output: " . trim($process->output() ?: 'нет вывода'));
            if ($process->errorOutput()) {
                Log::warning("Git reset errors: " . trim($process->errorOutput()));
            }

            if (!$process->successful()) {
                Log::warning('Git reset --hard не удался, пробуем git pull', [
                    'error' => $process->errorOutput(),
                ]);
                
                // Если reset не удался, пробуем обычный pull
                $process = Process::path($this->basePath)
                    ->env([
                        'GIT_CEILING_DIRECTORIES' => dirname($this->basePath),
                    ])
                    ->run("git -c safe.directory={$safeDirectoryPath} pull origin main --no-rebase --force");
            }

            // 3. Получаем новый commit после обновления
            $afterCommit = $this->getCurrentCommitHash();
            Log::info("📦 Commit после обновления: " . ($afterCommit ?: 'не определен'));
            
            // 4. Проверяем, обновились ли файлы
            if ($beforeCommit && $afterCommit && $beforeCommit !== $afterCommit) {
                Log::info("✅ Код успешно обновлен: {$beforeCommit} -> {$afterCommit}");
                
                // Показываем измененные файлы
                try {
                    $diffProcess = Process::path($this->basePath)
                        ->run("git diff --name-only {$beforeCommit} {$afterCommit} 2>&1");
                    
                    $changedFiles = array_filter(explode("\n", trim($diffProcess->output())));
                    if (!empty($changedFiles)) {
                        $fileList = implode(', ', array_slice($changedFiles, 0, 10));
                        if (count($changedFiles) > 10) {
                            $fileList .= ' ... (всего ' . count($changedFiles) . ' файлов)';
                        }
                        Log::info("📝 Обновленные файлы: {$fileList}");
                    }
                } catch (\Exception $e) {
                    Log::warning('Не удалось получить список измененных файлов', [
                        'error' => $e->getMessage(),
                    ]);
                }
            } elseif ($beforeCommit && $afterCommit && $beforeCommit === $afterCommit) {
                Log::info("ℹ️ Код уже актуален, изменений нет");
            } else {
                Log::warning("⚠️ Не удалось определить состояние коммитов", [
                    'before' => $beforeCommit,
                    'after' => $afterCommit,
                    'message' => 'Возможно, Git репозиторий не инициализирован или есть проблемы с доступом',
                ]);
                
                // Дополнительная проверка: проверяем, что это Git репозиторий
                $gitCheckProcess = Process::path($this->basePath)
                    ->run("git rev-parse --is-inside-work-tree 2>&1");
                
                if (!$gitCheckProcess->successful() || trim($gitCheckProcess->output()) !== 'true') {
                    Log::error("❌ Это не Git репозиторий! Путь: {$this->basePath}");
                } else {
                    Log::info("✅ Это Git репозиторий, но commit hash не определен");
                }
            }
            
            // 5. Дополнительная проверка: список последних коммитов
            try {
                $logProcess = Process::path($this->basePath)
                    ->run("git log --oneline -3 2>&1");
                $lastCommits = trim($logProcess->output());
                if ($lastCommits) {
                    Log::info("📋 Последние 3 коммита:\n{$lastCommits}");
                }
            } catch (\Exception $e) {
                // Ignore
            }

            if ($process->successful()) {
                return [
                    'success' => true,
                    'status' => 'success',
                    'output' => $process->output(),
                    'had_local_changes' => $hasChanges,
                ];
            }

            return [
                'success' => false,
                'status' => 'error',
                'error' => $process->errorOutput() ?: $process->output(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Очистить неотслеживаемые файлы, которые могут конфликтовать
     */
    protected function cleanUntrackedFiles(): void
    {
        try {
            // Получаем список неотслеживаемых файлов
            $untrackedProcess = Process::path($this->basePath)
                ->run('git ls-files --others --exclude-standard');

            $untrackedFiles = array_filter(explode("\n", trim($untrackedProcess->output())));

            if (empty($untrackedFiles)) {
                return;
            }

            // Удаляем только файлы, которые точно должны быть в репозитории
            $filesToRemove = [
                'DEPLOY_NEXT_STEPS.md',
                'DEPLOY_SYSTEM_PLAN.md',
                'app/Console/Commands/ClearLogs.php',
                'app/Console/Commands/Deploy.php',
                'app/Http/Controllers/Api/DeployController.php',
                'app/Http/Middleware/VerifyDeployToken.php',
            ];

            foreach ($filesToRemove as $file) {
                $filePath = $this->basePath . '/' . $file;
                if (in_array($file, $untrackedFiles) && file_exists($filePath)) {
                    Log::info("Удаляем неотслеживаемый файл: {$file}");
                    @unlink($filePath);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Ошибка при очистке неотслеживаемых файлов', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Настроить безопасную директорию для git
     * Решает проблему "detected dubious ownership in repository"
     */
    protected function ensureGitSafeDirectory(): void
    {
        try {
            // Сначала пытаемся добавить в глобальную конфигурацию
            $process = Process::path($this->basePath)
                ->run("git config --global --add safe.directory {$this->basePath}");

            // Если глобально не получилось, пробуем локально
            if (!$process->successful()) {
                $processLocal = Process::path($this->basePath)
                    ->run("git config --local --add safe.directory {$this->basePath}");

                // Если и локально не получилось, используем переменную окружения
                if (!$processLocal->successful()) {
                    // Используем переменную окружения для текущей сессии
                    putenv("GIT_CEILING_DIRECTORIES=" . dirname($this->basePath));

                    // Альтернативный способ - через GIT_CONFIG
                    $gitConfig = "safe.directory={$this->basePath}";
                    putenv("GIT_CONFIG_GLOBAL={$gitConfig}");
                }
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки настройки - возможно, уже настроено или нет прав
            Log::warning('Не удалось настроить safe.directory для git', [
                'path' => $this->basePath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Выполнить composer install
     */
    protected function handleComposerInstall(): array
    {
        try {
            // Получаем путь к composer
            $composerPath = $this->getComposerPath();

            // Определяем HOME директорию (для composer)
            // Попробуем получить из пользователя или использовать базовую директорию
            $homeDir = getenv('HOME');
            if (!$homeDir) {
                // Попробуем определить по пути проекта или использовать временную директорию
                $projectUser = posix_getpwuid(posix_geteuid());
                $homeDir = $projectUser['dir'] ?? '/tmp';
            }

            // Используем PHP 8.2 для запуска composer
            // Добавляем --no-scripts временно, чтобы избежать проблем с prePackageUninstall
            // Затем запустим скрипты отдельно после успешной установки
            $command = "{$this->phpPath} {$composerPath} install --no-dev --optimize-autoloader --no-interaction --no-scripts";

            // Устанавливаем переменные окружения для composer и увеличиваем таймаут
            $process = Process::path($this->basePath)
                ->timeout(600) // 10 минут для composer install
                ->env([
                    'HOME' => $homeDir,
                    'COMPOSER_HOME' => $homeDir . '/.composer',
                    'COMPOSER_DISABLE_XDEBUG_WARN' => '1',
                ])
                ->run($command);

            // Если composer install прошел успешно, запускаем скрипты отдельно
            if ($process->successful()) {
                // Запускаем post-install скрипты
                $scriptsCommand = "{$this->phpPath} {$composerPath} run-script post-install-cmd --no-interaction";
                $scriptsProcess = Process::path($this->basePath)
                    ->timeout(300)
                    ->env([
                        'HOME' => $homeDir,
                        'COMPOSER_HOME' => $homeDir . '/.composer',
                    ])
                    ->run($scriptsCommand);

                // Игнорируем ошибки скриптов - они не критичны
                if (!$scriptsProcess->successful()) {
                    Log::warning('Composer post-install scripts failed', [
                        'error' => $scriptsProcess->errorOutput(),
                    ]);
                }
            }

            if ($process->successful()) {
                return [
                    'success' => true,
                    'status' => 'success',
                    'output' => $process->output(),
                ];
            }

            return [
                'success' => false,
                'status' => 'error',
                'error' => $process->errorOutput() ?: $process->output(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Получить путь к composer
     */
    protected function getComposerPath(): string
    {
        // 1. Проверить явно указанный путь в .env
        $composerPath = env('COMPOSER_PATH');
        if ($composerPath && file_exists($composerPath)) {
            return $composerPath;
        }

        // 2. Попробовать найти composer в стандартных местах
        $possiblePaths = [
            '/home/d/dsc23ytp/.local/bin/composer',
            '/usr/local/bin/composer',
            '/usr/bin/composer',
            'composer', // Последняя попытка - использовать из PATH
        ];

        foreach ($possiblePaths as $path) {
            if ($path === 'composer') {
                // Для 'composer' проверяем через which
                $whichProcess = Process::run('which composer');
                if ($whichProcess->successful() && trim($whichProcess->output())) {
                    return trim($whichProcess->output());
                }
            } else {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        // 3. Fallback на 'composer' (будет ошибка, если не найден)
        return 'composer';
    }

    /**
     * Выполнить миграции
     */
    protected function runMigrations(): array
    {
        try {
            $process = Process::path($this->basePath)
                ->run("{$this->phpPath} artisan migrate --force");

            if ($process->successful()) {
                // Парсим вывод для определения количества миграций
                $output = $process->output();
                preg_match_all('/Migrating:\s+(\d{4}_\d{2}_\d{2}_\d{6}_[\w_]+)/', $output, $matches);
                $migrationsRun = count($matches[0]);

                return [
                    'status' => 'success',
                    'migrations_run' => $migrationsRun,
                    'message' => $migrationsRun > 0
                        ? "Выполнено миграций: {$migrationsRun}"
                        : 'Новых миграций не обнаружено',
                    'output' => $output,
                ];
            }

            return [
                'status' => 'error',
                'error' => $process->errorOutput() ?: $process->output(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Очистить кеш package discovery
     * Важно: после удаления dev-пакетов нужно очистить кеш провайдеров
     */
    protected function clearPackageDiscoveryCache(): void
    {
        try {
            // Удаляем кеш package discovery
            $packagesCachePath = $this->basePath . '/bootstrap/cache/packages.php';
            if (file_exists($packagesCachePath)) {
                unlink($packagesCachePath);
                Log::info('Кеш package discovery удален');
            }

            // Удаляем кеш сервис-провайдеров (если существует)
            $servicesCachePath = $this->basePath . '/bootstrap/cache/services.php';
            if (file_exists($servicesCachePath)) {
                unlink($servicesCachePath);
                Log::info('Кеш сервис-провайдеров удален');
            }

            // Очищаем кеш конфигурации (может содержать ссылки на удаленные провайдеры)
            $process = Process::path($this->basePath)
                ->run("{$this->phpPath} artisan config:clear");

            if (!$process->successful()) {
                Log::warning('Не удалось очистить кеш конфигурации', [
                    'error' => $process->errorOutput(),
                ]);
            }

            // Переобнаруживаем пакеты (только установленные, без dev-зависимостей)
            $discoverProcess = Process::path($this->basePath)
                ->timeout(60)
                ->run("{$this->phpPath} artisan package:discover --ansi");

            if (!$discoverProcess->successful()) {
                Log::warning('Не удалось переобнаружить пакеты', [
                    'error' => $discoverProcess->errorOutput(),
                ]);
            } else {
                Log::info('Пакеты успешно переобнаружены');
            }
        } catch (\Exception $e) {
            Log::warning('Ошибка при очистке кеша package discovery', [
                'error' => $e->getMessage(),
            ]);
            // Не бросаем исключение, чтобы не прерывать деплой
        }
    }

    /**
     * Очистить временные файлы разработки
     * Удаляет файлы, которые не должны быть в продакшене
     */
    protected function cleanDevelopmentFiles(): void
    {
        try {
            $filesToRemove = [
                'public/hot',
                'public/hot/',
            ];

            foreach ($filesToRemove as $file) {
                $filePath = $this->basePath . '/' . trim($file, '/');

                // Удаляем через shell команды (наиболее надежно)
                $escapedPath = escapeshellarg($filePath);
                $publicPath = escapeshellarg($this->basePath . '/public');

                // Множественные способы удаления для максимальной надежности
                Process::path($this->basePath)
                    ->run("rm -f {$escapedPath} 2>/dev/null || true");

                Process::path($this->basePath)
                    ->run("rm -rf {$escapedPath} 2>/dev/null || true");

                Process::path($this->basePath)
                    ->run("find {$publicPath} -maxdepth 1 -name 'hot' -delete 2>/dev/null || true");

                // Через PHP функции (как дополнительная проверка)
                if (file_exists($filePath)) {
                    if (is_file($filePath)) {
                        @unlink($filePath);
                    } elseif (is_dir($filePath)) {
                        $this->deleteDirectory($filePath);
                    }
                    Log::info("Удален файл разработки: {$file}");
                }
            }

            // Финальная проверка через 2 секунды (на случай асинхронного создания)
            Process::path($this->basePath)
                ->timeout(5)
                ->run("sleep 2 && find " . escapeshellarg($this->basePath . '/public') . " -maxdepth 1 -name 'hot' -delete 2>/dev/null || true");

            // Дополнительно: запускаем Artisan команду для гарантированного удаления
            try {
                Process::path($this->basePath)
                    ->timeout(10)
                    ->run("{$this->phpPath} artisan clean:hot --force 2>/dev/null || true");
            } catch (\Exception $e) {
                // Игнорируем ошибки
            }

        } catch (\Exception $e) {
            Log::warning('Ошибка при очистке файлов разработки', [
                'error' => $e->getMessage(),
            ]);
            // Не бросаем исключение, чтобы не прерывать деплой
        }
    }

    /**
     * Рекурсивно удалить директорию
     */
    protected function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    /**
     * Очистить все кеши
     */
    protected function clearAllCaches(): array
    {
        $commands = [
            'config:clear',
            'cache:clear',
            'route:clear',
            'view:clear',
            'optimize:clear',
        ];

        $results = [];
        foreach ($commands as $command) {
            try {
                $process = Process::path($this->basePath)
                    ->run("{$this->phpPath} artisan {$command}");

                $results[$command] = $process->successful();
            } catch (\Exception $e) {
                $results[$command] = false;
                Log::warning("Ошибка очистки кеша: {$command}", ['error' => $e->getMessage()]);
            }
        }

        return [
            'success' => !in_array(false, $results, true),
            'details' => $results,
        ];
    }

    /**
     * Оптимизировать приложение
     */
    protected function optimizeApplication(): array
    {
        $commands = [
            'config:cache',
            'route:cache',
            'view:cache',
        ];

        $results = [];
        foreach ($commands as $command) {
            try {
                $process = Process::path($this->basePath)
                    ->run("{$this->phpPath} artisan {$command}");

                $results[$command] = $process->successful();
            } catch (\Exception $e) {
                $results[$command] = false;
                Log::warning("Ошибка оптимизации: {$command}", ['error' => $e->getMessage()]);
            }
        }

        return [
            'success' => !in_array(false, $results, true),
            'details' => $results,
        ];
    }

    /**
     * Получить текущий commit hash
     */
    protected function getCurrentCommitHash(): ?string
    {
        try {
            $process = Process::path($this->basePath)
                ->run('git rev-parse HEAD 2>&1');

            if ($process->successful()) {
                $hash = trim($process->output());
                if (!empty($hash) && strlen($hash) === 40) {
                    return $hash;
                }
            } else {
                Log::warning('Не удалось получить commit hash', [
                    'output' => $process->output(),
                    'error' => $process->errorOutput(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Ошибка при получении commit hash', [
                'error' => $e->getMessage(),
            ]);
        }
        return null;
    }
}

//exit()
