# Server guide: the Laravel API backend (system-care-api.nanybot.com)

Written 1 October 2026 for the agent that works on the production VPS. Read all of it before you run
anything. It covers **only the Laravel API project**. Tasks, in this order: **A** deploy the latest backend,
**B** check old money rows, **C** fix the real-time (Reverb) proxy, **E** check mail and the rest of
production. At the end, send the report in section 9.

**The `nanybot.com` landing page is a different project.** It shares this VPS and the parent domain, nothing
else. It has its own guide, `NANYBOT-LANDING-GUIDE.md`. Do not touch `nanybot.com` or `www.nanybot.com` while
doing the tasks below.

## 0. Rules (these override anything below if they conflict)

1. **Look before you change.** Run the read-only checks first and write down what you found.
2. **Back up before you edit** any config file: `sudo cp -a FILE FILE.bak-$(date +%F-%H%M)`.
3. **Never print or paste secrets** (`.env` values such as `DB_PASSWORD`, `REVERB_APP_SECRET`,
   `PAYPAL_CLIENT_SECRET`, `MAIL_PASSWORD`, API keys). To check that a value is set, test it
   without showing it, for example `grep -q '^SUPPORT_EMAIL=.\+' .env && echo set`.
4. **Touch only what is named here.** This VPS hosts other apps (for example `jovoc.com`, with its own
   php-fpm pool and nginx block). Never edit their files, pools or certificates. Never edit a certbot-managed
   server block except for the single `include` line in Task C.
5. **`sudo nginx -t` must pass before every `reload`.** If it fails, restore the `.bak` file and stop.
6. **Only the API.** This guide never creates, edits or removes anything for `nanybot.com` or `www.nanybot.com`.
7. **Ask Partho instead of guessing** for: the support email address, new USD values for old money
   rows, and anything not covered here.
8. **Nothing tracked by git is ever changed on the server.** Do not edit, patch, stash, checkout, reset, revert or
   commit any file in the project's git working tree (`app/`, `routes/`, `config/`, `resources/`, `database/`,
   `tests/`, `public/`, `deploy.sh`, `deploy-guide.md`, `composer.*`, `package*.json`, and so on). The working tree
   must stay clean. Every code change is made in Partho's local repo, committed, pushed, and then reaches the
   server through the `git pull` inside `sudo ./deploy.sh`. If you think code or a tracked file needs a change,
   stop, describe it in your report, and let Partho do it locally.
9. **Config changes are limited to what this guide names:** the `.env` keys listed in Tasks A and C (`.env` is not
   tracked by git), the single nginx `include` line in Task C, and the `.bak` copies. Anything else you think
   should change is reported, not changed.

## 1. Context

| Thing | Value |
|---|---|
| API host | `system-care-api.nanybot.com` (Laravel, php-fpm pool `system_care`, PostgreSQL db `system_care_backend`) |
| App dir | the directory that holds `deploy.sh` (owned by user `system_care`) |
| Deploy command | `sudo ./deploy.sh` from the app dir (pulls `main`, composer, npm build, `migrate --force`, caches, restarts worker, Reverb and scheduler units) |
| Existing guide | `deploy-guide.md` in the app dir (Reverb steps in detail) |

What changed in the backend since the last deploy (all committed to `main` by Partho before you start; if
`git log` does not show them, stop and tell Partho):
- USD only: migration `2026_10_01_120000_rename_bdt_columns_to_usd` renames `features.price_bdt`, and
  `quotes|invoices|payments.amount_bdt` to `*_usd`. API fields are now `amount_usd` and `price_usd`.
- `/privacy` shows `config('app.support_email')` (env `SUPPORT_EMAIL`) instead of the mail from-address.
- Every outgoing mail gets a Reply-To header from `MAIL_REPLY_TO` (falls back to `SUPPORT_EMAIL`), because the
  from-address is `no-reply@mail.jovoc.com`.

## 2. Task A: deploy the latest backend

**A1. Read-only checks** (from the app dir):
```bash
git status --short            # must be clean, deploy.sh refuses a dirty tree
git log --oneline -5
php artisan about --only=environment | head -20   # env should be production, debug OFF
systemctl is-active laravel-queue-worker@1 laravel-reverb laravel-scheduler 2>/dev/null
```
(Unit names may differ; `deploy.sh` prints them in step 7 to 9.)

**A2. Back up the database before the migration** (the migration only renames columns, but take the backup anyway):
```bash
sudo -u postgres pg_dump system_care_backend | gzip > /root/system_care_backend-$(date +%F-%H%M).sql.gz
ls -la /root/system_care_backend-*.sql.gz | tail -1
```

**A3. Add the two `.env` values** (ask Partho for the address; never invent one). Edit as the app user:
```bash
sudo -u system_care nano .env
```
```dotenv
SUPPORT_EMAIL=<address Partho gives you>
MAIL_REPLY_TO=<same address, or leave empty to use SUPPORT_EMAIL>
MAIL_FROM_ADDRESS=no-reply@mail.jovoc.com   # keep as is
```
Any inbox works for Reply-To, including a Gmail address. The sign-in codes and reports still come from the
no-reply address, and a customer's Reply goes to the Reply-To address.

**A4. Deploy:**
```bash
sudo ./deploy.sh
```
Read every warning it prints at the end. Do not ignore `APP_DEBUG`, `APP_URL`, `QUEUE_CONNECTION`,
or Reverb warnings: they feed Task C and E.

**A5. Verify:**
```bash
curl -s https://system-care-api.nanybot.com/api/health                       # {"status":"ok",...}
curl -s https://system-care-api.nanybot.com/privacy | grep -o 'mailto:[^"]*' | sort -u   # shows the SUPPORT_EMAIL, not no-reply@...
sudo -u system_care php artisan migrate:status | tail -5                     # rename migration "Ran"
sudo -u postgres psql system_care_backend -c '\d quotes' | grep -i amount    # amount_usd
```
If `/privacy` still shows `no-reply@...`, the config cache is stale: run `sudo -u system_care php artisan optimize`
and check the `SUPPORT_EMAIL` line again.

## 3. Task B: old money rows (they were typed in BDT)

Quotes, invoices, payments and paid-feature prices written before the migration are now read as US dollars
(for example a 350 that meant 350 BDT would show as $350). The real value is about **$9**. Do not convert by a
formula. List them and let Partho set the right USD numbers:
```bash
sudo -u postgres psql system_care_backend -c "select id, amount_usd, status, created_at from quotes order by id;"
sudo -u postgres psql system_care_backend -c "select id, invoice_number, amount_usd, status from invoices order by id;"
sudo -u postgres psql system_care_backend -c "select id, gateway, amount_usd from payments order by id;"
sudo -u postgres psql system_care_backend -c "select key, type, price_usd from features where price_usd is not null;"
```
- If all of them are test data, ask Partho whether to delete them or set them to the new USD value (for
  example `update quotes set amount_usd = 9 where amount_usd = 350;`). Run an `update` only after Partho
  confirms the exact statement. Wrap it in `begin; ... commit;` and show the row count first.
- Paid invoices and real payments are records of money that was taken. Do not change those amounts without
  Partho saying so in writing. Report them instead.

## 4. Task C: fix the real-time connection (Reverb behind nginx)

**Symptom.** `GET /app/<anything>` with websocket headers on `https://system-care-api.nanybot.com` returns a
plain Laravel HTML 404. That means nginx is sending `/app/` to PHP, not to Reverb. Desktop agents then fall
back to hourly polling, so notifications still arrive but not in real time. Probable cause: the one-time
`include` line from `deploy-guide.md` step 1 was never added.

**C1. Diagnose (read-only):**
```bash
sudo nginx -T 2>/dev/null | grep -n "system-care-reverb"            # no match = include missing
ls -la /etc/nginx/snippets/system-care-reverb.conf                  # written by deploy.sh
systemctl is-active laravel-reverb                                  # active?
sudo ss -ltnp | grep -E ':8081\b'                                   # Reverb listening on 127.0.0.1:8081
for k in BROADCAST_CONNECTION REVERB_APP_ID REVERB_APP_KEY REVERB_APP_SECRET REVERB_HOST REVERB_PORT REVERB_SCHEME REVERB_SERVER_HOST REVERB_SERVER_PORT REVERB_CLIENT_HOST REVERB_CLIENT_PORT REVERB_CLIENT_SCHEME; do
  v=$(sudo -u system_care grep -m1 "^$k=" .env | cut -d= -f2-); [ -n "$v" ] && echo "$k set" || echo "$k MISSING"; done
sudo -u system_care grep -E '^(BROADCAST_CONNECTION|REVERB_HOST|REVERB_PORT|REVERB_SCHEME|REVERB_CLIENT_[A-Z]+)=' .env   # no secrets in this list
sudo journalctl -u laravel-reverb -n 30 --no-pager
```
Expected values: `BROADCAST_CONNECTION=reverb`, `REVERB_HOST=127.0.0.1`, `REVERB_PORT=8081`,
`REVERB_SCHEME=http`, `REVERB_CLIENT_HOST=system-care-api.nanybot.com`, `REVERB_CLIENT_PORT=443`,
`REVERB_CLIENT_SCHEME=https`. Port 8081 only: 8080 belongs to the remote-support relay.
If one of **these named keys** is wrong or missing, set it to the expected value in `.env` (back it up first). For
`REVERB_APP_ID`, `REVERB_APP_KEY` and `REVERB_APP_SECRET`, ask Partho: he generates the key and secret once with
`openssl rand -hex 16` and `openssl rand -hex 32`, never reusing local ones. Then run `sudo ./deploy.sh` again.
Any other `.env` key, or a problem that is not a wrong or missing value, is reported, not changed.

**C2. Add the include** (the only edit allowed in this certbot-managed block):
```bash
sudo cp -a /etc/nginx/sites-available/system-care-api.nanybot.com{,.bak-$(date +%F-%H%M)}
sudo nano /etc/nginx/sites-available/system-care-api.nanybot.com
```
Inside the **HTTPS** `server { listen 443 ssl ... }` block, at server level next to the other `location` blocks
(not in the port-80 redirect block), add exactly:
```nginx
include /etc/nginx/snippets/system-care-reverb.conf;
```
```bash
sudo nginx -t && sudo systemctl reload nginx
```
If `nginx -t` fails because the snippet file is missing, run `sudo ./deploy.sh` once (it writes it), then retry.

**C3. Verify** (the key is read on the server and never printed):
```bash
KEY=$(sudo -u system_care grep -m1 '^REVERB_APP_KEY=' .env | cut -d= -f2-)
curl -s -i -m 10 -H "Connection: Upgrade" -H "Upgrade: websocket" -H "Sec-WebSocket-Version: 13" \
  -H "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==" \
  "https://system-care-api.nanybot.com/app/$KEY?protocol=7" | head -5
unset KEY
```
Pass = `HTTP/1.1 101 Switching Protocols`. Still a Laravel 404 = the include is in the wrong block or nginx was not
reloaded. A 502 = Reverb is not running on 8081 (`journalctl -u laravel-reverb`). Also run the same probe with
a wrong key: Reverb must answer 101 and then close with an error, not a 404 page.

## 6. Task E: mail and the rest of production

Read-only unless a step says otherwise. Report anything that does not match, do not change it unprompted.
```bash
sudo -u system_care grep -E '^(APP_ENV|APP_DEBUG|APP_URL|QUEUE_CONNECTION|MAIL_MAILER|CHECKOUT_GATEWAY|PAYPAL_MODE|ACCOUNT_MAX_DEVICES)=' .env
for k in MAIL_HOST MAIL_USERNAME MAIL_PASSWORD PAYPAL_CLIENT_ID PAYPAL_CLIENT_SECRET PAYPAL_WEBHOOK_ID SUPPORT_EMAIL MAIL_REPLY_TO; do
  sudo -u system_care grep -q "^$k=.\+" .env && echo "$k set" || echo "$k MISSING"; done
```
Expected: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://system-care-api.nanybot.com`,
`QUEUE_CONNECTION=database`, `MAIL_MAILER` not `log`, `CHECKOUT_GATEWAY=paypal`, `PAYPAL_MODE=live`
(sandbox means no real payments), `ACCOUNT_MAX_DEVICES=3`, and every key above "set".
- **Reply-To check:** ask Partho for an address he can read, then send one test sign-in-style mail to it from
  `php artisan tinker` (`Mail::raw('test', fn($m) => $m->to('<his address>')->subject('Reply-To test'));`).
  He should see From `no-reply@mail.jovoc.com` and Reply-To the support address. Do not send mail to any
  other address.
- **Feature tiers:** open `/admin/features` (Partho logs in) or run
  `sudo -u postgres psql system_care_backend -c "select key, tier, uses_ai, charges_credits, free_uses, is_active from features order by sort_order;"`
  and compare with the defaults below. Report differences. Do not change them.

| Free | Pro | AI (Pro + credits unless noted) |
|---|---|---|
| disk-cleanup, recycle-bin, registry-tweaks, startup-review, security-scan | plan-one-click, weekly-maintenance, background-processes, power-plan-high, drive-optimize, data-backup, data-restore, software-restore | ai-startup-classification (1 free use per PC), ai-process-classification, ai-backup-importance; **ai-report is Free and charges no credits**; ai-troubleshoot is inactive |

- **Services:** the queue worker, Reverb and scheduler units are `active`; `sudo journalctl -p err -n 30 --no-pager`
  for the last errors; disk space `df -h /` has more than 20% free; `certbot certificates` shows no certificate
  expiring in under 30 days.
- **Backups:** confirm a database backup exists and is recent (cron or provider snapshot). If there is none, say so.

## 7. Rollback

- **nginx:** restore the `.bak` file, then `sudo nginx -t && sudo systemctl reload nginx`.
- **Backend:** the migration is reversible (`php artisan migrate:rollback --step=1` renames the columns back).
  Only do it if Partho asks. A **code** rollback is never done on the server: Partho reverts the commit in his
  local repo and pushes it, and the server gets it through `sudo ./deploy.sh`.
- **Database:** restore from the dump taken in A2, only if Partho asks.

## 8. Do not

- Do not store secrets in this guide, a commit, or a report.
- Do not modify any file tracked by git on the server (rule 8). `git status` must be clean before and after.
- Do not run `php artisan migrate:fresh`, `db:wipe`, or anything that drops tables.
- Do not change `APP_KEY`.
- Do not edit `/etc/nginx/nginx.conf`, other sites' blocks, or php-fpm pools.
- Do not create, edit or remove anything for `nanybot.com` or `www.nanybot.com` (see `NANYBOT-LANDING-GUIDE.md`).

## 9. Report back (copy this, fill it in)

```
A. Backend deploy: commit <hash>, migrate OK/FAILED, /privacy shows <the support address, not no-reply>
B. Money rows: quotes <n>, invoices <n> (paid <n>), payments <n>, paid features <n>; actions taken: <none/what>
C. Reverb: include was missing/present; probe result <101 / 404 / 502>; reverb unit active yes/no
E. Env: APP_DEBUG <>, MAIL_MAILER <>, PAYPAL_MODE <>, missing keys <>, Reply-To test <received/not>, tier differences <>
git status after everything: clean yes/no (if no, list the files, do not clean them up)
Problems found that I did not fix: <list>
```
