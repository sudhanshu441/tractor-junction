# Local setup

From a clean machine to the site running in your browser. Every command here has
been run start to finish; the output quoted is what you should actually see.

---

## 1. What you need first

| Software | Version | Check with |
|---|---|---|
| PHP | **8.3 or newer** | `php -v` |
| Composer | 2.x | `composer -V` |
| MySQL | 8.0+ (or MariaDB 10.6+) | `mysql --version` |
| Git | any | `git --version` |

**PHP extensions** — all normally bundled, but check:

```bash
php -m | grep -Ei "pdo_mysql|mbstring|openssl|tokenizer|xml|ctype|json|bcmath|fileinfo|gd|curl|zip"
```

`gd` matters: it resizes uploaded listing photos. Without it, uploads fail.

<details>
<summary><b>Installing the prerequisites</b></summary>

**Windows** — the simplest route is [Laragon](https://laragon.org/) (bundles PHP,
MySQL, Composer). Otherwise install PHP 8.3 from [windows.php.net](https://windows.php.net/download/),
then enable the extensions above in `php.ini` by removing the leading `;`.

**macOS**

```bash
brew install php@8.3 composer mysql
brew services start mysql
```

**Ubuntu / Debian**

```bash
sudo apt update
sudo apt install -y php8.3 php8.3-{cli,mysql,mbstring,xml,curl,gd,zip,bcmath} \
                    mysql-server composer git
sudo systemctl start mysql
```
</details>

---

## 2. Clone the repository

```bash
git clone https://github.com/sudhanshu441/tractor-junction.git
cd tractor-junction
git checkout claude/krishi-junction-setup-pmlgom
```

That branch holds all five phases. `main` does not.

---

## 3. Install the PHP packages

```bash
composer install
```

Takes a minute or two the first time.

---

## 4. Create the environment file

```bash
cp .env.example .env
php artisan key:generate
```

`key:generate` writes `APP_KEY`. **Do not skip it** — sessions and encrypted
cookies fail without it, usually with an unhelpful error.

---

## 5. Create the database

```bash
mysql -u root -p -e "CREATE DATABASE krishi_junction CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Then open `.env` and set your MySQL credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=krishi_junction
DB_USERNAME=root
DB_PASSWORD=your_mysql_password
```

<details>
<summary><b>No MySQL? Use SQLite instead (fine for a first look)</b></summary>

```bash
touch database/database.sqlite
```

Then in `.env`, set `DB_CONNECTION=sqlite` and delete the other `DB_*` lines.
Everything works; MySQL is what production uses.
</details>

---

## 6. Build the schema and load the demo data

```bash
php artisan migrate --seed
```

This creates 95 tables and loads roles, permissions, geography, the catalogue and
demo data. It takes about 15 seconds. You will see:

```
Super admin: admin@krishijunction.com / mobile 9000000001
Local password: KrishiAdmin@2026
Demo catalogue: 58 products with specs and prices
Demo marketplace: 18 used listings from 4 sellers
Demo dealers: 4 dealers · 3 verified
Content: 3 pages · 14 FAQs · 3 posts · 3 menus
```

If you ever want to start over: `php artisan migrate:fresh --seed`.
**That deletes everything.**

---

## 7. Link the uploads folder

```bash
php artisan storage:link
```

Without this, listing photos and uploaded images render as broken.

---

## 8. Warm the caches and start the server

```bash
php artisan kj:warm
php artisan serve
```

Open **http://localhost:8000**.

---

## Logging in

The site has **two ways in**, and this trips people up.

### Mobile + OTP — how real users sign in

At `/login`, enter a mobile number and press **Send code**. No SMS is sent
locally: `SMS_DRIVER=log` means the code is written to the log instead.

**Read the code from the log:**

```bash
# macOS / Linux
tail -f storage/logs/laravel.log | grep "OTP DEBUG"

# Windows PowerShell
Get-Content storage\logs\laravel.log -Wait | Select-String "OTP DEBUG"
```

You will see a line like:

```
[2026-09-08 03:52:23] local.INFO: [OTP DEBUG] 9812300001 => 820682
```

`820682` is the code. It is valid for 10 minutes, single-use, and 5 codes per
mobile per hour.

### Email + password — the staff shortcut

Go to **http://localhost:8000/login/password** — this is the quickest way into
the admin panel and it is not linked from the site.

---

## Accounts created by the seeder

**These passwords are in a public repository. Change them before the site is
reachable by anyone else.**

### Administrator

| | |
|---|---|
| URL | http://localhost:8000/login/password |
| Email | `admin@krishijunction.com` |
| Password | `KrishiAdmin@2026` |
| Mobile (for OTP login) | `9000000001` |
| Role | super-admin — every permission |

Lands on **http://localhost:8000/admin**.

> On a production install (`APP_ENV=production`) the seeder generates a random
> 16-character password instead and prints it once. It is never `KrishiAdmin@2026`.

### Demo dealers

All use password **`KrishiDemo@2026`**. Dealer panel: **http://localhost:8000/dealer**

| Dealership | Email | Status |
|---|---|---|
| Shri Balaji Tractors | `shri-balaji-tractors@example.com` | verified |
| Kisan Agro Motors | `kisan-agro-motors@example.com` | verified |
| Punjab Tractor House | `punjab-tractor-house@example.com` | verified |
| Godavari Farm Equipment | `godavari-farm-equipment@example.com` | **pending** — so the verification queue is not empty |

### Demo sellers (customers)

All use password **`KrishiDemo@2026`**. Customer panel: **http://localhost:8000/account**

| Name | Email | Mobile |
|---|---|---|
| Ramesh Kumar | `ramesh-kumar@example.com` | 9812300001 |
| Vijay Singh | `vijay-singh@example.com` | 9812300002 |
| Anil Kumar | `anil-kumar@example.com` | 9812300003 |
| Sunil Yadav | `sunil-yadav@example.com` | 9812300004 |

---

## Email

`MAIL_MAILER=log` by default — **nothing is actually sent**. Every email is
written to `storage/logs/laravel.log`, which is what you want locally.

To send real email, set this in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your_app_password      # a Gmail App Password, not your login
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@krishijunction.com"
MAIL_FROM_NAME="Krishi Junction"
```

Gmail needs an **App Password** (Google Account → Security → 2-Step Verification →
App passwords). Your normal password will be rejected.

For testing without a real inbox, [Mailtrap](https://mailtrap.io) gives you SMTP
credentials that catch everything.

---

## Other services, and what happens without them

Everything below works out of the box in a mode that needs no keys. You only
configure these when you want the real thing.

| Service | Default | What the default does |
|---|---|---|
| **SMS / OTP** | `SMS_DRIVER=log` | Writes the OTP to the log instead of texting it. Set `SMS_DRIVER=msg91` with `MSG91_AUTH_KEY` for real SMS. |
| **Payments** | `PAYMENT_DRIVER=log` | Plan upgrades and listing promotions complete end to end without taking money. Set `PAYMENT_DRIVER=razorpay` with `RAZORPAY_KEY_ID` / `RAZORPAY_KEY_SECRET` for real payments. |
| **Analytics** | not set | No GA tag and no consent banner load at all. Add a GA4 or GTM ID under Admin → Settings to enable both. |
| **Queue** | `database` | Runs inline. For background jobs: `php artisan queue:work` |
| **Scheduler** | not running | Listing expiry, SLA escalation, sitemaps and backups. Run `php artisan schedule:work` in a second terminal to see them fire. |

---

## What to look at first

| Page | URL |
|---|---|
| Home | http://localhost:8000 |
| Tractor catalogue with live filters | http://localhost:8000/tractors |
| Used marketplace | http://localhost:8000/used |
| Sell your tractor (6-step wizard) | http://localhost:8000/sell |
| Dealer directory | http://localhost:8000/dealers |
| EMI calculator | http://localhost:8000/loan/emi-calculator |
| Insurance | http://localhost:8000/tractor-insurance |
| News & guides | http://localhost:8000/news |
| **The whole site in Hindi** | http://localhost:8000/hi |
| Admin dashboard | http://localhost:8000/admin |
| Admin reports & charts | http://localhost:8000/admin/reports |
| Moderation queue | http://localhost:8000/admin/used-listings/moderation |
| API (no token needed) | http://localhost:8000/api/v1/products |

---

## If something goes wrong

| Symptom | Cause and fix |
|---|---|
| `No application encryption key has been specified` | `php artisan key:generate` |
| `SQLSTATE[HY000] [1049] Unknown database` | The database does not exist — step 5 |
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL is not running: `brew services start mysql` / `sudo systemctl start mysql` |
| Images are broken | `php artisan storage:link` |
| `Permission denied` writing to storage | `chmod -R 775 storage bootstrap/cache` |
| The OTP never arrives | It is in `storage/logs/laravel.log` — see **Logging in** |
| A page 404s that should exist | `php artisan route:clear && php artisan config:clear` |
| Changes to `.env` do nothing | `php artisan config:clear` — cached config is a snapshot |
| Everything is odd after pulling | `php artisan optimize:clear` clears every cache at once |

Run the test suite to confirm the install is sound (about 10 minutes):

```bash
php artisan test
```

226 tests should pass.

---

## Before anyone else can reach this

If you put this on a server, even briefly, work
[docs/15-LAUNCH-CHECKLIST.md](15-LAUNCH-CHECKLIST.md). The first three items are
the ones that matter most:

1. `APP_DEBUG=false` — with it on, every error page prints your environment
   variables, including database and API credentials.
2. **Change the admin password.** It is published in this repository.
3. Remove the demo seeders, or anyone can log in as a dealer.
