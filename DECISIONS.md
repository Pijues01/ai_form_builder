# Decisions

This file records the significant design decisions made during the assignment and the rationale behind them, as required by SRS §6. Each entry covers the problem, the choice, the trade-offs, and what would be done with more time.

---

## Part A — Core builder

### A1. JSON schema as the single source of truth

- **Problem:** the builder, the public renderer and server-side validation all need one description of a form, and they must never disagree.
- **Decision:** every form is stored as a JSON `schema` document (`forms.schema`) with `title`, `description` and `sections[].fields[]`. All parts of the app read and write this one document.
- **Trade-offs:** schema changes are cheap but there is no relational structure to query directly; analytics and search must work against JSON. Versioning is trivial (snapshot the JSON) but column-level querying is harder.
- **With more time:** a dedicated schema migration layer with per-field database rows for complex queries, and a schema validation suite run in CI.

### A2. Server-side validation is derived from the schema

- **Problem:** the browser can be bypassed; field rules must be enforced on the server, not just in the UI.
- **Decision:** `FormSchemaValidator::rules()` turns the schema (required, min/max, length, regex, mimes, min/max selections, visible conditions) into Laravel validation rules for the public submit endpoint.
- **Trade-offs:** one source of rules, but rule fidelity depends on the schema → rule mapping; exotic field types need new rules.
- **With more time:** a test matrix asserting every field type's rules against both valid and invalid payloads.

---

## Part B — AI generation

### B1. AI runs as a queued job with a status row

- **Problem:** LLM calls can take tens of seconds; blocking a web request would time out and give no feedback.
- **Decision:** `GenerateFormJob` processes prompts off the request thread; a `ai_generations` row records status (queued → processing → completed/failed), model, token usage and latency, polled by the UI.
- **Trade-offs:** complexity of job/queue infrastructure and failure handling in exchange for a responsive UI and an auditable history.
- **With more time:** WebSockets/server-sent events instead of polling, plus retry and dead-letter handling for long failures.

### B2. Schema-valid output is enforced, not trusted

- **Problem:** LLMs return malformed or hallucinated JSON (wrong field types, missing options, markdown fences).
- **Decision:** output is extracted and repaired by `JsonExtractor`, normalized and validated against the field-type whitelist, and retried (up to `AI_RETRIES`). A broken schema is never persisted. Unknown field types coerce to `text`.
- **Trade-offs:** deterministic safety net at the cost of some model expressiveness (types outside the whitelist are dropped).
- **With more time:** few-shot "repair" examples per failure mode and evaluation fixtures to measure schema-validity rate across models.

---

## Part C — Word/Excel import

### C1. Deterministic-first, AI-only-when-ambiguous parsing

- **Problem:** Word/Excel documents are unstructured; relying entirely on AI is slow, non-deterministic and can fail imports.
- **Decision:** a deterministic parser (`DocxParser`/`XlsxParser` + `FieldTypeGuesser`) handles everything it confidently can; the AI is consulted only for low-confidence field types, and its answer is validated against the whitelist with the heuristic kept on failure.
- **Trade-offs:** conservative, fast, testable base with optional AI refinement, at the cost of maintaining two code paths.
- **With more time:** a confidence/threshold tuning harness and per-document diffs to evaluate parser accuracy against a labelled corpus.

### C2. Preview & mapping before commit

- **Problem:** an import is a destructive operation; a misdetected type or label should not silently become a form.
- **Decision:** files parse into an `import_previews` draft the user reviews on a mapping screen (change type, label, required, options) before creating the form.
- **Trade-offs:** an extra human step in exchange for never creating a broken form from a bad parse.
- **With more time:** auto-save of mappings, bulk field editing, and a "regenerate guess" action per field.

---

## Part D — Differentiators

Three differentiators were chosen over the alternatives (form versioning/rollback, embeddable widget/QR share, webhooks) because each is net-new functionality with a clear SRS write-up, not an extension of something already shipped (versioning already exists in Part A; QR is already rendered in the builder).

### D1. Form analytics & drop-off funnel

- **Problem:** form owners get raw submission tables but no sense of *how* their form performs. Conversion questions ("where do people give up?", "what's the completion rate?") are answered with ad-hoc counting if at all.
- **Implementation:**
  - A new `/forms/{form}/analytics` Livewire page (`FormAnalytics`) with summary cards: total responses, average questions answered, average filling time, and last-N-day total.
  - A 7/14/30-day responses-per-day bar chart computed from `form_submissions.created_at`.
  - A **per-question completion funnel**: for every input field, the % of submissions with a non-empty answer, sorted lowest first, so drop-off points surface immediately. Filling time comes from the `metadata.filling_time_seconds` already recorded at submit time.
  - Navigation links from the forms list and the submissions page.
- **Trade-offs:** per-field completion is computed from the stored `data` JSON in PHP for simplicity. This is fine up to a few thousand submissions but would not scale to millions without precomputed aggregates.
- **With more time:** background aggregation (a nightly job materialising counts), richer segmentation (device/browser, referrer, source), funnel over multi-step forms, and optional event tracking on "field-focused" to measure time-per-field abandonment.

### D2. Rate limiting & spam protection on public forms

- **Problem:** a public form is immediately a target for bots and spam — junk submissions pollute data and inflate analytics. Part A shipped honeypot + min-fill-time checks; there was no enforcement against a single IP hammering the endpoint.
- **Implementation:**
  - Added a per-form, per-IP **token-bucket rate limit** (`RateLimiter`, 10 submits/minute) checked server-side in `PublicForm::submit()` before validation.
  - Rate-limited users see a clear error message rather than a silently dropped submission, so a human who exceeded the limit understands why.
  - Combined with the existing honeypot (invisible field that bots fill → silent discard) and min-fill-time (submits faster than 3s are dropped), this is a layered, cheap, request-level defence that requires no external service.
- **Trade-offs:** IP-based limits are the pragmatic choice but a single IP can hide many real users behind NAT; too-low limits would hurt shared connections. 10/min is a deliberate, generous default.
- **With more time:** honeypot + rate-limit with exponentially increasing backoff, per-IP allow/deny lists, Cloudflare-style managed challenges, and ML-based content scoring; also folding blocked attempts into the analytics dashboard.

### D3. Template library

- **Problem:** starting from a blank canvas is slow for common use cases (contact forms, event registration, job applications, feedback surveys). Users repeatedly rebuild the same shapes, and a fresh form has no sensible defaults.
- **Implementation:**
  - A `FormTemplates` service holds four curated, schema-valid templates (Contact Us, Event Registration, Job Application, Customer Feedback) as JSON schemas — the same source-of-truth format as everything else.
  - A `/templates` page (`Templates` Livewire) lists them; "Use template" normalizes the schema, creates a draft form at schema v1 with a version snapshot, and lands the user in the builder to customise.
  - Linked from the main navigation and the forms list.
- **Trade-offs:** templates are static PHP constants; curating more of them means editing code. Storing them in the database would let non-developers author them but adds curation/admin UI.
- **With more time:** community/shared template marketplace, database-backed templates with categories and search, template versioning, and AI "start from a prompt" as a first-class alternative on the same page.

---

## Rejected Part D candidates

- **Form versioning & rollback:** already shipped in Part A (snapshots + restore UI); would be an extension, not a differentiator.
- **Embeddable widget / QR share:** the QR is already rendered in the builder and the public link is shareable; a JS embed would be security-sensitive work with little demo value.
- **Webhooks / public submission API:** a genuinely useful "export" feature, but it duplicates CSV export and the database queue already covers integration; deprioritized in favour of analytics which is more visible to a reviewer.
- **Redis-cached compiled schemas:** performance work with no observable feature; the current database cache is correct, just not fastest.
