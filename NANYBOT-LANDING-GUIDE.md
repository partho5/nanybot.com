# nanybot.com landing page: deployment guide

Written 1 October 2026 for the agent that deploys the AI Laptop Solver landing page on the production VPS.
Read all of it before you run anything.

**What this is.** A small static website: HTML, one compiled Tailwind CSS file, images and screenshots, and at most a
few lines of inline JavaScript (the mobile menu). No database, no PHP, no Node and no build step on the server: the
CSS is compiled on Partho's PC and only the finished files are uploaded (see `README.md`). It must be reachable at:

    https://nanybot.com/ai-laptop-solver/            landing page
    https://nanybot.com/ai-laptop-solver/privacy/    privacy policy (required for the Microsoft Store listing)
    https://nanybot.com/ai-laptop-solver/terms/      terms of use

**What this is not.** `nanybot.com` is a different project from the Laravel backend
(`system-care-api.nanybot.com`), even though both run on the same VPS. Do not use the backend's guide, `deploy.sh`,
php-fpm pool, nginx server block, certificate or `.env` for this job, and do not change any of them. The two
share the server and the parent domain name, and nothing else.

## 0. Rules

1. **Look before you change.** Back up any config file before editing it: `sudo cp -a FILE FILE.bak-$(date +%F-%H%M)`.
2. **Touch only this site.** This VPS hosts other apps (the API, `jovoc.com`, others). Never edit their files,
   php-fpm pools, nginx blocks or certificates.
3. **`sudo nginx -t` must pass before every `sudo systemctl reload nginx`.** If it fails, restore the `.bak` and stop.
4. **Publish only the contents of the `site/` folder.** `FEATURES.md`, `landing-page-features-and-narrative.md`,
   `SERVER-GUIDE.md`, this file and `README.md` are internal and must never be on the server. Neither may Tailwind's
   source files (`package.json`, `tailwind.config.*`, `node_modules`, the input CSS): do not install Node or run a
   build on the server.
5. **Ask Partho instead of guessing** for the support email address and for anything this guide does not cover. Never
   print secrets.

## 1. Read-only checks

```bash
dig +short nanybot.com A; dig +short nanybot.com AAAA
dig +short www.nanybot.com A; dig +short www.nanybot.com AAAA      # empty = no www record
curl -s -m 10 https://api.ipify.org; echo                          # this server's IPv4, to compare with the A record
sudo nginx -T 2>/dev/null | grep -nE "server_name .*nanybot\.com"  # blocks that already name nanybot.com
ls -la /var/www 2>/dev/null
```
- `nanybot.com` should resolve to this server. If it does not, stop and tell Partho (DNS is his to change).
- A secure connection to `nanybot.com` currently fails with "unrecognized name", so no server block answers for it yet.
  If you find a block that already serves `nanybot.com`, do **not** create a second one: add only a
  `location /ai-laptop-solver/` that serves the folder from step 3, test, reload, and tell Partho what the existing
  site is.
- If `www.nanybot.com` has no DNS record, do every step below for `nanybot.com` only, and tell Partho that `www`
  needs an A/AAAA record before it can be added to the certificate.

## 2. Get the files onto the server

Partho copies the `site/` folder (not the folder above it). From his Windows PC, for example:
```powershell
scp -r E:\Development\ai-laptop-solver-site\site\* <user>@<server>:/tmp/ai-laptop-solver-site/
```
Then on the server, with the support email address Partho gave you:
```bash
cd /tmp/ai-laptop-solver-site
grep -rl "SUPPORT_EMAIL" .              # index.html, privacy/index.html, terms/index.html
sed -i 's/SUPPORT_EMAIL/<address Partho gave you>/g' index.html privacy/index.html terms/index.html
grep -r "SUPPORT_EMAIL" . && echo "PLACEHOLDER STILL THERE, STOP" || echo "placeholder gone"
ls -R . | head -40
```
Expected: `index.html`, `privacy/index.html`, `terms/index.html`, and an `assets/` folder holding the compiled CSS
file, the icon, the screenshots (`assets/screens/`) and any self-hosted font. Delete anything else
(`*.md`, `package.json`, `package-lock.json`, `tailwind.config.*`, `node_modules`, `.git`, source `.css` that is
not the compiled one). If a page loads anything from another host (`grep -rEn "https?://" . | grep -v nanybot.com`
shows `src=`, `href="http` for fonts, scripts or CDNs), tell Partho: the site must make no third-party requests.

## 3. Install the files

The page lives under `/ai-laptop-solver/`, so the bare domain stays free for the main nanybot.com site later:
```bash
sudo mkdir -p /var/www/nanybot.com/ai-laptop-solver
sudo rsync -a --delete /tmp/ai-laptop-solver-site/ /var/www/nanybot.com/ai-laptop-solver/
sudo chown -R root:root /var/www/nanybot.com
sudo find /var/www/nanybot.com -type d -exec chmod 755 {} \; ; sudo find /var/www/nanybot.com -type f -exec chmod 644 {} \;
rm -rf /tmp/ai-laptop-solver-site
```
Files are root-owned and world-readable on purpose: nginx only reads them and nothing can write there.

## 4. nginx server block

Create `/etc/nginx/sites-available/nanybot.com` with port 80 only. Certbot adds HTTPS and the redirect in step 5.
Use `server_name nanybot.com;` alone if `www` has no DNS record.
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name nanybot.com www.nanybot.com;

    root /var/www/nanybot.com;
    index index.html;
    server_tokens off;
    autoindex off;

    gzip on;
    gzip_types text/css application/javascript image/svg+xml text/plain;

    add_header X-Content-Type-Options nosniff always;
    add_header X-Frame-Options SAMEORIGIN always;
    add_header Referrer-Policy strict-origin-when-cross-origin always;

    # The bare domain (nanybot.com/) is Partho's to decide. Until he does, it answers 404.
    location = / { return 404; }

    location /ai-laptop-solver/ {
        try_files $uri $uri/ =404;
    }

    # Page images and CSS change rarely
    location ~* ^/ai-laptop-solver/assets/.*\.(png|css|jpg|jpeg|webp|svg|ico|woff2|js)$ {
        expires 7d;
        add_header Cache-Control "public";
        add_header X-Content-Type-Options nosniff always;
    }

    # Never serve dotfiles or source notes
    location ~ /\. { deny all; }
    location ~* \.(md|bak|sh|env)$ { deny all; }
}
```
```bash
sudo ln -s /etc/nginx/sites-available/nanybot.com /etc/nginx/sites-enabled/nanybot.com
sudo nginx -t && sudo systemctl reload nginx
curl -s -o /dev/null -w "%{http_code}\n" -H "Host: nanybot.com" http://127.0.0.1/ai-laptop-solver/     # 200
```

## 5. HTTPS (Let's Encrypt)

Same method as the other sites here; auto-renewal is already covered by the system `certbot.timer`.
```bash
sudo certbot --nginx -d nanybot.com -d www.nanybot.com      # drop "-d www.nanybot.com" if www has no DNS record
sudo nginx -t && sudo systemctl reload nginx
sudo certbot certificates | grep -A3 nanybot.com
```
Choose "redirect HTTP to HTTPS" when asked. Do not request a wildcard certificate and do not touch the
`system-care-api.nanybot.com` certificate.

## 6. Verify

Run from the server, and ask Partho to open the three URLs in a browser:
```bash
for p in /ai-laptop-solver/ /ai-laptop-solver/privacy/ /ai-laptop-solver/terms/; do
  printf "%s -> " $p; curl -s -o /dev/null -w "%{http_code}\n" https://nanybot.com$p; done
# every asset the landing page references must load (CSS, icon, screenshots, fonts)
curl -s https://nanybot.com/ai-laptop-solver/ | grep -oE '(src|href)="[^"#]*assets/[^"]+"' | sed -E 's/^(src|href)="//; s/"$//' | sort -u | while read a; do
  printf "%s -> " "$a"; curl -s -o /dev/null -w "%{http_code}\n" "https://nanybot.com/ai-laptop-solver/$a"; done
curl -sI https://nanybot.com/ai-laptop-solver/privacy | head -3                       # 301 to .../privacy/
curl -sI http://nanybot.com/ai-laptop-solver/ | head -3                               # 301 to https
curl -s https://nanybot.com/ai-laptop-solver/privacy/ | grep -c "Last updated 29 September 2026"    # 1
curl -s https://nanybot.com/ai-laptop-solver/ | grep -c "SUPPORT_EMAIL"               # 0
for p in /FEATURES.md /ai-laptop-solver/FEATURES.md /ai-laptop-solver/../etc/passwd / ; do
  printf "%s -> " $p; curl -s --path-as-is -o /dev/null -w "%{http_code}\n" https://nanybot.com$p; done    # 404 or 403
echo | openssl s_client -connect nanybot.com:443 -servername nanybot.com 2>/dev/null | openssl x509 -noout -subject -dates
```
Pass = 200 for the three pages and for every asset the landing page references, the two redirects as noted, 0 placeholders, every "must not exist" path is
404 or 403, and a valid certificate for `nanybot.com`. Also confirm the API still answers:
`curl -s https://system-care-api.nanybot.com/api/health`.

## 7. Updating the page later

Repeat steps 2 and 3 only (copy the new files, replace the placeholder, rsync). No nginx change is needed.

## 8. Rollback

`sudo rm /etc/nginx/sites-enabled/nanybot.com && sudo nginx -t && sudo systemctl reload nginx` removes this site and
touches nothing else. Delete `/var/www/nanybot.com/ai-laptop-solver` only if Partho asks.

## 9. Report back

```
DNS: apex ok yes/no, www exists yes/no
Existing site on nanybot.com before: none / <what>
Certificate expiry: <date>
URLs: landing <code>, privacy <code>, terms <code>, assets loaded <n of n>, placeholder gone yes/no, third-party requests found yes/no
"Must not exist" paths (FEATURES.md, ../etc/passwd, /): <codes>
API health after the change: <ok/not>
Problems found that I did not fix: <list>
```
