---
name: litespeed-phpunit-test
description: Creates PHPUnit tests in `tests/` for `Detain\LiteSpeed\LiteSpeed` bootstrapped via `vendor/autoload.php`. Covers input validation (returns `['error'=>...]`) and response parsing by partially mocking `req()` with Mockery. Use when user says 'write test', 'add test', 'test method', or 'coverage'. Do NOT use for integration tests that hit the live LiteSpeed API at store.litespeedtech.com.
---
# litespeed-phpunit-test

## Critical

- **Never** call the real `req()` method in unit tests — it fires live cURL to `store.litespeedtech.com`.
- `LiteSpeed::__construct()` calls `function_requirements('xml2array')` and `myadmin_log()` — these global functions **must** be stubbed before any `new LiteSpeed(...)` call or PHP will fatal.
- Validation methods (`order`, `upgrade`) return `['error' => 'message']` arrays — they do **not** throw exceptions. Assert `assertArrayHasKey('error', $result)`, never `expectException()`.
- Run suite with: `vendor/bin/phpunit --bootstrap vendor/autoload.php tests/ -v`

## Instructions

1. **Create `tests/bootstrap.php`** to stub the three globals the constructor needs. This file must be referenced via `--bootstrap vendor/autoload.php` — add a `require` at the top of your test file instead of modifying the CLI bootstrap:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

if (!function_exists('function_requirements')) {
	function function_requirements(string $func): void {}
}
if (!function_exists('myadmin_log')) {
	function myadmin_log(string $module, string $level, string $msg, int $line, string $file): void {}
}
if (!function_exists('xml2array')) {
	function xml2array(string $xml): array { return []; }
}
```

   Verify `tests/bootstrap.php` exists before adding test classes.

2. **Create `tests/LiteSpeedTest.php`** using this exact structure (namespace, use statements, setUp pattern):

```php
<?php
require_once __DIR__ . '/bootstrap.php';

use Detain\LiteSpeed\LiteSpeed;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class LiteSpeedTest extends TestCase
{
	use MockeryPHPUnitIntegration;

	protected $login    = 'testuser';
	protected $password = 'testpass';

	// Partial mock: stubs only req(), real validation logic runs
	protected function makeMock(array $reqReturn = []): LiteSpeed
	{
		$mock = Mockery::mock(LiteSpeed::class . '[req]', [$this->login, $this->password]);
		$mock->shouldReceive('req')->andReturn($reqReturn);
		return $mock;
	}
}
```

   Verify the class extends `TestCase` and uses `MockeryPHPUnitIntegration` before adding test methods.

3. **Write validation tests** (no mock needed — early returns happen before `req()`):

```php
public function testOrderRejectsInvalidProduct(): void
{
	$ls = new LiteSpeed($this->login, $this->password);
	$result = $ls->order('INVALID', '1', 'monthly', 'credit');
	$this->assertArrayHasKey('error', $result);
	$this->assertSame('Invalid Product', $result['error']);
}

public function testOrderRejectsInvalidCpu(): void
{
	$ls = new LiteSpeed($this->login, $this->password);
	$result = $ls->order('LSWS', '99', 'monthly', 'credit');
	$this->assertArrayHasKey('error', $result);
	$this->assertSame('Invalid CPU', $result['error']);
}

public function testUpgradeRejectsInvalidPayment(): void
{
	$ls = new LiteSpeed($this->login, $this->password);
	$result = $ls->upgrade('SER-IAL', '1.2.3.4', '1', 'bitcoin');
	$this->assertArrayHasKey('error', $result);
	$this->assertSame('Invalid Payment Method', $result['error']);
}
```

4. **Write response-parsing tests** using `makeMock()` (Step 2 output):

```php
public function testOrderSuccessReturnsResponseArray(): void
{
	$expected = ['LiteSpeed_eService' => ['action' => 'Order', 'result' => 'success', 'license_serial' => 'ABC-123']];
	$ls = $this->makeMock($expected);
	$result = $ls->order('LSWS', '1', 'monthly', 'credit');
	$this->assertSame($expected, $result);
}

public function testPingCallsReqWithPing(): void
{
	$mock = Mockery::mock(LiteSpeed::class . '[req]', [$this->login, $this->password]);
	$mock->shouldReceive('req')->once()->with('Ping')->andReturn(['LiteSpeed_eService' => ['result' => 'success']]);
	$mock->ping();
}
```

5. **Run and verify**: `vendor/bin/phpunit --bootstrap vendor/autoload.php tests/ -v`. All tests must pass before submitting.

## Examples

**User says:** "Write tests for the `cancel` method"

**Actions:**
1. Ensure `tests/bootstrap.php` exists with stubs from Step 1.
2. Add to `LiteSpeedTest`: a test that calls `cancel('SER-IAL', '1.2.3.4', 'Y', 'nonpayment')` on `makeMock(['LiteSpeed_eService' => ['result' => 'success']])` and asserts the returned array matches.
3. Verify `$mock->shouldReceive('req')->once()->with('Cancel')` is satisfied.

**Result:** Test passes; `cancel()` is confirmed to delegate to `req('Cancel')` with no early-exit validation.

## Common Issues

- **`Call to undefined function function_requirements()`**: Your test file is not loading `tests/bootstrap.php`. Add `require_once __DIR__ . '/bootstrap.php';` as the first line of the test file.
- **`Class 'Mockery' not found`**: Run `composer install` — `mockery/mockery` is in `require-dev` and must be installed.
- **Mockery `shouldReceive` not verified / test passes when it should fail**: Missing `use MockeryPHPUnitIntegration;` in the test class — Mockery expectations are only enforced on teardown when this trait is present.
- **`req()` is called for real during validation tests**: You used `makeMock()` for a path that returns `['error'=>...]` before reaching `req()`. Use `new LiteSpeed(...)` directly for validation-only tests (Steps 3).
- **`Undefined index: error` in `req()`**: Your stubbed `xml2array` returns `[]` but production code checks `empty($this->response['error'])`. The stub is correct — this only surfaces if you bypass the mock and hit `req()` for real.