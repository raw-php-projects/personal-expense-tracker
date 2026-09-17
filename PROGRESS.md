# Progress

> **This file is the single source of truth for phase status.**
> Nothing else in the repo tracks progress. Read the [Update Protocol](#update-protocol) before editing it.

| | |
|---|---|
| **Next phase to start** | `01` — PHP Language Playground |
| **Currently working on** | `00` — Environment & Skeleton |
| **Overall** | 0 / 12 phases done |
| **Last updated** | 2026-09-17 |

---

## Status Board

| # | Phase | Status | Started | Completed |
|---|-------|--------|---------|-----------|
| 00 | [Environment & Skeleton](./plans/phases/00-setup.md) | `working` | 2026-09-17 | — |
| 01 | [PHP Language Playground](./plans/phases/01-language-playground.md) | `not started` | — | — |
| 02 | [PDO Connection & Reading](./plans/phases/02-pdo-read.md) | `not started` | — | — |
| 03 | [Forms, `$_POST` & Validation](./plans/phases/03-forms-create.md) | `not started` | — | — |
| 04 | [Categories](./plans/phases/04-categories.md) | `not started` | — | — |
| 05 | [Dashboard: Monthly Totals](./plans/phases/05-dashboard.md) | `not started` | — | — |
| 06 | [Filtering with `$_GET`](./plans/phases/06-filtering.md) | `not started` | — | — |
| 07 | [Edit & Delete](./plans/phases/07-edit-delete.md) | `not started` | — | — |
| 08 | [Security Hardening](./plans/phases/08-security.md) | `not started` | — | — |
| 09 | [Structure & UX Polish](./plans/phases/09-polish.md) | `not started` | — | — |
| 10 | [Manual QA + PHPUnit](./plans/phases/10-testing.md) | `not started` | — | — |
| 11 | [Stretch Goals](./plans/phases/11-stretch.md) | `not started` | — | — |

### Stretch Goal Items (Phase 11)

Track these separately — they're independent and can be done in any order.

| # | Item | Status |
|---|------|--------|
| S1 | [CSV Export](./plans/phases/11-stretch.md#s1--csv-export) | `not started` |
| S2 | [Monthly Report Page](./plans/phases/11-stretch.md#s2--monthly-report-page) | `not started` |
| S3 | [Recurring Expenses](./plans/phases/11-stretch.md#s3--recurring-expenses) | `not started` |
| S4 | [Budget Limits](./plans/phases/11-stretch.md#s4--budget-limits) | `not started` |
| S5 | [MySQL Migration](./plans/phases/11-stretch.md#s5--mysql-migration) | `not started` |

---

## Legend

| Status | Meaning |
|---|---|
| `not started` | No work done. Cannot have a Started date. |
| `working` | **Currently in progress.** Exactly one phase may be `working` at a time. |
| `done` | The phase's **Verify** section passed and its checkboxes are all ticked. |
| `blocked` | Stopped — needs a decision, a fix, or a missing prerequisite. Record why in the Log. |

---

## Update Protocol

Follow these rules exactly. They are what keeps this file honest.

### Starting a phase

1. Confirm the **previous** phase is `done`. If it isn't, stop — go and finish it first.
2. Change that phase's row to `working` **before writing any code for it**.
3. Fill its **Started** date (`YYYY-MM-DD`).
4. Update the header block: `Next phase to start` → the following phase, `Currently working on` → this phase.
5. Add a Log entry: `<date> · <phase> · started`.

### Finishing a phase

1. Work the phase file's sub-phase checkboxes to all-ticked.
2. Run the phase file's **Verify** section and confirm every item passes.
3. Change the row to `done` and fill **Completed**.
4. Update the header block: `Currently working on` → *nothing*, refresh `Overall` count and `Last updated`.
5. Add a Log entry: `<date> · <phase> · done`.

### Blocking a phase

1. Set the row to `blocked`.
2. Add a Log entry naming the blocker precisely — what you tried, what failed.
3. Do **not** start another phase to "work around it". Resolve it or ask.

### Hard rules

- **Only one phase may be `working` at a time.**
- **Always flip to `working` before starting work** — never do the work and update status afterwards.
- **Never skip a phase.** They're sequential: each depends on the one before.
- **A phase is not `done` because the code was written.** It's done when Verify passes.
- Update `Last updated` on every change.
- Phase files never carry status. If you see a status in a phase file, it's wrong — this file wins.

---

## Log

| Date | Phase | Event |
|------|-------|-------|
| 2026-09-17 | — | Plan written and split into `plans/` — 12 phase files + overview. |
| 2026-09-17 | — | `PROGRESS.md` and `AGENT.md` created. No phase started yet. |
| 2026-09-17 | — | Phase files moved into `plans/phases/`. Links updated in `plans/README.md`, `PROGRESS.md`, `AGENT.md`, and all 12 phase files. |
| 2026-09-17 | 00 | started |
