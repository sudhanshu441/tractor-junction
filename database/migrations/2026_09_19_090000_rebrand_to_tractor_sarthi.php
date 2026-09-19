<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Carries the rename into installs that already have data.
 *
 * Most of the brand is code, but the parts a visitor reads most — the site
 * name, the CMS pages, the FAQs, the SMS and email templates — were written
 * into the database by the seeders. Without this, an existing install shows
 * the new logo above the old name and nothing in the repository explains why.
 *
 * Deliberately NOT rewritten:
 *  - `notification_logs`: a record of messages that were actually sent. The old
 *    name is what went out; editing it would falsify the audit trail.
 *  - `notifications`: the same reasoning for items already delivered to a user.
 *  - Reference numbers already issued. `kj.brand.reference_prefix` applies to
 *    new rows; TS-L-000457 following KJ-L-000456 is honest, and renumbering
 *    would break every reference a customer has been quoted.
 */
return new class extends Migration
{
    private const OLD_NAME = 'Krishi Junction';

    private const NEW_NAME = 'Tractor Sarthi';

    private const OLD_DOMAIN = '@krishijunction.com';

    private const NEW_DOMAIN = '@tractorsarthi.com';

    /** Seeded demo logins, and the password each is documented with. */
    private const DEMO_PASSWORDS = [
        'admin' => 'SarthiAdmin@2026',
        'staff' => 'SarthiStaff@2026',
        'demo' => 'SarthiDemo@2026',
    ];

    /** table => columns carrying prose the seeders wrote. */
    private const TEXT_COLUMNS = [
        'settings' => ['value'],
        'notification_templates' => ['sms_body', 'email_subject', 'email_body'],
        'pages' => ['title', 'content', 'meta_title', 'meta_description'],
        'faqs' => ['question', 'answer'],
        'seo_meta' => ['meta_title', 'meta_description', 'og_title', 'og_description'],
    ];

    public function up(): void
    {
        $this->renameInText(self::OLD_NAME, self::NEW_NAME);
        $this->moveSeededLogins(self::OLD_DOMAIN, self::NEW_DOMAIN);

        $this->flushCache();
    }

    public function down(): void
    {
        $this->renameInText(self::NEW_NAME, self::OLD_NAME);
        $this->moveSeededLogins(self::NEW_DOMAIN, self::OLD_DOMAIN);

        $this->flushCache();
    }

    /** Settings and rendered fragments are cached with the old name in them. */
    private function flushCache(): void
    {
        if (Schema::hasTable('cache')) {
            DB::table('cache')->delete();
        }
    }

    private function renameInText(string $from, string $to): void
    {
        foreach (self::TEXT_COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                // REPLACE() is the same call in MySQL and SQLite, and it keeps
                // the work in the database rather than reading every row.
                DB::table($table)
                    ->where($column, 'like', '%'.$from.'%')
                    ->update([$column => DB::raw(
                        sprintf('REPLACE(%s, %s, %s)', $column, $this->quote($from), $this->quote($to))
                    )]);
            }
        }
    }

    /**
     * Seeded demo accounts are identified by their email domain, never by id —
     * a real person who signed up is not on that domain and is never touched.
     *
     * Their passwords are documented in docs/18-LOCAL-SETUP.md, so they are
     * reset to the documented values: a credential nobody can look up is worse
     * than one printed in a public repository, which these already are.
     */
    private function moveSeededLogins(string $fromDomain, string $toDomain): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $seeded = DB::table('users')
            ->where('email', 'like', '%'.$fromDomain)
            ->get(['id', 'email']);

        foreach ($seeded as $user) {
            $email = str_replace($fromDomain, $toDomain, $user->email);

            // Never clobber an account that already holds the target address.
            $taken = DB::table('users')->where('email', $email)->where('id', '!=', $user->id)->exists();

            if ($taken) {
                continue;
            }

            DB::table('users')->where('id', $user->id)->update([
                'email' => $email,
                'password' => Hash::make($this->passwordFor($user->email)),
            ]);
        }
    }

    private function passwordFor(string $email): string
    {
        if (str_starts_with($email, 'admin@')) {
            return self::DEMO_PASSWORDS['admin'];
        }

        if (str_starts_with($email, 'staff.')) {
            return self::DEMO_PASSWORDS['staff'];
        }

        return self::DEMO_PASSWORDS['demo'];
    }

    private function quote(string $value): string
    {
        return DB::getPdo()->quote($value);
    }
};
