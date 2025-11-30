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

            // 4. Очистка кешей
            $cacheResult = $this->clearAllCaches();
            $result['data']['cache_cleared'] = $cacheResult['success'];

            // 5. Оптимизация
            $optimizeResult = $this->optimizeApplication();
            $result['data']['optimized'] = $optimizeResult['success'];

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

            // Выполняем git pull с дополнительной настройкой безопасной директории
            $safeDirectoryPath = escapeshellarg($this->basePath);
            $process = Process::path($this->basePath)
                ->env([
                    'GIT_CEILING_DIRECTORIES' => dirname($this->basePath),
                ])
                ->run("git -c safe.directory={$safeDirectoryPath} reset --hard origin/main");

            if (!$process->successful()) {
                // Если reset не удался, пробуем обычный pull
                $process = Process::path($this->basePath)
                    ->env([
                        'GIT_CEILING_DIRECTORIES' => dirname($this->basePath),
                    ])
                    ->run("git -c safe.directory={$safeDirectoryPath} pull origin main --no-rebase");
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
                ->run('git rev-parse HEAD');

            if ($process->successful()) {
                return trim($process->output());
            }
        } catch (\Exception $e) {
            // Ignore
        }
        return null;
    }
}

