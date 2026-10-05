---
name: erp-coding
description: >-
  Must read before writing, adding, refactoring, or reviewing backend and full-stack logic in KEREN ONE:
  controllers (app/Controllers/**), services (app/Services/**), helpers (app/Helpers/**), routing, API endpoints,
  and business rules. Enforces clean minimalist code, token efficiency, anti-bloat/anti-overengineering,
  mandatory reuse of existing helpers, Pure PHP 8.1+ patterns (not Laravel), atomic DB transactions, audit logging,
  and autonomous technical guardianship for AI-assisted development.
---

# Backend & Implementation Engineering Standards (Pragmatic & Minimalist)

> **Single Source of Truth (SSOT)**  
> All Agentic AIs and engineers must strictly adhere to these guidelines when authoring, modifying, or refactoring logic in the **KEREN ONE** ERP repository.
> 
> **Core Identity:**  
> KEREN ONE is a lightning-fast, high-performance, **Pure PHP 8.1+ Custom MVC** system. It is **NOT** Laravel, Symfony, or WordPress. There is no Eloquent, no Artisan, and no Blade template engine. Do not hallucinate external framework abstractions.

---

## 1. Developer Persona & Autonomous Guardianship

The repository owner operates as an **AI-Assisted Developer** focusing on business operations and system workflows rather than low-level framework plumbing or database theory.

As an AI Agent in this repository, you hold **Complete Autonomous Technical Responsibility**:
1. **No Cryptic Technical Quizzes**: Never ask the user to resolve SQL join strategies, foreign key cascades, transaction isolation levels, or low-level PHP syntax errors. Research the codebase, deduce the correct path, and implement the robust solution.
2. **Defensive & Self-Verifying**:
   - Check for null values, type consistency, and database constraints before committing code.
   - Verify every route, controller method, and helper call against the actual codebase.
3. **Zero Breakage Invariant**:
   - Never break existing working features or regressions across interconnected modules (e.g., inventory mutation affecting cost of goods or orders).
   - Any modification must leave the repository in a fully operational state.
4. **Clear, Transparent Explanations**:
   - Communicate what was done in plain, practical Indonesian or English without unnecessary academic jargon.

---

## 2. The Keren One Efficiency Ladder (Adapted Ponytail Protocol)

Always solve problems using the **highest applicable rung** on this ladder. Stop as soon as a rung solves the task cleanly:

```
[Rung 1] YAGNI & Scope Sanity (Does this need to exist?)
   ↓
[Rung 2] Reuse Existing Helpers & Services (Already in app/Helpers/?)
   ↓
[Rung 3] Native PHP 8.1+ & Standard Library (Can native PHP do it?)
   ↓
[Rung 4] Zero-New-Package Rule (Strictly use the 3 Composer libraries)
   ↓
[Rung 5] Shortest Working Diff (Cleanest, most readable, minimal code)
```

### Detailed Rung Execution:

1. **Rung 1: YAGNI (You Aren't Gonna Need It)**:
   - Reject speculative feature bloat, hypothetical future configurations, or premature abstractions.
   - Never create an `Interface` with only one implementation.
   - Never create a `Factory` for a single class.
   - Never create a custom DTO class when a typed associative array or standard object satisfies the need.

2. **Rung 2: Mandatory Reuse of Existing Helpers (`app/Helpers/`)**:
   Before writing any utility function, **ALWAYS** check `app/Helpers/`. Writing redundant helpers or reimplementing logic that already exists is strictly forbidden:
   - **`App\Helpers\Format`**: Rupiah currency (`Format::rupiah`), dates, numbers, phone numbers, unit conversions.
   - **`App\Helpers\DocumentNumber`**: Sequence generator for orders (`SO-`), delivery orders (`SJ-`), cash vouchers (`BKK-`/`BKM-`), purchase orders, etc.
   - **`App\Helpers\StockHelper`**: Stock balance verification, movements, and inventory integrity checks.
   - **`App\Helpers\CSRF`**: Token generation (`CSRF::tokenField()`) and strict verification (`CSRF::check()`).
   - **`App\Helpers\ActivityLog`**: Operational audit logging (`ActivityLog::log()`).
   - **`App\Helpers\CashVoucher`**: Cash in/out document creation and accounting ties.
   - **`App\Helpers\CompanySetting`**: Fetching dynamic tenant/company configurations.
   - **`App\Helpers\Upload`**: File upload processing, validation, MIME validation, and resizing.
   - **`App\Helpers\Flash`**: Flash messaging across redirects.
   - **`App\Helpers\PrintDocumentHelper`**, **`PdfExport`**, **`ExcelExport`**: Standard output and reporting engines.

3. **Rung 3: Leverage Modern PHP 8.1+ Idioms**:
   - Use `match` expressions instead of verbose `switch` blocks.
   - Use nullsafe operator (`?->`) and null coalescing (`??`).
   - Use `str_contains()`, `str_starts_with()`, `str_ends_with()`.
   - Use named arguments when they enhance clarity for multi-argument methods.
   - Use typed properties and strict type comparisons (`===`).

4. **Rung 4: Pragmatic Dependency Curation (YAGNI)**:
   - **Prinsip Utama**: Dilarang menambahkan dependensi pihak ketiga baru yang tidak diperlukan saat ini berdasarkan realita kode, database, dan kebutuhan esensial aplikasi.
   - Proyek saat ini mengandalkan 3 dependensi terpasang:
     1. `aws/aws-sdk-php` (Cloudflare R2 storage / object storage).
     2. `dompdf/dompdf` (PDF generation).
     3. `phpspreadsheet/phpspreadsheet` (Excel export & import).
   - Manfaatkan kapabilitas native PHP 8.1+ dan pustaka standar. Jangan menambahkan library eksternal untuk hal-hal sepele yang bisa ditulis dengan 10-30 baris PHP bersih.
   - Pengecualian: Jika di masa depan bisnis memerlukan integrasi resmi pihak ketiga (seperti Payment Gateway Midtrans/Xendit, WhatsApp API resmi, atau printer thermal), diskusikan dan pastikan ada persetujuan eksplisit dari owner sebelum menginstal.

5. **Rung 5: Shortest Working Diff (Root Cause Focus)**:
   - Smallest, most precise diff wins.
   - Always find and fix the **root cause** in shared controllers, models, or helpers rather than patching symptoms across 5 different views.
   - Deletion of dead code is strictly preferred over accumulating commented-out legacy code.

---

## 3. Non-Negotiable ERP Safety Guardrails

Efficiency **NEVER** overrides financial or data safety. The following guards are mandatory in all mutating business logic:

### 3.1. Atomic Database Transactions (No Partial Commits)
Whenever performing multiple updates, inventory deductions, balance transfers, or creating related child records:
```php
$db = \config\database::getConnection(); // PDO instance
$db->beginTransaction();
try {
    // 1. Verify preconditions & lock rows if necessary (e.g. SELECT ... FOR UPDATE)
    // 2. Perform state mutations (e.g. insert order, deduct stock, record payment)
    // 3. Log audit activity
    ActivityLog::log('MODUL_NAME', 'ACTION', "Description: #{$docNumber}");

    $db->commit();
} catch (\Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    // Handle error gracefully or log for developer diagnosis
    throw $e;
}
```

### 3.2. Mandatory Security, CSRF & RBAC Checks
Every mutating endpoint (`POST`, `PUT`, `DELETE`):
1. **CSRF Validation**:
   ```php
   \App\Helpers\CSRF::check();
   ```
2. **Permission Gate**:
   ```php
   \App\Core\Auth::requirePermission('permission_name');
   // Or role requirement:
   \App\Core\Auth::requireRole(['admin', 'owner']);
   ```
3. **Developer-Only Endpoints**:
   System tools, database synchronizations, and internal diagnostic scripts must enforce:
   ```php
   \App\Core\Auth::requireDeveloper();
   ```

### 3.3. Input Sanitization & Parameterized Queries
- **Never interpolate raw variables into SQL**:
  ```php
  // ❌ FORBIDDEN:
  $db->query("SELECT * FROM pelanggan WHERE id = " . $_GET['id']);

  // ✅ MANDATORY:
  $stmt = $db->prepare("SELECT * FROM public.pelanggan WHERE id = :id");
  $stmt->execute(['id' => $id]);
  ```
- Use strict type casting (`(int)`, `(float)`, `trim()`) for request payloads.

---

## 4. Architectural Rules: Pure PHP MVC Patterns

### 4.1. Controllers (`app/Controllers/`)
- Controllers handle request extraction, permission validation, orchestration, and rendering.
- Keep controllers focused. Heavy reusable business algorithms (such as complex commission schemes or database replication) belong in `app/Services/`.
- Return responses using standard JSON helper or View render:
  ```php
  // JSON API response:
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['success' => true, 'data' => $result]);
  exit;
  ```

### 4.2. Routing (`app/Core/Router.php`)
- Follow the established convention in `Router.php` when adding new endpoints.
- Group related URLs logically and maintain clear naming (e.g., `/inventory/stock-opname`, `/orders/create`).

### 4.3. View Rendering (`views/`)
- Views are pure PHP files using native alternative syntax (`<?php foreach (...): ?>`, `<?php if (...): ?>`).
- Ensure UI interactions comply with `erp-ui-design`: use Lucide icons, Inter/JetBrains Mono fonts, and standard modal structures.
- Escape all output rendered to the browser using `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')` or equivalent helpers to prevent XSS.

---

## 5. Pre-Implementation & Completion Checklist

Before considering any backend coding task complete, verify:

- [ ] **Helper Verification**: Did I check `app/Helpers/` before writing custom utilities?
- [ ] **Pure PHP Compliance**: Zero Laravel/Eloquent hallucinations.
- [ ] **Transaction Atomicity**: Are multiple mutating queries wrapped in `beginTransaction()` + `try/catch/rollBack()`?
- [ ] **Audit Trail**: Is `ActivityLog::log()` called for significant business events?
- [ ] **Security**: Are `CSRF::check()` and `Auth::require...` properly enforced on mutation?
- [ ] **Dual-Database Safety**: If this change requires schema updates, did I adhere to `erp-database` protocol for both Supabase Live and Local?
- [ ] **Minimalist & Clean**: Is there zero leftover dead/mock code, unneeded interfaces, or bloated boilerplate?
