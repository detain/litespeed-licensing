# LiteSpeed Licensing

PHP client library for the LiteSpeed eService licensing API. Single class: `Detain\LiteSpeed\LiteSpeed` in `src/LiteSpeed.php`.

## Commands

```bash
composer install                          # install deps including phpunit
vendor/bin/phpunit --bootstrap vendor/autoload.php tests/ -v   # run tests
vendor/bin/phpunit --bootstrap vendor/autoload.php tests/ -v --coverage-clover coverage.xml --whitelist src/  # with coverage
```

## Architecture

- **Entry**: `src/LiteSpeed.php` · namespace `Detain\LiteSpeed` · PSR-4 via `composer.json`
- **API target**: `https://store.litespeedtech.com/reseller/LiteSpeed_eService.php`
- **Transport**: cURL POST (default) or GET via `$this->usePost` flag
- **Response**: raw XML → `xml2array()` → `$this->response` array
- **Logging**: `myadmin_log('licenses', 'info', $msg, __LINE__, __FILE__)`
- **Optional stats**: `\StatisticClient` (workerman) loaded conditionally in `req()`

## Class API

| Method | Params | Notes |
|--------|--------|---------|
| `ping()` | — | health check |
| `order($product, $cpu, $period, $payment)` | validates against `$validProducts`, `$validCpu`, `$validPeriod`, `$validPayment` | |
| `cancel($serial, $ip, $now, $reason)` | | |
| `release($serial, $ip)` | | |
| `suspend($serial, $ip, $reason)` | | |
| `unsuspend($serial, $ip, $reason)` | | |
| `upgrade($serial, $ip, $cpu, $payment)` | | |
| `query($field)` | | |
| `req($action)` | internal — builds params, fires cURL, parses XML | |

## Conventions

- All params encoded with `rawurlencode()` before adding to `$this->params`
- Call `$this->resetParams()` is done in constructor; methods add to `$this->params` then call `$this->req($action)`
- Validation returns `['error' => 'message']` array — not exceptions
- `$this->error[]` accumulates errors from failed responses
- Valid products: `['LSWS', 'LSLB']` · CPU: `['1','2','4','8','V','U']` · period: `['monthly','yearly','owned']` · payment: `['credit','creditcard']`
- Tabs for indentation (enforced by `.scrutinizer.yml`)
- camelCase for properties and parameters
- `displayResponse()` debug helper — outputs JSON-encoded response as `<pre>`

## CI / Analysis

- `.travis.yml` — PHP 5.4–7.1 matrix; coverage only on 7.0 via `phpdbg`
- `.scrutinizer.yml` — static analysis + coverage upload; excludes `tests/*`
- `.codeclimate.yml` — duplication + phpmd analysis on `**.php`
- `.bettercodehub.yml` — PHP language hint

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->
