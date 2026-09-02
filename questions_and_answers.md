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

## 2. How does clicking a status button (e.g. "Compliant") save the answer to the database immediately and update the live score?

The status buttons (`status-ok`, `status-nc`, `status-na`) are wired to a vanilla-JS click handler in `resources/views/visitors/show.blade.php`. There is no page reload and no form POST for each answer — each click is autosaved over a JSON endpoint.

The flow, step by step:

1. **Click → optimistic UI.** The handler sets `itemEl.dataset.status` to the clicked value, shows/hides the Non-Compliant details panel, and then calls `saveItem(itemEl, { status: value })`. Styling is refreshed after the response so only the selected button is highlighted.
2. **Autosave request.** `saveItem` builds a `FormData` body with `_method=PUT`, the CSRF token, and the status (plus root cause / note / main kitchen / support department when Non-Compliant), then sends a `fetch('POST /visitors/items/{id}')` with an `Accept: application/json` header.
3. **Server persistence.** `VisitItemController@update` authorizes the item's visit, runs `VisitService::saveItem()`:
   - stores the status and stamps `visited_at = now()` on the item's **first** review (this is what makes it count as reviewed/reported in progress);
   - when the status is `nc`, it stores `root_cause_id`, `note`, `main_kitchen`, `support_department`;
   - for any other status it clears those NC fields (deleting a stray NC detail is impossible by design);
   - the status itself is validated to be one of `ok | nc | na` by `UpdateVisitItemRequest`.
4. **Recalculate + respond.** After saving, the controller reloads the visit's items and computes the score via `VisitScoreService::calculate()` — the single source of truth (available = sum of deduction over non-`na` items, deduction = sum over `nc` items; percentage = `(final / available) * 100` with a blue/green/yellow/red class). It returns JSON: `{ ok, status, visited_at, reviewed, total, score }`.
5. **Live updates in the browser.** On success the JS sets `data-visited="1"` and `data-status`, then calls:
   - `refreshCounts()` — updates the header progress (`reviewed / total`), the progress bar, per-section counters and the mobile section dropdown;
   - `applyScore(score)` — updates the big live score percentage, the `final / available` figure, and the score colour.

So one tap on "Compliant" = one fetch → one DB write → one authoritative score response → progress + score + button highlight all refresh in place.

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

## How to add more entries

Keep each entry self-contained: rephrase the question the way a future developer would google it, then answer with the exact file/line patterns and the business rule behind them. If an answer changes because the code changes, update it here in the same edit and note the change in `memory.md`.