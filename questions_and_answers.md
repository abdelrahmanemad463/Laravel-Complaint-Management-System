# Questions and Answers

Practical, implementation-focused Q&A for the Quality Visits (inspection) module and the rest of the application. Read this whenever you revisit behavior and want a quick "how does it actually work" answer. Answers reference the source of truth in the codebase rather than repeating it.

---

## 1. Why must a Critical (non-compliant) item have an evidence photo, while a Major/Minor item does not? How is that decided and enforced?

An evidence photo is **mandatory only when the item is both Non-Compliant (`nc`) AND Critical**, or when the checklist item was explicitly configured with the `photo_required` flag. It is **not** based on the deduction score — a Minor violation worth many points stays optional in terms of evidence.

The rule is implemented in the model, `app/Models/VisitorVisitItem.php`:

```php
public function isCritical(): bool
{
    return strtolower((string) $this->severity) === 'critical';
}

public function requiresPhoto(): bool
{
    return ($this->status === 'nc' && $this->isCritical()) || $this->photo_required;
}
```

The full chain:

1. **Master data decides eligibility.** Each checklist item carries a `severity` (`critical` / `major` / `minor`) and an optional `photo_required` boolean, imported through the Excel master-data flow. Both are snapshotted onto the visit item at visit creation, so the rule never changes mid-visit.
2. **The UI advertises the requirement.** On the inspection page (`resources/views/visitors/show.blade.php`), critical items show an **`Evidence *`** badge. When the item is marked Non-Compliant, a warning panel is shown under the photo uploader.
3. **Submission is blocked in two layers.**
   - **Client side:** the submit handler in `show.blade.php` scans every Critical+NC item; if any has no uploaded photo link, it stops the form and alerts `visitors.evidence_photo_required`.
   - **Server side:** `VisitService::submit()` runs the same check from the database (`photos->isEmpty()`), so the rule cannot be bypassed through the API:
     ```php
     $missingPhotoItems = $visit->items->filter(function ($item) {
         if (!$item->isNonCompliant() || !$item->requiresPhoto()) return false;
         return $item->photos->isEmpty();
     });
     ```
     If any are found, it throws `__('visitors.evidence_photo_required')` and the visit stays `in_progress`.

Net effect: `critical + nc` → photo required; every other combination (major/minor, or a compliant/NA critical item) → photo optional, unless that item was configured with `photo_required`.

---

## 2. How does clicking a status button (e.g. "Compliant") save the answer to the database immediately?

The status buttons (`status-ok`, `status-nc`, `status-na`) are wired to a vanilla-JS click handler in `resources/views/visitors/show.blade.php`. There is no page reload and no form POST for each answer — each click is autosaved over a JSON endpoint.

The flow, step by step:

1. **Click → optimistic UI.** The handler sets `itemEl.dataset.status` to the clicked value, shows/hides the Non-Compliant details panel, and then calls `saveItem(itemEl, { status: value })`. Styling is refreshed after the response so only the selected button is highlighted.
2. **Autosave request.** `saveItem` builds a `FormData` body with `_method=PUT`, the CSRF token, and the status (plus root cause / note / support department when Non-Compliant), then sends a `fetch('POST /visitors/items/{id}')` with an `Accept: application/json` header. *(Main Kitchen was removed from the inspection page on 2026-09-02 — the checkbox and its `main_kitchen` payload field are gone, but the DB column remains for legacy data.)*
3. **Server persistence.** `VisitItemController@update` authorizes the item's visit, runs `VisitService::saveItem()`:
   - stores the status and stamps `visited_at = now()` on the item's **first** review (this is what makes it count as reviewed/reported in progress);
   - when the status is `nc`, it stores `root_cause_id`, `note`, `support_department` (`main_kitchen` still accepted by the backend for legacy compatibility but no longer sent by the page);
   - for any other status it clears those NC fields (deleting a stray NC detail is impossible by design);
   - the status itself is validated to be one of `ok | nc | na` by `UpdateVisitItemRequest`.
4. **Recalculate + respond.** After saving, the controller reloads the visit's items and computes the score via `VisitScoreService::calculate()` — the single source of truth (available = sum of deduction over non-`na` items, deduction = sum over `nc` items; percentage = `(final / available) * 100` with a blue/green/yellow/red class). It returns JSON: `{ ok, status, visited_at, reviewed, total, score }`.
5. **Refresh progress in the browser.** On success the JS sets `data-visited="1"` and `data-status`, then calls `refreshCounts()` — updates the header progress (`reviewed / total`), the progress bar, per-section counters and the mobile section dropdown. *(2026-09-02: the live score was removed from the inspection page — there is no `applyScore` anymore, so the score is not shown while filling out a visit and only appears on the completed-visit reports/PDF/dashboard.)*

So one tap on "Compliant" = one fetch → one DB write → one authoritative score response (used by reports) → progress + button highlight refresh in place, with no score exposed to the visit creator.

---

## 3. How does the Reports page color filter decide a visit's color? What do Blue / Green / Yellow / Red mean, and is it a percentage of the total items?

The color is **not** a count-based "compliant items ÷ total items" ratio. It is a **weighted score percentage** — how much of the available (achievable) score the visit kept, giving each item a weight equal to its `deduction_score`.

### The formula

```
available = Σ deduction_score over items where status ≠ 'na'   ← the achievable total
deduction = Σ deduction_score over items where status = 'nc'   ← points lost to violations
percentage = (available − deduction) / available × 100
```

Two consequences worth spelling out:

- **Not Applicable (`na`) items are removed from the denominator**, so a visit that was not subject to an item is not penalized for it — the color measures performance only over the items that actually applied to the visit.
- **Heavier items matter more.** A single Critical item worth a large `deduction_score` drags the percentage far more than several `minor` items with small weights. Two visits with the same count of NC items can get different colors purely because of the deduction weights.

### The thresholds (`VisitScoreService::colorFor()`)

| Color | Meaning (English localization) | Score percentage |
|---|---|---|
| `blue` | Blue (Excellent) | ≥ 85 |
| `green` | Green (Good) | 75 – 84.9 |
| `yellow` | Yellow (Fair) | 68 – 74.9 |
| `red` | Red (Needs action) | < 68 |
| `slate` | N/A | percentage is `null` (no available score, e.g. all items Not Applicable) |

### How the filter works under the hood

1. The reports page renders one checkbox per color from the same `score_class_*` keys used everywhere, submitted as `colors[]` (`resources/views/visitors/reports/index.blade.php`).
2. `VisitorReportService::listReports()` keeps the exact same percentage expression in SQL and, for each selected color, adds an `orWhereIn('id', colorSubquery($color))`. Each subquery aggregates `visitors_visit_items` per visit group and uses `HAVING` on the band range (e.g. `pct >= 75 AND pct < 85` for green). The SQL repeats the whole percentage expression instead of using a select alias because SQL Server cannot reference select aliases inside `HAVING`.
3. The percentage formula lives in exactly two mirrored places by design — `VisitScoreService::fromAggregates()`/`colorFor()` (single source of truth for a single report) and `VisitorReportService::colorSubquery()` (batch SQL) — so the web report, the print view, and the filter can never disagree.

So when you pick "Green (Good)" in the filters, you are asking for: visits whose weighted compliance percentage is between 75% and 85%.

---

## 4. When does a Corrective Action Plan item change from Open to Closed, and why does my report still show Open?

A CAPA row is created at submission, not at inspection, and it stays **Open** until the follow-up + review workflow runs. There is no "auto-close" after a second visit; historical reports are frozen snapshots.

### Lifecycle

1. **Created as Open on submit.** On submission, `VisitService::submit()` calls `CapaService::processViolationsOnSubmit()` (`CapaService.php:94`), which loops every `nc` item and calls `CapaService::createFromNonCompliant($visit, $item)` (`CapaService.php:61`) once per NC item, creating one `visitors_capa_actions` row with `status = 'open'` and the snapshot columns `item_code`/`item_title`/`immediate_action`/`corrective_action`/`preventive_action`/`responsible`, then writes an initial `visitors_capa_updates` entry `status = 'open', comment = 'Violation recorded during inspection.'`. **Due dates are never entered by hand.** The action copies `period_hours` from the visit-item snapshot (which was snapshotted from `visitors_checklist_items` master data at visit start via `VisitService::start()`), then computes `due_at = action.created_at + period_hours` ONCE at creation via `DueDateService::dueDate()` (`DueDateService.php:102`) — stored forever, never recomputed. `due_at` stays `NULL` when `period_hours` is `0` (Immediate). If `responsible_user_id` was never filled from the checklist import, it remains `NULL` — which is why your report shows Responsible = branch text.
2. **Stored vs. effective status.** The column `visitors_capa_actions.status` holds the real workflow values `open | in_progress | pending_review | closed | rejected`. `VisitorCapaAction::effectiveStatus()` (`app/Models/VisitorCapaAction.php:80`) is the single place the report, PDF and dashboard ask for the status — it delegates to `App\Services\Visitors\DueDateService::dueStatus()` (the single source of truth, `DueDateService.php:177`), which derives the full **virtual** taxonomy: `immediate`, `upcoming`, `due_soon`, `overdue`, `completed`, `closed_late`, `rejected`. `overdue` is virtual (not stored) when the action is `open`/`in_progress` and `due_at < now()`; nothing is written to the DB for it, reports simply display it. The due-soon window is `config('visitors.due_soon_hours')` (default 24). Period labels (`Immediate` / `1 day` / …) come from `DueDateService::hoursLabel()` (`DueDateService.php:118`).
3. **How Closed happens (since 2026-09-06).** **(a) Resolve** — the inspector submits resolution from the violation page (`POST visitors/violations/{capaAction}/resolve`). `CapaService::submitResolution()` (`CapaService.php:177`) requires the action to be `open|in_progress` and, for **Critical** violations, that a resolution evidence photo exists (`evidence_role='resolution'` attached to the action); it then sets `status = 'pending_review'` + `submitted_review_at = now()`. **(b) Approve/reject** — a `visit.review`-permission holder (Quality Manager) calls `approve` (`status = 'closed'`, `completed_at = reviewed_at = now()`, `reviewed_by`) or `reject` (requires a `reject_reason`, sets `status = 'in_progress'` so the inspector can try again). Both `approve`/`reject` throw `capa_not_pending_review` unless the action is already `pending_review`. Every transition appends a `visitors_capa_updates` history row. The effective status compares `completed_at` against the stored `due_at` to distinguish `completed` (closed on/before due) from `closed_late` (closed after due).
4. **No duplicate open findings.** On the next inspection of the same branch, `CapaService::existingOpenMap()` and the inspection page show an amber **Existing Open Violation** panel when an action for the same `checklist_item_id` is still `open|in_progress|pending_review`. The inspector picks **Still Open / Resolved / New Violation**; on submit, `VisitService::processViolationsOnSubmit()` routes each NC item accordingly — `still_open` just appends a `follow_up` update to the linked action, `resolved` runs `submitResolution`, and anything else creates a brand-new action. So a still-open finding is not duplicated across reports.
5. **Why your report (#22) is still all Open.** If nobody has run resolve/approve, the actions from that submission have never left `open`. Reports only ever reflect the current stored state of `visitors_capa_actions`; the **originating visit's snapshot columns never change**, so historical reports stay byte-identical even after the CAPA moves on. The old per-row Close button on `reports/show.blade.php` was removed — the report is now read-only; lifecycle actions live on the violation list/show pages (`/visitors/violations`). For manual verification without UI you still can:
   ```php
   $a = \App\Models\VisitorCapaAction::find($id);
   app(\App\Services\Visitors\CapaService::class)->approve($a); // action must be pending_review first
   ```
   To also make `overdue`/`due_soon`/`upcoming` meaningful, the action needs a `period_hours` + `due_at` pair, both set at creation (a `NULL` `due_at` renders `Immediate`). **Do not hand-set `due_date`** — today an `UPDATE visitors_capa_actions SET due_date = ...` no longer drives the reports (they read `due_at`/`period_hours`). To backfill a legacy action manually you must set both the period and the due timestamp, e.g. (SQL Server) `UPDATE visitors_capa_actions SET period_hours = 72, due_at = DATEADD(hour, 72, created_at) WHERE id = ?`. For the shipped migration, `2026_09_04_190000_replace_deadline_with_period_hours.php` already backfilled `period_hours`/`due_at` from the old text `deadline` (via `DueDateService::periodToHours`) and legacy date-only `due_date` (seeded `due_at` at `startOfDay`), so newly-capable periods are correct without manual work.

So: the report faithfully renders the current CAPA workflow state. Since the 2026-09-06 lifecycle, follow-ups are handled from the inspection page and approvals from a dedicated quality reviewer — open the violation page, hit **Resolve**, then approve/reject.

---

## 5. Why does a visitors-only user get "Unauthorized" right after login, and how do I set where they land?

The two dashboards are now separately permissioned and the login landing page is per-user.

- **Permissions:** `dashboard.view` (Complaint Dashboard at `/` — `DashboardController` now checks `abort_unless(can('dashboard.view'))`) and `dashboard.visitors.view` (Visitors Dashboard at `/visitors/reports/dashboard` — gated via `dashboard.visitors` = `dashboard.visitors.view || report.view || visit.manage`). `Viewer` and `Admin` have both by seeder; inspectors (`Customer Support`) keep `dashboard.view`; a visitors-only role should be given `dashboard.visitors.view` + the needed `visit.*` permissions via `roles/{role}/edit`.
- **Per-user default:** `users.default_home` (`dashboard` / `visitors.dashboard` / `complaints` / `visitors`), set on `users/form.blade.php` as Default Home Page (لوحة تحكم الشكاوى `/` / لوحة تحكم الزيارات `/visitors/reports/dashboard` / الشكاوى `/complaints` / زيارات الجودة `/visitors`). `AuthController@login`/`showLogin` first honors `default_home` if the user actually has that permission, otherwise it falls back to the first dashboard they are allowed to see: `dashboard.view` → `/`, `dashboard.visitors.view` → `/visitors/reports/dashboard`, then `complaint.view` → `/complaints`, `visit.view` → `/visitors`; a logged-in user hitting `GET /login` is redirected to that same home instead of seeing the login form. A user with only `visit.*` + `dashboard.visitors.view` will therefore land on the visitors dashboard instead of being bounced to a forbidden complaint dashboard.

So: give the role `dashboard.visitors.view` (or `complaint.view`/`visit.view` for the list pages) and set its users' Default Home to Visitors Dashboard / Quality Visits.

---

## 6. How are evidence photos compressed and downscaled before they are stored?

Every evidence photo goes through the same one-way pipeline in `app/Services/Visitors/VisitorPhotoService.php` — `store(UploadedFile $file)` does: **validate → decode → downscale → re-encode as JPEG quality 80 → save on the private `local` disk**. There are no temp files and no third-party libraries; compression uses PHP's built-in GD extension, and the visit item only ever sees the resulting `VisitorPhoto` record (whose `compressed_size` column shows how small the output got).

### Logic, step by step

1. **Size gate.** `MAX_ORIGINAL_SIZE = 20 MB`; any larger upload throws `RuntimeException('The photo exceeds the maximum allowed size of 20 MB.')`.
2. **GD decode.** PNG and WebP are loaded with `imagecreatefromstring($contents)`; JPEG uses `imagecreatefromjpeg($file->getRealPath())`. A failed decode (`@` suppresses the warning) throws `'The uploaded file is not a valid image.'`.
3. **Downscale.** If either dimension exceeds `$maxDim = 1600`, the ratio `min(1600 / width, 1600 / height)` is applied and the image is resampled with `imagecopyresampled()` into an `imagecreatetruecolor()` canvas — the longest edge becomes ≤ 1600 px, which shrinks the pixel data before any encoding happens.
4. **Re-encode to JPEG q80.** GD writes directly into an output buffer instead of a file:
   ```php
   ob_start();
   imagejpeg($image, null, 80);   // null file = write to the output buffer
   $compressed = ob_get_clean();
   imagedestroy($image);
   ```
5. **Persist.** `Storage::disk('local')->put($path, $compressed)` where `$path = 'visitor-photos/Y/m/d/'.Str::uuid().'.jpeg'` — so **every** photo is stored as a `.jpeg` with `mime_type` forced to `image/jpeg`, even when the source was PNG or WebP. Alpha transparency is flattened (transparent areas become black), and the original pixel dimensions are not kept.
6. **Return.** The method returns `{ path, original_name, mime_type: 'image/jpeg', original_size, compressed_size: strlen($compressed) }`, which is exactly what creates the `VisitorPhoto` row. Photos are served back later through the `visitors.photos.serve` route (`VisitPhotoController@serve`).

### Example — the relevant part of the real service

```php
// app/Services/Visitors/VisitorPhotoService.php
public function store(UploadedFile $file): array
{
    if ($file->getSize() > self::MAX_ORIGINAL_SIZE) {            // 1. 20 MB cap
        throw new RuntimeException('The photo exceeds the maximum allowed size of 20 MB.');
    }
    $originalName = $file->getClientOriginalName();
    $mime = $file->getMimeType();
    $originalSize = $file->getSize();

    $image = $this->loadImage($file, $mime);                     // 2 + 3. decode + downscale to <= 1600px
    $path = 'visitor-photos/'.date('Y/m/d').'/'.Str::uuid().'.jpg';

    ob_start();                                                  // 4. JPEG quality 80
    imagejpeg($image, null, 80);
    $compressed = ob_get_clean();
    imagedestroy($image);

    Storage::disk('local')->put($path, $compressed);             // 5. private local disk

    return [                                                     // 6.
        'path' => $path, 'original_name' => $originalName, 'mime_type' => 'image/jpeg',
        'original_size' => $originalSize, 'compressed_size' => strlen($compressed),
    ];
}

private function loadImage(UploadedFile $file, string $mime)
{
    $contents = file_get_contents($file->getRealPath());
    $image = match ($mime) {
        'image/png', 'image/webp' => @imagecreatefromstring($contents),
        default                   => @imagecreatefromjpeg($file->getRealPath()),
    };
    if (!$image) throw new RuntimeException('The uploaded file is not a valid image.');

    $maxDim = 1600; $width = imagesx($image); $height = imagesy($image);
    if ($width > $maxDim || $height > $maxDim) {
        $ratio = min($maxDim / $width, $maxDim / $height);
        $resized = imagecreatetruecolor((int) round($width * $ratio), (int) round($height * $ratio));
        imagecopyresampled($resized, $image, 0, 0, 0, 0, imagesx($resized), imagesy($resized), $width, $height);
        imagedestroy($image); $image = $resized;
    }
    return $image;
}
```

Net effect: a ~8 MB JPEG phone photo becomes a ≤1600 px, quality-80 JPEG (typically a few hundred KB — exactly the number stored in `compressed_size`), and PNG/WebP uploads are converted to JPEG so the private disk only ever holds one format.

---

## 7. How can a Super Admin open another inspector's in-progress visit from a copied link, while everyone else gets "This action is unauthorized"?

The route is `visitors/{visit}` (name `visitors.show`), handled by `VisitController@show`, which calls `$this->authorize('view', $visit)`. That triggers policy `VisitorVisitPolicy::view` — **owner only**:

```php
// app/Policies/VisitorVisitPolicy.php
public function view(User $user, VisitorVisit $visit): bool
{
    return (int) $visit->inspector_id === (int) $user->id;   // only the inspecting user passes
}
```

So when a regular inspector pastes a copied link to a colleague's visit, the policy returns `false`, `authorize()` throws `AuthorizationException`, and Laravel renders the **403 "This action is unauthorized"** page. Same pattern guards `update()` and `submit()` (both also require not-completed), so a non-owner can't touch the visit either.

### Why a Super Admin sails through — the `Gate::before` bypass

`app/Providers/AppServiceProvider.php:25` registers a global "before" hook that Laravel runs **before every policy/permission check**:

```php
Gate::before(function ($user, string $ability) {
    return $user->hasRole('Super Admin') ? true : null;
});
```

- **Super Admin** → hook returns `true`, so the check short-circuits to "allowed" and `VisitorVisitPolicy` is never consulted.
- **Everyone else** → hook returns `null` ("no opinion"), so normal policy evaluation continues — owner-only → foreign visit → 403.

This works because the `Super Admin` role is created with **every seeded permission** in the seeder (`PermissionSeeder` → `$super->syncPermissions($all)` → assigned to `admin@example.com`), and the role itself is protected: the roles screen disables its checkboxes (`roles/form.blade.php`) and only a Super Admin may assign or remove it (`UserController` aborts 403).

### What this means in practice

- A Super Admin pasting any `visitors/{id}` link can view — and even **update / submit** — a visit that another inspector has in progress (the bypass covers all abilities on `VisitorVisit`, not just `view`).
- The same `null` (normal) path is why non-admins get the exact list they're allowed to see: on the reports page, `viewReport` returns `false` for foreign **in-progress** visits for everyone, and for completed reports only `visit.manage` holders (or the inspecting user with `report.view`) pass.

So: "action not authorized" = the policy's owner check failed; "clicking into it works for the boss" = `Gate::before` short-circuits the check to `true` for anyone holding the `Super Admin` role.

---

## 8. Why do two Laravel apps on the same host (e.g. `localhost/complaint` and `localhost/POS`) log each other out on the same browser?

Because both ship the **default session cookie name `laravel_session`** and both set it for the **same host (`localhost`) at cookie path `/`** — and a browser only keeps **one cookie per (name, domain, path) trio**. Whichever app logs in last overwrites the shared cookie, so the other app suddenly can't authenticate.

### Why the other app then "logs you out"

The cookie alone doesn't carry the session — it only stores the session **ID**, which the app must (1) decrypt with **its own `APP_KEY`** and (2) look up in **its own session store**. `http://localhost/complaint/public/` and `http://localhost/POS/public/` are two independent Laravel installs with different `.env` → different `APP_KEY` → different encrypted cookie and different `sessions` table/file. When app B (POS) overwrites the `laravel_session` cookie, the next request to app A (complaint) reads a cookie encrypted with B's key (and pointing at a session ID B created). A cannot decrypt it or find the session, so A treats the user as a guest — the same in reverse. Login to A effectively "kicks you out" of B and vice versa.

### The fix (applied to this repo on 2026-09-04)

Give each app a **unique session cookie name** in its own `.env`:

```dotenv
# C:\xampp\htdocs\complaint\.env
SESSION_COOKIE=complaint_session

# C:\xampp\htdocs\POS\.env   (sibling app — do the same there)
SESSION_COOKIE=pos_session
```

`config/session.php:130` already reads `env('SESSION_COOKIE', 'laravel_session')`, so no code change is needed — the value flows straight into the response cookie. Optionally scope the cookie path per app too (`SESSION_PATH=/complaint/public` and `/POS/public`) so each browser only sends the relevant cookie to the matching app, but the unique name alone is sufficient.

After changing `.env` run `php artisan config:clear` in each app (so the cached config picks up the new value) and clear `localhost` cookies once in the browser to drop the stale `laravel_session` cookie. Both apps then keep their own login independent of the other.

> Note: Laravel also sets a shared `XSRF-TOKEN` cookie on the same host, but this app reads the CSRF token from the `<meta name="csrf-token">` tag / hidden `@csrf` inputs (never from that cookie), so the cookie-name collision does **not** affect CSRF here.

---

## How to add more entries

Keep each entry self-contained: rephrase the question the way a future developer would google it, then answer with the exact file/line patterns and the business rule behind them. If an answer changes because the code changes, update it here in the same edit and note the change in `memory.md`.