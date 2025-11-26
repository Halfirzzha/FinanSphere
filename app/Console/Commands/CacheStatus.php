<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display cache and Redis status information';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('╔══════════════════════════════════════════════════╗');
        $this->info('║       FinanSphere Cache Status                  ║');
        $this->info('╚══════════════════════════════════════════════════╝');
        $this->newLine();

        // Cache Driver Info
        $this->line('📦 <fg=cyan>Cache Configuration</>');
        $this->line('   Driver: <fg=yellow>' . config('cache.default') . '</>');
        $this->line('   Session Driver: <fg=yellow>' . config('session.driver') . '</>');
        $this->line('   Queue Driver: <fg=yellow>' . config('queue.default') . '</>');
        $this->newLine();

        // Test Redis Connection
        $this->line('🔍 <fg=cyan>Redis Connection Test</>');

        try {
            $redis = Redis::connection();
            $redis->ping();
            $this->line('   Status: <fg=green>✓ Connected</>');

            // Get Redis info
            $info = $redis->info();
            $this->line('   Redis Version: <fg=yellow>' . ($info['redis_version'] ?? 'Unknown') . '</>');
            $this->line('   Memory Used: <fg=yellow>' . ($info['used_memory_human'] ?? 'Unknown') . '</>');
            $this->line('   Connected Clients: <fg=yellow>' . ($info['connected_clients'] ?? 'Unknown') . '</>');

        } catch (\Exception $e) {
            $this->line('   Status: <fg=red>✗ Failed</>');
            $this->line('   Error: <fg=red>' . $e->getMessage() . '</>');
        }

        $this->newLine();

        // Cache Keys Count
        $this->line('📊 <fg=cyan>Cache Statistics</>');

        try {
            $redis = Redis::connection();
            $prefix = config('database.redis.options.prefix');

            // Count keys by prefix
            $allKeys = $redis->keys($prefix . '*');
            $this->line('   Total Keys: <fg=yellow>' . count($allKeys) . '</>');

            // Count by tags
            $transactionKeys = $redis->keys($prefix . 'transactions:*');
            $categoryKeys = $redis->keys($prefix . 'categories:*');

            $this->line('   Transaction Cache: <fg=yellow>' . count($transactionKeys) . ' keys</>');
            $this->line('   Category Cache: <fg=yellow>' . count($categoryKeys) . ' keys</>');

        } catch (\Exception $e) {
            $this->line('   <fg=red>Unable to retrieve statistics</>');
        }

        $this->newLine();

        // Test Cache Operations
        $this->line('🧪 <fg=cyan>Cache Operations Test</>');

        try {
            $testKey = 'test_' . time();
            $testValue = 'test_value';

            // Test write
            Cache::put($testKey, $testValue, 60);
            $this->line('   Write: <fg=green>✓ Success</>');

            // Test read
            $retrieved = Cache::get($testKey);
            if ($retrieved === $testValue) {
                $this->line('   Read: <fg=green>✓ Success</>');
            } else {
                $this->line('   Read: <fg=red>✗ Failed</>');
            }

            // Test delete
            Cache::forget($testKey);
            $this->line('   Delete: <fg=green>✓ Success</>');

        } catch (\Exception $e) {
            $this->line('   <fg=red>✗ Test Failed: ' . $e->getMessage() . '</>');
        }

        $this->newLine();

        $this->info('╔══════════════════════════════════════════════════╗');
        $this->info('║  Run "php artisan cache:clear" to clear cache  ║');
        $this->info('╚══════════════════════════════════════════════════╝');

        return Command::SUCCESS;
    }
}
