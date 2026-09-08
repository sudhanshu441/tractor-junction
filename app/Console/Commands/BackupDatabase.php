<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Nightly database dump.
 *
 * A backup nobody has restored is a rumour, so `--verify` counts the tables in
 * the dump and refuses to report success on a file that is obviously short.
 * The full restore rehearsal is in docs/16-RUNBOOKS.md and is a human task.
 */
class BackupDatabase extends Command
{
    protected $signature = 'kj:backup {--keep=14 : How many daily dumps to retain} {--verify}';

    protected $description = 'Dump the database to the backup disk and prune old dumps';

    public function handle(): int
    {
        $connection = config('database.default');

        if ($connection !== 'mysql') {
            $this->warn("Backups are configured for MySQL; this connection is [{$connection}].");

            return self::FAILURE;
        }

        $config = config("database.connections.{$connection}");
        $file = 'backups/krishi-junction-'.now()->format('Y-m-d-His').'.sql.gz';
        $path = Storage::disk('local')->path($file);

        Storage::disk('local')->makeDirectory('backups');

        // The password goes in through the environment, never on the command
        // line, where it would be visible in the process list to every user.
        $process = Process::fromShellCommandline(
            'mysqldump --host="${:DB_HOST}" --port="${:DB_PORT}" --user="${:DB_USER}" '
            .'--single-transaction --quick --routines --no-tablespaces "${:DB_NAME}" | gzip > "${:DUMP_PATH}"',
        );

        $process->setTimeout(1800);
        $process->run(null, [
            'DB_HOST' => $config['host'],
            'DB_PORT' => (string) $config['port'],
            'DB_USER' => $config['username'],
            'DB_NAME' => $config['database'],
            'DUMP_PATH' => $path,
            'MYSQL_PWD' => $config['password'],
        ]);

        if (! $process->isSuccessful()) {
            $this->error('Backup failed: '.trim($process->getErrorOutput()));

            return self::FAILURE;
        }

        $bytes = Storage::disk('local')->size($file);

        if ($bytes < 1024) {
            $this->error("The dump is only {$bytes} bytes. Treating that as a failure.");

            return self::FAILURE;
        }

        if ($this->option('verify')) {
            $tables = (int) trim(shell_exec('gzip -dc '.escapeshellarg($path).' | grep -c "^CREATE TABLE" || true'));

            $this->line("  tables in dump: {$tables}");

            if ($tables < 50) {
                $this->error('The dump has fewer tables than this schema has. Not treating it as a good backup.');

                return self::FAILURE;
            }
        }

        $this->info('Wrote '.$file.' ('.round($bytes / 1048576, 1).' MB)');

        $this->prune((int) $this->option('keep'));

        return self::SUCCESS;
    }

    private function prune(int $keep): void
    {
        $files = collect(Storage::disk('local')->files('backups'))
            ->filter(fn ($file) => str_ends_with($file, '.sql.gz'))
            ->sortDesc()
            ->values();

        $stale = $files->slice($keep);

        foreach ($stale as $file) {
            Storage::disk('local')->delete($file);
        }

        if ($stale->isNotEmpty()) {
            $this->line('  pruned '.$stale->count().' old dump(s)');
        }
    }
}
