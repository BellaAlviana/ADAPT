<?php

putenv('APP_ENV=testing');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
putenv('SESSION_DRIVER=array');
putenv('CACHE_STORE=array');

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PHPUnit\TextUI\CliArguments\Builder as CliConfigurationBuilder;
use PHPUnit\TextUI\XmlConfiguration\DefaultConfiguration;
use PHPUnit\TextUI\Configuration\Registry;

$cliConfiguration = (new CliConfigurationBuilder)->fromParameters([]);
$xmlConfiguration = DefaultConfiguration::create();
Registry::init($cliConfiguration, $xmlConfiguration);

class FeatureTestExecutor extends Tests\Feature\AdaptTest
{
    public function runAllTests()
    {
        $ref = new ReflectionClass(Tests\Feature\AdaptTest::class);
        $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
        
        $passed = 0;
        $failed = 0;

        foreach ($methods as $m) {
            $name = $m->getName();
            if (str_starts_with($name, 'test_')) {
                echo "Running {$name}... ";
                try {
                    $this->setUpTheTestEnvironment();
                    Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                    $this->$name();
                    $this->tearDownTheTestEnvironment();
                    echo "[PASSED]\n";
                    $passed++;
                } catch (\Throwable $e) {
                    echo "[FAILED]: " . $e->getMessage() . "\n";
                    echo "  at " . $e->getFile() . ":" . $e->getLine() . "\n";
                    $failed++;
                }
            }
        }

        echo "\n============================================\n";
        echo "Summary: $passed passed, $failed failed.\n";
        echo "============================================\n";
        return $failed === 0;
    }
}

$executor = new FeatureTestExecutor('test');
$success = $executor->runAllTests();
exit($success ? 0 : 1);
