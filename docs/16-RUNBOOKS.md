# Runbooks

Operational procedures for Krishi Junction. Each one is written to be followed by
somebody who did not build the system, at 2am, without asking anyone.

---

## 1. Deploy

```bash
php artisan down --render="errors::503" --retry=60

git pull --ff-only
composer install --no-dev --optimize-autoloader
php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

php artisan kj:warm            # so the first visitor does not pay for a cold cache
php artisan up
```

**Never** run `php artisan config:cache` before `.env` is in place — the cached
config is a snapshot, and a missing key becomes a silent null rather than an error.

**After a deploy that changes URLs**, rebuild the sitemaps: `php artisan seo:sitemap`.

### Rolling back

```bash
git checkout <previous-tag>
composer install --no-dev --optimize-autoloader
php artisan migrate:rollback --step=1     # only if the deploy added migrations
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

A rollback across a destructive migration is a restore, not a rollback — go to §3.

---

## 2. Backups

A dump runs nightly at 02:00 via `kj:backup --verify`, keeping 14 days on the
local disk. `--verify` counts `CREATE TABLE` statements and fails the run if the
dump has fewer tables than the schema does, so a truncated dump is reported as a
failure rather than filed as a success.

```bash
php artisan kj:backup --verify         # run one by hand
ls -lh storage/app/backups/            # what is retained
```

**Off-site copy is not automatic.** Sync `storage/app/backups/` to object storage
(S3, Spaces, GCS) from cron or the host's own backup agent. A backup on the same
disk as the database survives a bad deploy but not a dead disk.

---

## 3. Restore rehearsal

Do this **once before launch and quarterly after**. A backup nobody has restored
is a rumour.

```bash
# 1. On a scratch database, never the live one.
mysql -e "CREATE DATABASE kj_restore_test"

# 2. Restore the most recent dump.
gzip -dc storage/app/backups/krishi-junction-<date>.sql.gz | mysql kj_restore_test

# 3. Check the shape, not just that the import exited 0.
mysql kj_restore_test -e "SELECT COUNT(*) FROM products;
                          SELECT COUNT(*) FROM used_listings;
                          SELECT COUNT(*) FROM leads;
                          SELECT MAX(created_at) FROM leads;"

# 4. Point a staging .env at kj_restore_test and load the home page,
#    a model page, and the admin dashboard.

# 5. Write the date and row counts in the operations log. Drop the database.
```

If step 3 shows a `leads.created_at` older than the dump's own timestamp, the
dump ran against a replica that was lagging. Investigate before trusting it.

---

## 4. Restore into production

Only after the team agrees the current data is lost or corrupt.

```bash
php artisan down
mysqldump ... > /tmp/pre-restore-safety.sql.gz     # even a corrupt database first
gzip -dc <chosen-dump>.sql.gz | mysql <production-db>
php artisan migrate --force                        # the dump may predate a migration
php artisan cache:clear && php artisan kj:warm
php artisan up
```

Then check, in this order: a public page renders; a dealer can log in; the lead
count matches roughly what the dashboard showed before the incident.

---

## 5. The site is slow

1. `php artisan queue:monitor` — a backed-up queue delays notifications, not pages.
2. Check the slow query log first; the query budget test (`PerformanceBudgetTest`)
   documents what each page should cost.
3. `php artisan kj:warm` — a cold cache after a deploy or a `cache:clear` makes
   the first request to every page do the work for everyone.
4. If a single page is slow, run it with `DB::listen` locally: an N+1 that grows
   with the catalogue is by far the most likely cause.

## 6. Leads have stopped arriving

1. Is the SMS gateway responding? `KJ_SMS_DRIVER=log` in production silently
   sends nothing — check first.
2. `SELECT COUNT(*) FROM leads WHERE created_at > NOW() - INTERVAL 1 HOUR;`
3. If leads exist but dealers say nothing arrived, check `lead_assignments` —
   an empty table means the routing rules matched nothing. `routing_rules` must
   have at least one catch-all row with `lead_type` NULL.
4. `php artisan leads:escalate` reports SLA breaches; run it by hand to see what
   it thinks is overdue.

## 7. A payment was taken but nothing was granted

Do **not** grant it by hand first — find out whether the gateway actually took it.

1. `SELECT * FROM payments WHERE reference_no = '<ref>';` — `status` tells you
   what we recorded.
2. `status = created` means checkout opened but no callback arrived. Check the
   gateway dashboard for that `gateway_order_id`.
3. If the gateway shows the payment captured, replay the callback: the settle
   path is idempotent, so re-posting the gateway's callback to the confirm URL
   grants it exactly once.
4. Only if the gateway cannot replay: set the payment to `paid` and activate the
   subscription in one transaction, and write the reference in the operations log.

## 8. Regenerating the self-hosted fonts

`public/assets/vendor/fonts/` holds Latin, Latin-Extended and Devanagari subsets
of Archivo, IBM Plex Sans, IBM Plex Mono and IBM Plex Sans Devanagari. To refresh
after a font update, fetch the CSS from Google Fonts with a modern browser
user-agent, download each `woff2` it references for those three subsets, rewrite
the URLs to local paths, and keep the header comment in `fonts.css`.

Do **not** replace this with a CDN link. A third-party font host costs a DNS
lookup, a TLS handshake and a round-trip before any text paints, which on a rural
3G connection is the slowest thing on the page.
