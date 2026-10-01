# AI Laptop Solver: complete feature inventory

Written 1 October 2026 from the code (Windows app, background agent, popup, Laravel backend) and the
repo's own plan files. Nothing here comes from memory or from the older marketing text. Where the
code and a user-facing sentence disagree, the disagreement is listed in section 17.

**How to read an entry.** Each feature has a benefit line first, then *(Technical aspect: ...)* for
how it really works. Tags:

- **[Free]** / **[Pro]**: the default tier seeded in the backend feature registry. The admin can move any
  feature between Free and Pro in the admin panel without a release, so the live tier is whatever
  `/admin/features` says.
- **[AI]**: calls an AI model (OpenAI) and is metered.
- **[Admin]**: only the owner sees it (admin panel).
- **[Built, not exposed]**: the code exists, but no user can reach it today.
- **[Gap]**: what the UI says and what the code does are not the same. See section 17.

---

## 0. The product in one paragraph

A Windows PC maintenance app for people who only know "my laptop is slow". It measures the PC for
real, shows what is wrong in plain words, fixes the safe things when asked, undoes most of them
on request, and proves the result with a before-and-after. It also backs up your files and
reinstalls your programs after a Windows reset, works in English and Bangla, and tells you about
new things through small notifications. The owner runs a Laravel backend with an admin panel for
users, payments, notifications, crashes and quotes.

**Free vs Pro at a glance (defaults).**

| Free | Pro |
|---|---|
| Scan, Speed Score, all dashboard cards, Security check and quick scan, Clear junk files, Empty Recycle Bin, responsiveness tweaks, startup apps (manual on/off), emailed AI report, before/after | One-click Speed up / Run now, weekly automatic cleanup, close background programs, High Performance power plan, drive optimization, backup, restore, reinstall software |
| | AI features: AI startup review (1 free use per PC, then Pro and credits), AI background-program review, AI file-importance check (Pro and credits) |

---

## 1. See what is wrong (scan and dashboard)

On launch the app measures the PC straight away and fills the dashboard as each reading arrives. Slow
readings (folders, security) load beside the fast ones, so nothing waits for them.

### 1.1 Speed Score **[Free]**
One honest number out of 100 that moves only when something real changes, with a small line chart of
how it changed over time and a one-line verdict ("Needs improvement"). After a fix it shows "+N after
using this software".
*(Technical aspect: the average of five readings, each scored 0 to 100: boot time, startup item count,
memory pressure, free system-drive space, and background process count. If a reading cannot be taken
it is left out, and the card says "average of 4 of 5 measurements" rather than guessing. The first
scan shows "a trend line appears from the next check".)*

### 1.2 Main Problems Detected **[Free]**
A plain list of what is actually wrong, worst first, written as sentences, not codes. If nothing is
wrong it says "No problems found. This PC is in good shape." It can raise any of these:
- Defender is holding a threat; no antivirus registered; virus protection switched off; virus
  definitions out of date; no full check recently; firewall off on every network.
- Junk files wasting space; Recycle Bin holding deleted files; drive nearly full; many programs
  slowing startup; many background programs; high RAM load; slow last boot; CPU always busy.
- Downloads or Documents folder crowded or large; Pictures, Music or Videos taking a lot of space;
  many installed programs using a lot of space; loose clutter at the top of a drive; Desktop
  crowded; programs you have not used for a while (names and how long).
- A closing line "and a lot more improvements needed" and a button to email the full report (see 8.1).
*(Technical aspect: `problem_*` phrases in `phrases.js`, built from the same snapshot as the cards.
Folder messages say "items" because the count mixes files and folders. When a folder is crowded but
small, the wording gives the count and the real reason instead of implying size is the problem.)*

### 1.3 "What was measured": nine cards **[Free]**
Each card shows one real reading, a status (Good / Needs improvement / Needs attention), the figure,
a plain "Technical reason" paragraph, and where it makes sense, a button.
1. **Junk files**: how much safe-to-delete clutter exists (button: Clear junk files).
2. **Recycle Bin**: how much deleted-file space is held (button: Empty Recycle Bin; asks first).
3. **Drive space**: free space on the system drive.
4. **Security check**: Protected / Worth a look / Threat found / Not protected (buttons: Run a quick
   scan, Open Windows Security).
5. **Startup impact**: how many programs launch with Windows (button: Review startup softwares).
6. **Background processes**: how many programs run right now, shown as a bar chart.
7. **Memory in use**: RAM used, with a gauge.
8. **Boot time**: how long the last startup took.
9. **CPU headroom**: how much processor time was free.
*(Technical aspect: boot time is read from Windows' own Diagnostics-Performance log, not a stopwatch.
The memory text explains that past roughly 88% Windows swaps to disk, which is what feels like
stalling. CPU is one one-second sample, labelled as a snapshot, not an average. Drive health (SMART)
is deliberately not read, and the card says so rather than claiming a health figure. Cards that
could not be read say "No reading" and a reason, never a made-up number.)*

### 1.4 System specs card **[Free]**
Who this PC is: Windows edition (and whether it is activated), RAM size with type and speed (for
example DDR4 3200), storage size and type (NVMe SSD, SSD, HDD), processor threads and GHz, graphics
card (shared or dedicated with memory), the PC's manufacture date, and the last Windows install date
with its age ("3 years 3 months old").
*(Technical aspect: collected by `SystemSpecsCollector`, reusing the storage inventory instead of a
second WMI query. The "manufacturing date" is the BIOS release date, the only date Windows really
has, and the row is hidden when unknown. Anything unreadable shows "Not available".)*

### 1.5 Test disk speed **[Free]**
A one-minute test that tells you how fast the drive really reads and writes, in MB/s, with "Test
again".
*(Technical aspect: `DiskIoBenchmark`, uncached (no buffering, write-through) so the OS cache cannot
flatter the result. It only runs on this button (and when you request a report), never in the
background.)*

### 1.6 Folder and drive clutter checks **[Free]**
Spots the mess a person feels when opening a folder: Downloads and Documents (how many items and how
much space), Pictures, Music, Videos (space), the number of installed programs and the space they
use, loose items at the top of each drive, and Desktop items.
*(Technical aspect: `FolderBloatScanner`, read-only. Drive and Desktop checks count top-level items
only, never a recursive walk, with a cap of 500 per drive; Desktop ignores shortcuts. Thresholds
are three-tier and use top-level counts rather than recursive ones. Nothing here deletes a file.)*

### 1.7 Programs you have not used **[Free]**
Lists up to eight installed programs that have not been launched for 30 days or more, with how long
("1 year 2 months").
*(Technical aspect: `StaleSoftwareScanner`. Uses Windows' own execution history (BAM, and UserAssist
for Start-menu launches) matched to the program's install location. A program with no evidence of
when it last ran is skipped, never labelled "unused". It is report-only: see 17.)*

### 1.8 Honest by design **[Free]**
- Every figure is a reading from this PC. The page says "Nothing here is estimated."
- If one section fails to load, only that section says "Couldn't load this" with a **Retry** button;
  the rest of the dashboard keeps working.
- A re-measure button (top bar) re-reads everything.
*(Technical aspect: each section is pushed independently from `WebBridge.Sections.cs`; a failed
section sends `section_error` and the page offers `retry_section`.)*

---

## 2. Fix it

All fixes need administrator rights, which Windows asks for once at launch. They are shown first,
and the reversible ones keep a record so they can be undone later, even after the app was closed.

### 2.1 Speed up my computer / "Run the recommended fixes" **[Pro]**
One tap runs the whole recommended routine, with "Step 3 of 7" progress, a Stop button, and a result tile
per step. It tells you what it skipped and why, and never runs while another run is active. When it
ends: "All done", or "Finished, but a few steps need a look".
*(Technical aspect: `OptimizationPlan`. Order: responsiveness tweaks, High Performance power plan,
close background programs, startup review (including the AI-picked auto turn-off, 2.4), drive
optimization, junk cleanup, Recycle Bin. If any spinning disk has under 15% free, cleanup moves
ahead of drive optimization. The order and which steps run are decided locally, so the button works
with the backend down. Without admin rights the run says it cannot start. Each step is estimated
first, and an unavailable step is skipped with a reason code. Re-measures at the end and files the
"after" reading.)*

### 2.2 Responsiveness tweaks **[Free]**
Three small settings that make menus appear faster and reduce needless disk writes. Shown as "3/3
applied", with your original values saved so they can be restored.
*(Technical aspect: `RegistryTweaksModule`: HKCU `MenuShowDelay` to 100; HKLM `NtfsDisableLastAccessUpdate`
and `NtfsDisable8dot3NameCreation` to 1. The menu change is instant, the NTFS ones apply after a
restart. Originals are written to disk before any change; if that save fails, nothing is changed. A
value that did not exist is deleted on undo, not overwritten with a default. Tweaks that skip the
"apps are preventing shutdown" prompt were removed on purpose because they risk unsaved data.)*

### 2.3 High Performance power plan **[Pro]**
Switches to the faster power plan; on a laptop it only does so when plugged in. Undo puts back
whatever plan you were using (Balanced, Power Saver, a maker's plan, a custom one).
*(Technical aspect: `PowerPlanModule` / `PowerPlanAction` with `powercfg`. The previous plan's GUID is
saved to disk. The active plan is found by the GUID's shape, not by English text, so it works on
German or French Windows. Undo has no battery guard because returning to your own plan is always
safe.)*

### 2.4 Startup programs **[Free for manual control, AI review: see 5]**
Shows every program that starts with Windows, with what it is, who made it, and why it may or may not be
needed. For each one you pick **Keep**, **Start later** (where Windows can delay it) or **Turn off**, then
press "Apply changes (N)". Anything turned off can be turned back on from the same list ("Turn back on").
Items turned off by Speed up are labelled "Turned off by Speed up".
- **Finds more than Task Manager:** registry Run entries, both Startup folders, scheduled tasks that
  start at logon, and auto-start services.
- **Protected items are never shown as switches**, so you cannot break Windows by accident.
- **After Speed up**, programs the AI judged "not needed at startup" are turned off for you, with
  one-click undo. Only simple per-user launchers (Run entries and Startup-folder shortcuts) are ever
  switched off unattended. Services and scheduled tasks are left to you.
*(Technical aspect: `StartupScanner`, `StartupItemAction`, `StartupReviewModule`, `ProtectedAllowlist`.
Every change is confirmed by reading the real state back. A write's exit code is not trusted. "Start
later" exists only for services and scheduled tasks, because only they have a native delayed start;
this app installs no helper to fake one. Turn-off never deletes the entry. RunOnce entries are
never listed. The protection is layered: a name list, Windows' own registry metadata (kernel and boot
drivers, critical error control), services that protected services depend on, and structural
signals such as the Windows directory. Publisher alone never decides, because Microsoft and NVIDIA
publish both essential and optional items. Access-denied is detected by exit code 5, so it works
in any language.)*

### 2.5 Close background programs **[Pro] [AI]**
Closes leftover background programs that do not need to run, without touching anything you have open.
*(Technical aspect: `BackgroundProcessesModule`. Candidates are chosen locally: only the signed-in
user's own session, never anything with a window (even minimised), never this app and its helpers, never
a hard-coded shell and system list, never a process whose file could not be identified. The AI only
narrows that list and can never add to it. If the AI call fails or times out (180 s budget), nothing is
closed and the step still reports success. Not reversible: the program is gone and you relaunch it
yourself. Without Pro or credits it says "This step checks background apps with AI, which is part of
Pro" and closes nothing.)*

### 2.6 Drive optimization **[Pro]**
Keeps drives healthy: a full tidy-up pass on hard disks, a re-trim on solid-state drives, and nothing
on drives whose type is unknown.
*(Technical aspect: `DriveOptimizeModule`. Skips a hard disk with under 15% free. Stops on battery and
waits for AC power (up to 30 min). Runs in a gentle mode and switches to full speed when the PC has been
idle 10 minutes (or if you choose "full speed"). One disk at a time. Reports per drive: trimmed,
defragmented, skipped (low space or unknown type), paused or stopped on battery, not allowed,
cancelled, with minutes taken.)*

### 2.7 Clear junk files **[Free]**
Frees space with files Windows makes again on demand, so nothing you own is lost, and reports how much came back.
- **Cleared without asking:** your temp folder, Windows temp, the graphics shader caches (D3D, NVIDIA),
  Delivery Optimization files, Explorer thumbnails, and (on spinning disks only) Prefetch.
- **Only with your consent** (they cost something you could notice, such as a slower next start or
  a re-download): crash dumps and minidumps, Chrome, Edge and Firefox cache, and the Windows Update
  download cache. The consent text says they are re-downloaded when needed.
*(Technical aspect: `DiskCleanupAction` has one target table shared by estimate and clean, so the
quoted number and the work done cannot disagree. A path allowlist (temp, system root, local app
data, ProgramData) refuses anything else, and roaming profile data is excluded. Locked or in-use
files are skipped and counted. The Windows Update cache step stops `wuauserv`, then restarts it, and
if it cannot confirm the restart it says "Restart your PC to fix this". Prefetch is skipped on SSD
because clearing it only costs pre-load hints. Crash dumps are consent-only because they are the first
thing a technician asks for. Not reversible: deleted, not moved. Can be scheduled.)*

### 2.8 Empty Recycle Bin **[Free]**
Shows what the bin holds and always asks "Delete permanently?" before emptying. Says "already empty" if so.
*(Technical aspect: `RecycleBinModule`. If Windows will not report the contents, it refuses to empty
blind. Success is confirmed by reading the bin back. Not reversible, and the UI says so.)*

### 2.9 Security check and quick scan **[Free]**
Answers "do I have a virus?" with Windows' own answer: the card gives one word and the reason, and a
quick virus check can be run from the card (with a Stop button) when Microsoft Defender is the active
antivirus. If another antivirus protects the PC, it names it and sends you to that program.
- The reasons it can report, worst first: Defender is holding named threats; no antivirus listed; real-time
  protection is off; definitions are out of date; no scan on record; last scan was long ago; firewall
  off on all networks; Windows gave no status (normal on Server).
- Quick scan: warns that it can take 10 to 15 minutes and is not stuck, can be stopped, ends with
  "found nothing" / "Defender is holding N item: names" / "no readable result".
*(Technical aspect: `SecurityStatusCollector` reads Windows Security Center and Defender through WMI,
with a timeout per read. `SecurityScanModule` runs `MpCmdRun.exe` with a 30-minute ceiling. This app
runs no scanner of its own and says so on the card. A third-party antivirus's history is not read and the
card says nothing is claimed about it. Items Defender already removed are not counted. A scan changes
nothing, so it has nothing to undo.)*

### 2.10 Advanced panel ("Every fix") **[Free to open; each fix keeps its own tier]**
An **Advanced** toggle lists every individual fix with **Check** (estimate), **Run**, and **Undo**, and
labels the ones that "Cannot be undone". For people who want to do one thing at a time.
*(Technical aspect: `estimate_module`, `execute_module`, `revert_module` bridge actions over the
module registry. Each module declares its own reversibility.)*

---

## 3. Prove it worked

### 3.1 Before and after **[Free]**
A table comparing how the PC looked the first time it was measured with how it stands after the last
cleanup: speed score, free space, junk, Recycle Bin (shown as "Emptied"), programs starting with
Windows, background programs, memory in use, startup time. It also states what was left alone: "24
startup softwares are still allowed to start, nothing is turned off unless it is safe to."
*(Technical aspect: `MeasurementStore`. The baseline file is written once and never overwritten,
by design; there is no method that can. "After" is replaced after each optimization run. A capped
history file keeps the trend locally. A locked or full disk means the table has less to show, never
an error.)*

### 3.2 "What this software has done for your PC" **[Free]**
Result tiles and lifetime totals in plain benefit sentences: "GB space recovered", "% memory freed",
"N background programs closed", "N programs no longer start with Windows", "N seconds faster startup",
"% more free CPU", "Speed score up N points", "Total N GB of junk cleared", "Total N MB emptied from the
Recycle Bin".
*(Technical aspect: measured gains compare the first scan with now; junk and bin are sums of every
cleanup from an append-only local ledger (`LifetimeSavingsStore`). Cleanup is counted in one wrapper
(`SavingsTrackingModule`) so Speed up, the card buttons, the Advanced panel and the weekly clean all
count. Unsent entries are synced to the server later and repeats are ignored.)*

---

## 4. Protect your files (backup, restore, reinstall)

Opened from **Tools**. The pitch in the app: installing Windows wipes the drive, so back up first, in one click.

### 4.1 Back up your files **[Pro]**
Copies Documents, Downloads, Desktop, Pictures, Music and Videos to a drive you choose. Before it
starts it shows which folders will be copied, how many GB, which drives are big enough, and the free
space on each. Originals are never changed or deleted.
- **Drive choice:** best candidates first, "Removable drive" badge, "Not enough space" badge with how
  much to free, and a warning when the drive is the same physical disk as Windows ("if that disk fails,
  this backup could be lost too").
- **Pause and resume:** a Pause button; "Paused. N files saved safely so far", then continue later.
- **Honest skip report:** files that could not be copied are listed with the reason, never dropped silently.
- **Optional extras:** save program installers so you can reinstall without internet (with a real size
  preview, "+N GB for installers", and a note when some sizes are unknown); save Wi-Fi networks and
  passwords so they reconnect after restore; and an AI check that points out files that look important.
*(Technical aspect: `DataBackupModule`, `VerifiedCopyEngine`. Each file is written to a partial file,
re-read, and its SHA-256 compared against a fresh hash of the source before it is renamed into place;
timestamps are kept. A journal records each step so a paused or interrupted run resumes; if a journal
line cannot be saved, the run stops rather than continuing unrecorded. Batches close at 10 files and
1 GB. Skip reasons: shortcut, link/junction, cloud-only OneDrive placeholder, system file, path too
long, locked, unreadable. Redirected known folders are handled. The destination ranking never assumes
the Windows drive is C:. The drive is never a network or not-ready drive. The backup folder is
`Important_Backup_AILaptopSolver`. Installers come from winget (exact package IDs), recorded in a
manifest, with sizes found by HEAD requests and no downloads, and a stop when the disk fills. Wi-Fi
export uses `netsh wlan export`, written only to your own drive and never uploaded, and a PC with
no Wi-Fi simply skips it. The importance check sends top-level file and folder names only (no paths,
no contents) and only if you tick the box.)*

### 4.2 Restore from backup **[Pro]**
On the new or reset Windows, "Have a backup from before? Bring your files and softwares back in one
click." It finds the backup on any connected drive, lets you pick if there is more than one ("N files,
N GB"), shows a summary, asks what to do with every file that already exists (**keep the one here**,
**replace with the backup**, or **skip**), then restores. Unreadable entries are skipped and reported.
*(Technical aspect: `DataRestoreModule`. Destinations are recomputed for the new profile's known
folders, never copied from the old paths. Wi-Fi networks are re-added before software is reinstalled,
and never block the file restore. A backup made before Wi-Fi support has no Wi-Fi folder and that is
not an error.)*

### 4.3 Reinstall your software **[Pro]**
After a reset, shows "Software you can get back": each program with a badge ("Official installer ready",
"From the official source", "No trusted installer") and an "Install selected (N)" button. Programs with
no safe source say so and are left for you to install.
*(Technical aspect: `SoftwareRestoreModule` and `InstallerSourceResolver`, fail-closed, four tiers in
order: installer saved in your backup, curated essential-software list from the server, winget by
exact package ID, nothing. A tier whose checksum is attempted and fails is a hard failure for that
program, never a fall-through. It never installs from a typed-in URL. Installs one at a time and honours Stop
between programs. With no internet it installs what it can and reports "N of M installed" and where
the saved installers are. The list comes from the program inventory uploaded at backup time and
matched to this PC by device ID, which is a hash of the hardware, so it works after a Windows reinstall
on the same PC.)*

---

## 5. AI features (what really exists)

Four server features call an AI model. All run on the server, so the app holds no AI key.

| Feature | What it does for the user | Default tier |
|---|---|---|
| AI startup review | Looks at each startup program and says which are safe to turn off, with a reason in English and Bangla; pre-selects them | Pro; **1 free use per PC**, then Pro and credits |
| AI background-program review | Narrows which leftover background programs are safe to close (2.5) | Pro and credits |
| AI file-importance check | Flags files in a backup that look important (4.1) | Pro and credits |
| Emailed AI report | Writes the diagnostic report you can email yourself (8.1) | **Free**, costs no credits (cost is logged) |
| AI troubleshooting chat | Reserved for a free-form chat | **Switched off in the registry**, not built (17) |

**The "Ask the AI" chat box.** A floating button opens a chat with three one-tap questions ("Why is my
PC slow?", "Do I have a virus?", "What starts with Windows?"). Typing or tapping lights up the dashboard
cards the words relate to and shows an AI reply with a pointer chip that scrolls to the relevant card.
**It is not a free-form AI answering anything you type.** Today a message goes to the startup-review AI (if
there are new startup items) or gets the canned reply "Got it, I'm working on that now." See 17.
*(Technical aspect: `POST /api/chat`; empty message = the passive launch overview, no AI; non-empty with
startup items = one batched AI classification, metered as `ai-startup-classification`.
`Dashboard.metricsForText` does the keyword highlighting locally.)*

**AI credits.** AI features that spend credits show "Out of AI credits, add credits to use it", a
balance, and credit packs.
*(Technical aspect: a credit ledger (no stored balance). Credits are charged from real token use times a
markup (default 3x, 1000 credits per USD), with a minimum balance to start. Only token counts, model, cost
and credits are kept, not the text. The model price table lives in admin settings. The server answers
402 before spending anything.)*

---

## 6. Account, plans and payments

### 6.1 Sign in with an email code **[Free]**
No password. Type your email, get a 6-digit code valid for 10 minutes, and this PC stays signed in. It
shows the signed-in email, a list of your computers ("2 of 3") with "Active today / yesterday / N days
ago", and lets you sign out any other computer or this one. If you are at the limit, it asks you to sign
one out and then signs this one in at once.
*(Technical aspect: `AccountService`. Code length 6, expires in 600 s, 5 attempts, resend cooldown with
a countdown, an hourly cap per address, and a limit per account (default 3 devices, adjustable
per device in admin). Errors are specific: wrong code with tries left, expired, cooldown, too many,
email unavailable, offline. The email goes by `LoginCodeMail`.)*

### 6.2 Pro trial **[Free]**
"Claim Pro free for 30 days", includes 200 AI credits, no card needed. A badge in the top bar counts down
("Pro trial, 28 days left", "last day"). It can be used once per account or computer.
*(Technical aspect: defaults `trial_days` 30 and `trial_starter_credits` 200, both admin settings.)*

### 6.3 Pro plans and credit packs
- **Pro Annual**: one year; **Pro Lifetime**: one payment, yours for good. Defaults $19 and $79,
  one account on up to 3 computers. Prices are admin settings and are shown in the app before you pay.
- **AI credit packs**: small 500 credits for $5, medium 1,200 for $10, large 3,000 for $20; Pro members
  get 20% off.
- **Coupon codes** add AI credits ("Added N AI credits"), with clear errors: not valid, expired, already used,
  fully used.
*(Technical aspect: `Pricing` and `config/pricing.php` defaults; the admin overrides them in /admin/settings
without a release.)*

### 6.4 Paying and staying licensed
"Buy" opens secure PayPal checkout in your normal browser (never an elevated one). The app waits and updates
itself when the payment goes through ("Finish paying in your browser", "Open the payment page again"),
then says "You're Pro". Payment cancelled says nothing was charged. A "Your plan" panel shows the plan,
expiry date and upgrade, extend and buy-credits buttons. Locked buttons open a friendly sheet saying
what Pro adds, instead of an error. "No serial keys. Your license is managed automatically."
*(Technical aspect: PayPal gateway by default (`CHECKOUT_GATEWAY`); webhook verified server-side.
Code also exists for SSLCommerz and a manual test gateway. The app evaluates entitlement against the clock
as well as the server, so a trial ends on time offline, and annual Pro has 7 grace days. The same
registry decides in the app and on the server, so a button is locked exactly when the server would
refuse. A PC that has never reached the server cannot get Pro by staying offline, because the fallback
list locks Pro features.)*

---

## 7. Notifications and the background helper

### 7.1 Notification bell **[Free]**
A bell in the top bar with an unread count, a list (Today / Yesterday / N days ago), "Mark all read",
and notifications that open a web page (showing the site name first, "Opens example.com"). It keeps
showing the last list when offline. A switch turns the corner popups on or off.
*(Technical aspect: `/api/notifications`, read-all, per-notification events.)*

### 7.2 Corner popups and the background helper **[Free]**
A tiny helper starts after you sign in (10 minutes later, so it never competes with the logon rush),
and shows a small card in the bottom-right when there is something new, even with the app closed.
It politely waits when you are busy (full-screen games or video, presentations, locked screen,
Do Not Disturb) and shows only new items, never a month-old backlog. The card can carry an image and a link,
and has an X.
- Limits: a daily cap (default 2 popups per PC per day; more still go to the bell).
- Reporting: delivered, shown, clicked and dismissed are reported so the owner can see what works.
- Withdrawn notifications close their popup.
*(Technical aspect: `CustomSystemCare.Agent` (NativeAOT, runs as the normal user, never elevated) and
`CustomSystemCare.Popup` (a small native GDI window). Updates arrive in real time through a websocket
"doorbell" to the Reverb server, with an hourly fallback poll, reconnect with backoff, and a faster
retry when the network returns. The 0 to 30 s random wait on a broadcast keeps every PC from hitting the
server at once. Busy detection is `SHQueryUserNotificationState`. Popup text is English only for now.
The agent reads the device key from a DPAPI-protected shared file.)*

### 7.3 Automatic folder-clutter nudge **[Free]** *(optional add-on)*
When Desktop, Downloads or Documents holds too many items, the PC gets one notification about it, then stays quiet
for a cooldown.
*(Technical aspect: the agent sends daily counts only (no names) to `/api/agent/signals`; the server
rule `SignalsRule` sends one device-targeted notification through the normal pipeline. Switch, thresholds,
cooldown and wording are all admin settings.)*

---

## 8. Reports and the paid service

### 8.1 Email me the full report **[Free]**
Type your email and get a detailed PDF with plain English next to the technical detail. No cost and no
sign-up. It waits for the scan to finish, measures disk speed, then sends. It offers to keep you signed
in as that email.
*(Technical aspect: `POST /api/report-requests` (throttled), queued job `GenerateAndEmailReportJob`:
the AI writes a summary, a good/fair/poor rating, findings (title, technical detail, plain explanation,
severity) and recommendations from the dashboard snapshot only. The prompt forbids inventing anything,
and it is told to say so when the PC is healthy. Rendered to PDF and emailed.)*

### 8.2 Quote and invoice **[Free, owner-driven]**
After each scan the PC's findings go to the owner. If the owner prices a fix, the dashboard shows "We
reviewed your PC: N BDT to fix what we found." with a **Download** button that saves the invoice PDF to
Downloads.
*(Technical aspect: `SubmitDiagnosisReportAndPushStatusAsync` posts a diagnosis report, then reads the
latest quote and invoice for the device. The admin writes the quote and marks it paid. Entirely
best-effort: if the backend is down, the banner simply does not appear.)*

### 8.3 Weekly deep clean **[Pro, or a paid-invoice customer]**
Cleans junk once a week in the background and shows "Your weekly deep clean is running in the
background" (not on the very first launch). Turns off by itself when Pro ends.
*(Technical aspect: a persisted schedule entry for `disk-cleanup` (Weekly) in the in-app scheduler;
survives restarts. Counted into the lifetime totals.)*

---

## 9. Language and wording

- **English and Bangla**, switched with one button. Every app string has both, and the AI reasons come
  in both. Technical terms (RAM, SSD, Windows Defender, startup, cache) stay in English in Bangla text
  because that is how Windows spells them. Digits stay Western.
- **Simple wording by default** with a technical "reason" line on each card, and an Advanced toggle for
  the technical version.
- The popup is English only for now.

---

## 10. Safety and trust built into every action

- **Undo that survives a restart**: undo data lives on disk, and the app will not change a setting if it cannot save
  how to put it back.
- **Read-back verification**: a change is only reported as done after Windows confirms it.
- **Protected lists**: startup items and processes that Windows needs are never offered for switching off.
- **Allowlisted paths**: cleanup can only touch temp and cache locations.
- **AI narrows, never widens**: the AI can only pick from what the local safety rules already allowed,
  and a failed AI call means "do nothing".
- **Unknown is never "safe"**: an unreadable process or item, or an unanswered Windows query, always means
  "leave it alone and say so".
- **Always asks before anything permanent**: Recycle Bin, Windows Update and browser caches.
- **No invented problems**: every card is a measurement; unreadable ones say so.

---

## 11. Reliability (it should not crash, and if it does, the owner knows)

- A crash in the interface shows "Something went wrong. The app is still running and the problem was reported."
  and the app keeps going. A burst of repeated errors restarts it cleanly instead of freezing.
- A failed section shows a **Retry** button. A failed button reports the error to the owner.
- If the page's rendering process crashes, the window reloads (up to three times in ten minutes).
- **Missing WebView2** (the component that draws the window): a friendly screen with **Install** (bundled
  Microsoft installer, Store build), **Download from Microsoft**, and **Try again** that continues without restart.
- Crashes that skip every handler (native crash, forced kill, hang, stack overflow) are found on the next
  launch from the Windows event log and reported, with no personal data.
- The background helper and popup report their own crashes too.
*(Technical aspect: crash outbox on disk (50 files, 64 KB each) written before sending, `CrashReporter`,
`ErrorStormGuard` (5 errors in 30 s), JS error capture (rate-limited), `ProcessFailed` handling, session
marker plus `AbnormalExitScan` (events 1000, 1026, 1002), `fatal-reported` files to avoid double reports.
The server groups crashes by a fingerprint and ranks them in /admin/crashes. Reports carry app version, OS build,
language, memory, WebView2 version, CPU and maker/model, and never file contents.)*

---

## 12. How your data and the connection are protected

- **No account needed to run.** The PC is identified by a one-way hash of hardware IDs, so raw serial
  numbers never leave the PC.
- **Signed requests**: every request carries a timestamp, a one-time number and an HMAC signature; the
  server rejects stale or repeated ones.
- **The device key** is stored encrypted (Windows DPAPI) for the agent and in Windows Credential Manager for the app.
- **HTTPS only** for the backend and for vendor downloads (local testing may use loopback).
- **Files and backups stay on your PC**; Wi-Fi passwords go only to the backup drive.
- **A public privacy policy** lists everything that is sent, why, who handles it and how to ask for deletion.
*(Technical aspect: `RequestSigner` (HKDF-derived key), `SharedDeviceKey`, `CredentialStore`, `UrlValidation`.)*

---

## 13. Installing, starting and updating

- **Windows installer (Inno Setup)**: Start-menu entry, optional desktop icon, installs the WebView2 component if
  missing (and stops with a clear message if that fails), provisions the device key, registers the
  notification helper to start 10 minutes after logon, and a clean uninstall that removes the helper and popup.
- **Microsoft Store package (MSIX)**: the same app, signed by Microsoft; the helper starts through the package's startup task.
  Permissions: full-trust desktop app, runs elevated (UAC), real (unvirtualised) writes. Updates through the Store.
  Verified on Windows 10 and 11.
- Self-contained: no .NET install needed.
- **No in-app updater** exists in the code: updates come from the Store (or a new installer).
- Windows 10 version 2004 or later, 64-bit.

---

## 14. Admin panel (owner only) **[Admin]**

- **Dashboard**: the owner's overview.
- **Features**: set each feature's Free or Pro tier, whether it uses AI, whether it charges credits, and its
  free uses, without a release.
- **Essential software**: the curated installer list used by software restore.
- **Customers, devices**: see each PC, assign it to a user, sign it out, change its device limit; grant or revoke a
  license, add credits.
- **Orders, invoices, coupons**: orders and "mark paid", invoices with PDF, create and switch coupons on and off.
- **Reports, quotes, report requests**: read every PC's diagnosis report and write a quote; see emailed
  report requests.
- **Crashes**: "Top crashes" grouped by fingerprint (count, devices, versions, source, distribution), filters,
  and the raw list.
- **Measurements, telemetry, software inventories**: per-PC history, scan snapshots, installed-program lists.
- **AI usage**: tokens, cost and credits per feature.
- **Settings**: every price, credit and popup limit.
- **Notifications**: compose in English and Bangla with image and link, target everyone, chosen PCs, chosen
  users or an app version, send now or schedule, set an expiry, send a test to one PC, duplicate, retract,
  see delivered/shown/clicked/dismissed.
- **Signals**: the automatic clutter-nudge rule.
- **Remote support**: see 16.
- Sign-in with password reset by email.

---

## 15. Backend services (what the server runs)

Registration of devices (throttled), a feature catalogue the app reads, the license and checkout APIs, credit
ledger, notifications inbox and events, report and crash intake (throttled), measurement, savings and
telemetry intake, software inventory and icon store, essential-software list, invoice PDFs, the three
AI endpoints, the emailed-report queue job, a scheduled-notification release command, a crash-fingerprint
backfill command, an admin-user creation command, and a public `/privacy` page.

---

## 16. Built but not exposed, and not built

- **Remote support** **[Built, not exposed]**: screen sharing and remote control with a consent window, a relay
  server, an admin viewer and a poll endpoint. The client was unwired on 25 September 2026, so no user can
  trigger it. The code is kept.
- **AI Technician** (a free-form AI agent): deferred; drafts parked in `ai-technician-requirements-to-build/`.
- **AI troubleshooting chat**: present in the feature registry as inactive.
- **Windows Update control**: `windows-update-control.md` is implementation hints only; no module exists.
- **Items in `pc-optimizer-master-checklist.md`** beyond what is listed above are plan, not product.

---

## 17. Where the app's words and the code disagree (check before you promise anything)

1. **"I can uninstall them if you want"** (programs not used for a while). There is no uninstall action in
   the code. The list is report-only. Either build it or reword it.
2. **"Ask the AI" looks like a free-form assistant.** It is not (section 5). The greeting says "ask me
   anything about what you see there". Either build the chat (the registry already has an inactive
   `ai-troubleshoot` feature for it) or reword the greeting and suggestions.
3. **Restore lists a "curated" installer tier** that the code itself notes is empty by default.
4. **The Google Drive mention** in a "not enough space" message is only advice text; there is no Google
   Drive feature.

Decided, not open (1 October 2026):
- **Currency is USD only.** Quotes, invoices and paid-feature prices are now in US dollars, the same as plans
  and credit packs. The database columns and API fields were renamed from `*_bdt` to `*_usd`.
- **The corner popup is English only in v1**, on purpose.
- **Wi-Fi passwords are saved in plain text** on the user's own backup drive, by decision.

**About the Free/Pro tiers in section 0:** the table shows what a fresh install of the backend is seeded with.
The owner can change any feature's tier in the admin panel (Features page), and that takes effect at once,
without releasing a new app. So the table is correct for a clean setup, but the real, live split is whatever
the Features page on the production server holds. Open that page once and make sure it matches section 0.
