<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Http\Requests\Install\ProcessStep1Request;
use App\Http\Requests\Install\ProcessStep3Request;
use App\Http\Requests\Install\ProcessStep4Request;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InstallController extends Controller
{
    private const TOTAL_STEPS = 5;

    private const STEP_LABELS = [
        1 => 'Лицензия',
        2 => 'Требования',
        3 => 'База данных',
        4 => 'Администратор',
        5 => 'Готово',
    ];

    public function index()
    {
        return redirect()->route('install.step', 1);
    }

    public function show(int $step, Request $request)
    {
        if ($step > 1) {
            for ($i = 1; $i < $step; $i++) {
                if (!session("install.step{$i}_done")) {
                    return redirect()->route('install.step', 1);
                }
            }
        }

        $data = [
            'currentStep' => $step,
            'totalSteps'  => self::TOTAL_STEPS,
            'stepLabels'  => self::STEP_LABELS,
        ];

        if ($step === 2) {
            $data['checks'] = $this->getRequirementsChecks();
            $data['allPassed'] = collect($data['checks'])->where('optional', false)->every(fn ($c) => $c['pass']);
        }

        if ($step === 3) {
            $isHttps = $request->isSecure()
                || strtolower((string) $request->header('X-Forwarded-Proto')) === 'https'
                || strtolower((string) $request->header('X-Forwarded-SSL')) === 'on'
                || strtolower((string) $request->server('HTTPS')) === 'on';
            $data['detectedUrl'] = ($isHttps ? 'https' : 'http') . '://' . $request->getHost();
        }

        if ($step === 5) {
            if (!file_exists(storage_path('.installed'))) {
                file_put_contents(storage_path('.installed'), now()->toDateTimeString());
            }

            session()->forget(array_map(fn ($i) => "install.step{$i}_done", range(1, 4)));

            try {
                DB::table('sessions')->truncate();
            } catch (\Throwable) {
            }

            foreach (glob(storage_path('framework/sessions/*')) ?: [] as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }

            session()->invalidate();
            session()->regenerateToken();
        }

        return view("install.step{$step}", $data);
    }

    public function processStep1(ProcessStep1Request $request)
    {
        session(['install.step1_done' => true]);

        return redirect()->route('install.step', 2);
    }

    public function processStep2(Request $request)
    {
        if (!session('install.step1_done')) {
            return redirect()->route('install.step', 1);
        }

        $checks = $this->getRequirementsChecks();
        $allPassed = collect($checks)->where('optional', false)->every(fn ($c) => $c['pass']);

        if (!$allPassed) {
            return redirect()->route('install.step', 2)
                ->with('error', 'Не все требования выполнены. Устраните проблемы и попробуйте снова.');
        }

        session(['install.step2_done' => true]);

        return redirect()->route('install.step', 3);
    }

    public function processStep3(ProcessStep3Request $request)
    {
        if (!session('install.step2_done')) {
            return redirect()->route('install.step', 1);
        }

        $data = $request->validated();

        $host = $data['db_host'];
        $port = (int) $data['db_port'];
        $dbname = $data['db_name'];
        $user = $data['db_user'];
        $pass = $data['db_pass'] ?? '';
        $createDb = !empty($data['create_db']);
        $cleanDb = !empty($data['clean_db']);

        $dbExists = $this->dbExists($host, $port, $dbname, $user, $pass, $connectError);

        if ($connectError && $dbExists === null) {
            return back()->with('toast_error', $this->friendlyDbError($connectError))->withInput();
        }

        if (!$dbExists && !$createDb) {
            return back()
                ->with('toast_error', 'База данных не существует. Включите "Создать базу данных" или создайте её вручную.')
                ->withInput();
        }

        if (!$dbExists && $createDb) {
            $createError = $this->createDatabase($host, $port, $dbname, $user, $pass);

            if ($createError) {
                return back()->with('toast_error', 'Не удалось создать базу данных: ' . $this->friendlyDbError($createError))->withInput();
            }
        }

        if ($dbExists && !$cleanDb && $this->dbHasTables($host, $port, $dbname, $user, $pass)) {
            return back()
                ->with('toast_error', 'База данных уже существует и содержит таблицы. Отметьте "Очистить базу данных" для переустановки.')
                ->withInput();
        }

        if ($dbExists && $cleanDb) {
            $cleanError = $this->cleanDatabase($host, $port, $dbname, $user, $pass);

            if ($cleanError) {
                return back()->with('toast_error', 'Не удалось очистить базу данных: ' . $this->friendlyDbError($cleanError))->withInput();
            }
        }

        $this->updateEnv([
            'APP_URL'     => rtrim($data['app_url'], '/'),
            'DB_HOST'     => $host,
            'DB_PORT'     => $port,
            'DB_DATABASE' => $dbname,
            'DB_USERNAME' => $user,
            'DB_PASSWORD' => $pass,
        ]);

        session(['install.step3_done' => true]);

        return redirect()->route('install.step', 4);
    }

    public function processStep4(ProcessStep4Request $request)
    {
        if (!session('install.step3_done')) {
            return redirect()->route('install.step', 1);
        }

        $data = $request->validated();

        try {
            $exitCode = Artisan::call('migrate', ['--force' => true]);

            if ($exitCode !== 0) {
                throw new \RuntimeException(Artisan::output());
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['migration' => 'Ошибка миграций: ' . $e->getMessage()]);
        }

        try {
            $groupId = DB::table('user_groups')->insertGetId([
                'name'        => 'Администраторы',
                'description' => 'Полный доступ ко всем разделам панели управления',
                'is_default'  => false,
                'is_system'   => true,
                'permissions' => json_encode(['all']),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['migration' => 'Не удалось создать группу администраторов: ' . $e->getMessage()]);
        }

        try {
            DB::table('users')->insert([
                'name'       => $data['name'],
                'email'      => $data['email'],
                'password'   => Hash::make($data['password']),
                'group_id'   => $groupId,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['email' => 'Не удалось создать пользователя: ' . $e->getMessage()]);
        }

        session(['install.step4_done' => true]);

        return redirect()->route('install.step', 5);
    }

    public function finalize(Request $request)
    {
        if (!file_exists(storage_path('.installed'))) {
            file_put_contents(storage_path('.installed'), now()->toDateTimeString());
        }

        if (session('install.delete_install')) {
            $this->deleteInstallDirectory();
        }

        session()->forget(array_map(
            fn ($i) => "install.step{$i}_done",
            range(1, 4),
        ));
        session()->forget('install.delete_install');

        return view('install.step5', [
            'currentStep' => 5,
            'totalSteps'  => self::TOTAL_STEPS,
            'stepLabels'  => self::STEP_LABELS,
        ]);
    }

    private function getRequirementsChecks(): array
    {
        $checks = [
            ['name' => 'PHP версия ≥ 8.2', 'required' => '≥ 8.2', 'actual' => PHP_VERSION, 'pass' => version_compare(PHP_VERSION, '8.2.0', '>='), 'optional' => false],
            ['name' => 'ext-pdo', 'required' => 'Включено', 'actual' => $this->extStatus('pdo'), 'pass' => extension_loaded('pdo'), 'optional' => false],
            ['name' => 'ext-pdo_pgsql', 'required' => 'Включено', 'actual' => $this->extStatus('pdo_pgsql'), 'pass' => extension_loaded('pdo_pgsql'), 'optional' => false],
            ['name' => 'ext-mbstring', 'required' => 'Включено', 'actual' => $this->extStatus('mbstring'), 'pass' => extension_loaded('mbstring'), 'optional' => false],
            ['name' => 'ext-openssl', 'required' => 'Включено', 'actual' => $this->extStatus('openssl'), 'pass' => extension_loaded('openssl'), 'optional' => false],
            ['name' => 'ext-curl', 'required' => 'Включено', 'actual' => $this->extStatus('curl'), 'pass' => extension_loaded('curl'), 'optional' => false],
            ['name' => 'ext-fileinfo', 'required' => 'Включено', 'actual' => $this->extStatus('fileinfo'), 'pass' => extension_loaded('fileinfo'), 'optional' => false],
            ['name' => 'ext-tokenizer', 'required' => 'Включено', 'actual' => $this->extStatus('tokenizer'), 'pass' => extension_loaded('tokenizer'), 'optional' => false],
            ['name' => 'ext-zip', 'required' => 'Включено', 'actual' => $this->extStatus('zip'), 'pass' => extension_loaded('zip'), 'optional' => false],
            ['name' => 'ext-gd (изображения)', 'required' => 'Рекомендуется', 'actual' => $this->extStatus('gd'), 'pass' => extension_loaded('gd'), 'optional' => true],
            ['name' => 'Запись в storage/', 'required' => 'Разрешено', 'actual' => is_writable(storage_path()) ? 'Разрешено' : 'Запрещено', 'pass' => is_writable(storage_path()), 'optional' => false],
            ['name' => 'Запись в bootstrap/', 'required' => 'Разрешено', 'actual' => is_writable(base_path('bootstrap/cache')) ? 'Разрешено' : 'Запрещено', 'pass' => is_writable(base_path('bootstrap/cache')), 'optional' => false],
            ['name' => 'Запись в modules/', 'required' => 'Разрешено', 'actual' => is_writable(base_path('modules')) ? 'Разрешено' : 'Запрещено', 'pass' => is_writable(base_path('modules')), 'optional' => false],
            ['name' => 'pg_dump (резервные копии)', 'required' => 'Рекомендуется', 'actual' => $this->findPgDump() ?? 'Не найден', 'pass' => (bool) $this->findPgDump(), 'optional' => true],
        ];

        return $checks;
    }

    private function friendlyDbError(string $raw): string
    {
        if (str_contains($raw, 'password authentication failed')) {
            return 'Неверное имя пользователя или пароль.';
        }

        if (str_contains($raw, 'Connection refused') || str_contains($raw, 'could not connect')) {
            return 'Не удалось подключиться к серверу. Проверьте хост и порт.';
        }

        if (str_contains($raw, 'Name or service not known') || str_contains($raw, 'could not translate host name')) {
            return 'Сервер не найден. Проверьте правильность указанного хоста.';
        }

        if (str_contains($raw, 'timeout')) {
            return 'Превышено время ожидания подключения. Сервер недоступен.';
        }

        if (str_contains($raw, 'permission denied')) {
            return 'Нет прав доступа. Проверьте параметры пользователя.';
        }

        return 'Не удалось установить соединение с базой данных. Проверьте параметры подключения.';
    }

    private function findPgDump(): ?string
    {
        $paths = ['/usr/bin/pg_dump', '/usr/local/bin/pg_dump', '/usr/pgsql-15/bin/pg_dump', '/usr/pgsql-16/bin/pg_dump'];

        foreach ($paths as $p) {
            if (file_exists($p) && is_executable($p)) {
                return $p;
            }
        }

        $which = trim(shell_exec('which pg_dump 2>/dev/null') ?? '');

        return $which ?: null;
    }

    private function extStatus(string $ext): string
    {
        return extension_loaded($ext) ? 'Включено' : 'Отсутствует';
    }

    private function dbExists(string $host, int $port, string $dbname, string $user, string $pass, ?string &$error = null): ?bool
    {
        $error = null;

        try {
            new \PDO("pgsql:host={$host};port={$port};dbname={$dbname}", $user, $pass, [
                \PDO::ATTR_TIMEOUT => 5,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);

            return true;
        } catch (\PDOException $e) {
            $msg = $e->getMessage();

            if (str_contains($msg, 'does not exist') || str_contains($msg, 'database') && str_contains($msg, 'exist')) {
                return false;
            }

            $error = $msg;

            return null;
        }
    }

    private function dbHasTables(string $host, int $port, string $dbname, string $user, string $pass): bool
    {
        try {
            $pdo = new \PDO("pgsql:host={$host};port={$port};dbname={$dbname}", $user, $pass, [
                \PDO::ATTR_TIMEOUT => 5,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $count = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'")->fetchColumn();

            return (int) $count > 0;
        } catch (\PDOException) {
            return false;
        }
    }

    private function createDatabase(string $host, int $port, string $dbname, string $user, string $pass): ?string
    {
        try {
            $pdo = new \PDO("pgsql:host={$host};port={$port};dbname=postgres", $user, $pass, [
                \PDO::ATTR_TIMEOUT => 5,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->exec('CREATE DATABASE "' . str_replace('"', '""', $dbname) . '"');

            return null;
        } catch (\PDOException $e) {
            return $e->getMessage();
        }
    }

    private function cleanDatabase(string $host, int $port, string $dbname, string $user, string $pass): ?string
    {
        try {
            $pdo = new \PDO("pgsql:host={$host};port={$port};dbname={$dbname}", $user, $pass, [
                \PDO::ATTR_TIMEOUT => 5,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->exec('DROP SCHEMA public CASCADE; CREATE SCHEMA public;');

            return null;
        } catch (\PDOException $e) {
            return $e->getMessage();
        }
    }

    private function updateEnv(array $values): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $example = base_path('.env.example');
            file_put_contents($envPath, file_exists($example) ? file_get_contents($example) : '');
        }

        $envContent = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $str = str_replace(["\r\n", "\r", "\n"], '', (string) $value);
            $needsQuotes = $str === '' || preg_match('/[\s"\'#\\\\$]/', $str);
            $escapedValue = $needsQuotes
                ? '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $str) . '"'
                : $str;

            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$escapedValue}", $envContent);
            } else {
                $envContent .= "\n{$key}={$escapedValue}";
            }
        }

        file_put_contents($envPath, $envContent);
    }

    private function deleteInstallDirectory(): void
    {
        $installPath = public_path('install');

        if (is_dir($installPath)) {
            $this->rrmdir($installPath);
        }
    }

    private function rrmdir(string $dir): void
    {
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }

        rmdir($dir);
    }
}
