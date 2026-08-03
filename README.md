# AI-Powered Form Builder

A working AI-powered form builder built with **Laravel 11**, **Livewire 3**, **MySQL 8** and **Tailwind CSS**. Build forms manually with drag & drop, generate them from a natural-language prompt with AI, import from Word/Excel, and — in Part D — arm them with analytics, spam protection and a template library.

> **Status:** Parts A–D are complete. See `DECISIONS.md` for the design decisions behind each part.

## Demo

- Live URL: _pending deployment_
- Demo login: `demo@example.com` / `password` (seeded by `DatabaseSeeder`)

## Features

### Part A — Core form builder (done)
- Add fields by **drag & drop** (SortableJS) and **click-to-add**; reorder, duplicate, edit inline, delete.
- 15 field types: text, textarea, number, email, phone, URL, date, time, dropdown, radio, checkbox, file upload, rating, section heading, paragraph.
- Fields group into **sections** (reorderable); per-field config for label, key, placeholder, help text, default, required flag, options and validation rules (min/max, length, numeric/email/URL/regex, file mime/size, min/max selections).
- **JSON schema is the single source of truth**: raw editor with two-way sync to the canvas, validated before save.
- Every form gets a **public fill URL**; server-side validation is derived from the schema (never trust the browser).
- Submissions stored, listed with pagination + search, and **exportable to CSV**.
- Form **versioning**: every save records a schema version you can view (foundation for rollback in Part D).
- Conditional visibility rules per field (Part D feature already exercised by the schema).

### Part B — AI form generation (done)
- Turn a natural-language prompt (e.g. *"internship application with education history, skills and resume upload"*) into a complete, fully editable schema.
- Output is **schema-valid**; malformed/partial JSON is extracted, repaired or retried, and a broken schema is never persisted.
- **AI editing of existing forms** ("add an emergency contact section", "make phone required") — pick a form and the model receives the full current schema.
- Generation runs as a **queued job** (`GenerateFormJob`) with visible status (queued → processing → completed/failed) via Livewire polling — no web request blocked.
- **Model, token usage and latency are logged** against an `ai_generations` row and surfaced in the UI.
- Two drivers: `openai` (any OpenAI-compatible chat-completions API) and `mock` (deterministic offline generator) for demos/tests without an API key.

### Part C — Import from Word & Excel (done)
- Upload a `.docx` or `.xlsx` and get back an editable form.
- **Word:** Heading styles become sections, questions become fields, bullet / checkbox (`☐`) lists become options.
- **Excel:** two documented layouts (structured rows and plain header row).
- **Hybrid parsing:** deterministic first, AI only for ambiguous field types (see below).
- **Preview & mapping screen** before anything is committed — fix a wrongly detected type, label or required flag, then create the form.
- Files are parsed in a **queued job** (`ImportFileJob`); the preview page polls. Unparseable blocks are reported as warnings, and a broken file fails loudly with a clear message.
- Sample files are committed under `storage/import-samples/` and covered by tests.

### Part D — Differentiators (done)

1. **Form analytics & drop-off funnel** (`/forms/{form}/analytics`): summary cards (total responses, avg. questions answered, avg. filling time), a 7/14/30-day responses-per-day chart, and a per-question completion funnel that sorts lowest completion first so drop-off points surface immediately.
2. **Rate limiting & spam protection** on public forms: a per-form, per-IP `RateLimiter` (10 submits/min) on top of the existing honeypot + min-fill-time checks, with a friendly error for throttled humans.
3. **Template library** (`/templates`): four curated, schema-valid starter forms (Contact Us, Event Registration, Job Application, Customer Feedback). "Use template" creates a versioned draft form and drops you into the builder.

See `DECISIONS.md` for the problem/trade-off write-up behind each differentiator.

## Stack

PHP 8.2+ · Laravel 11 · Livewire 3 + Volt · MySQL 8 · Tailwind · SortableJS · Blade · ES6.

## Setup

```bash
cp .env.example .env
composer install
npm install && npm run build

php artisan key:generate
php artisan migrate --seed

# AI generation runs as a queued job — start the worker:
php artisan queue:work

# Optionally process the queue in the same process for local demos:
php artisan queue:work --once
php artisan serve
```

Open `http://localhost:8000`, log in with `demo@example.com` / `password`, then use **AI Generate** in the nav.

## Environment variables

| Variable | Default | Purpose |
| --- | --- | --- |
| `AI_DRIVER` | `mock` | `mock` (offline generator) or `openai` (any OpenAI-compatible endpoint) |
| `AI_BASE_URL` | `https://api.openai.com/v1` | Base URL of the chat-completions API |
| `AI_API_KEY` | — | API key. **Never commit a real key.** |
| `AI_MODEL` | `gpt-4o-mini` | Model identifier |
| `AI_MAX_TOKENS` | `3000` | Max tokens per call |
| `AI_TEMPERATURE` | `0.3` | Sampling temperature (low → more deterministic JSON) |
| `AI_TIMEOUT` | `90` | HTTP timeout in seconds |
| `AI_RETRIES` | `2` | Max retries when output fails validation |
| `QUEUE_CONNECTION` | `database` | Queue driver for the AI job |

## Architecture overview

```
Browser (Blade + Livewire + SortableJS)
   │
   ├─ /forms/*      → FormBuilder, FormList, FormSubmissions, FormAnalytics, FormVersions (Livewire)
   ├─ /f/{slug}     → PublicForm (public fill, spam-protected)
   ├─ /templates    → Templates (start from a curated template)
   ├─ /ai/*         → AiGenerate (prompt → queued job → status + preview)
   └─ /import/*     → FormImport (upload → queued parse → preview/mapping)

   AI path:
        AiGenerate → GenerateFormJob (queued)
                          │
                          ▼
              FormGenerator (drivers: openai / mock)
                 ├─ AiClient        → chat-completions REST, latency/token capture
                 ├─ AiFormGenerator → system prompt + retry loop + JSON repair
                 └─ MockFormGenerator → keyword→field mapping (offline demo)
                          │
                          ▼
                  FormSchemaValidator.normalize() + validate()
                          │
                          ▼
                  ai_generations row (status, model, tokens, latency)

   Import path:
        FormImport → ImportFileJob (queued)
                          │
                          ▼
               FormImportService
                  ├─ DocxParser  → raw OOXML (ZipArchive + DOM/XPath)
                  ├─ XlsxParser  → PhpSpreadsheet (2 layouts)
                  └─ FieldTypeGuesser → heuristics (+ optional AI assist)
                          │
                          ▼
                  import_previews row (draft + warnings)
```

- The **JSON schema** lives in `forms.schema` (JSON column) and is the single source of truth for the builder, public renderer, and server-side validation.
- **AI output** is stored in `ai_generations.result` only after passing `FormSchemaValidator::validate()`; the form is only written when the user clicks *Apply*.
- The AI layer is a **service** (`App\Services\AI`) so it can be swapped for a separate FastAPI microservice over REST later without touching Livewire.

## Database schema / ERD

| Table | Purpose | Key indexes |
| --- | --- | --- |
| `users` | Auth | `email` (unique) |
| `forms` | Forms; `schema` JSON is source of truth | `user_id`, `slug` (unique), `status` |
| `form_versions` | Versioned snapshots of `schema` | `form_id`, `version` (unique per form) |
| `form_submissions` | Public submissions; `data` JSON + denormalized `searchable` | `form_id`, `created_at` (pagination/search) |
| `ai_generations` | One row per AI run: prompt, mode, status, model, tokens, latency | `user_id`, `form_id`, `status`, `mode` |
| `import_previews` | One row per imported file: draft + warnings before commit | `user_id`, `file_type`, `status`, `form_id` |

Indexes are chosen for the queries that actually run at scale: lookups by `user_id` on every page, `slug` for public fill, `form_id` for submissions/versions, and `status`/`mode` for generation history.

## API endpoints

This is a Livewire app, so most interaction is over Livewire's JSON updates rather than a public REST API. Public endpoints:

| Method | URL | Auth | Purpose |
| --- | --- | --- | --- |
| GET | `/forms` | auth | List forms |
| GET | `/forms/{form}/edit` | auth | Builder canvas |
| GET | `/forms/{form}/submissions` | auth | Submissions + search + pagination |
| GET | `/forms/{form}/submissions/export` | auth | CSV export |
| GET | `/forms/{form}/analytics` | auth | Analytics & drop-off funnel (Part D) |
| GET | `/forms/{form}/versions` | auth | Version history |
| GET | `/templates` | auth | Template library (Part D) |
| GET | `/ai` · `/ai/{generation}` | auth | AI generator + status |
| GET | `/import` · `/import/{preview}` | auth | File import + preview/mapping |
| POST | `/f/{slug}` | public | Submit a form response (rate-limited) |

## AI prompt strategy (Part B)

**System prompt** (in `AiFormGenerator::systemPrompt()`):
- Asserts the model is an "expert form designer that outputs only JSON".
- Ships an **output contract**: the exact JSON shape (title, description, sections, fields) with field-level keys for `placeholder`, `help_text`, `default`, `required`, `options`, and the full `validation` object.
- Ships a **field-type whitelist** (text, textarea, number, email, phone, url, date, time, dropdown, radio, checkbox, file, rating). Types outside the whitelist are treated as hallucinated and dropped/coerced by the validator.
- Adds guidance: 1–3 sections of 3–8 fields, options required for choice fields, sensible mimes/max_size for files, realistic option values, "no markdown, no prose".

**Output contract enforcement:**
1. `JsonExtractor::extract()` strips markdown fences/prose, finds the outermost JSON object (balanced-brace scan), and best-effort repairs trailing commas and unquoted keys.
2. Output is normalized with `FormSchemaValidator::normalize()` (fills ids/keys/validation defaults, coerces option shapes) and validated with `validate()`.
3. On failure the failing output is fed back with a repair instruction and the call is retried (up to `AI_RETRIES`).
4. If still invalid, the job **fails** with a clear error — a broken schema is never persisted.

**Edit mode:** the current form schema is serialized into the user message with the instruction to return the *complete updated* schema, keeping structure unless the request changes it. This makes "add a section", "make required", "translate labels" all the same code path.

**Hallucinated field types:** the validator's `normalize()` maps any unknown type to `text` (a deliberate safe fallback). Choice fields without options are also normalized/flagged so the UI never renders a broken field.

**Retries & fallbacks:** HTTP retries live in `AiClient` (`retry(2, ...)`); validation-retry lives in `AiFormGenerator`. With `AI_DRIVER=mock` there is no network call at all, which keeps demos and CI deterministic.

## Import strategy (Part C)

### Word (.docx)

The parser reads the raw OOXML (`word/document.xml`) via `ZipArchive` + `DOMXPath` — not a reader library — so heading styles, list numbering and checkbox glyphs are detected exactly as Word wrote them.

| Document element | Becomes |
| --- | --- |
| Title style, or first Heading 1 | Form title |
| Heading 1/2/3 (rest) | Sections |
| Plain paragraph | Question/field (type inferred) |
| Bullet / numbered list (`w:numPr`) | Options on the previous field |
| `☐`/`☑`/`[ ]` checkbox list | Checkbox options |
| Title-Case short line (no sentence punctuation) | Section heading |
| Tables | Reported as a warning and skipped |

### Excel (.xlsx)

Uses PhpSpreadsheet with **two documented layouts**. The parser auto-detects which layout a sheet uses.

**Layout A — structured rows (recommended):**

| question | type | required | options | section |
| --- | --- | --- | --- | --- |
| Full Name | text | yes | | Contact |
| Department | dropdown | | Engineering, Design, Marketing, HR | Job Preferences |
| Why do you want to join? | textarea | yes | | Job Preferences |

`type` is optional (unknown/blank cells fall back to guessing). `options` are separated by `|`, `,` or `;`. `section` groups rows into named sections.

**Layout B — plain header row:** row 1 is field labels, one column per field. An optional `type` column is honoured. Sample data rows are ignored, except that a column whose sample values form a small distinct set (2–4 values) is offered as radio options.

### Hybrid parsing: deterministic first, AI only when ambiguous

1. **Deterministic pass** (`DocxParser`/`XlsxParser` + `FieldTypeGuesser`): type, options and required are inferred from the text via a conservative keyword/pattern table. Every result is a valid field type and is safe to commit as-is.
2. **AI pass** (only when `AI_DRIVER=openai`): fields the heuristic marks low-confidence are batched into a single call that maps question text → field type. The AI's choices are validated against the field-type whitelist; a failure or invalid answer silently keeps the heuristic guess, so an import never fails because the AI did.
3. **Human pass** (always): the preview/mapping screen shows every field with its detected type (with a high/low confidence badge) and lets you change type, label, required and options before committing.

Files are parsed in a queued job; large files never block the upload. Empty rows, unparseable blocks and skipped tables are collected into warnings shown on the preview screen.

## Known limitations

- AI quality depends on the model/provider; `mock` produces a keyword-based best guess.
- Analytics per-field completion is computed from stored JSON in PHP — fine for thousands of submissions, not millions (see `DECISIONS.md` D1).
- Rate limiting is per-IP (10/min); a shared NAT IP can hit the cap (see `DECISIONS.md` D2).
- Templates are curated in code, not in the database (see `DECISIONS.md` D3).
- Deployment (live demo URL) is pending.
