---
name: litespeed-api-method
description: Adds a new action method to `src/LiteSpeed.php` following the param-build → req() pattern. Sets params on `$this->params` with `rawurlencode()`, validates inputs against `$valid*` arrays, returns array or false. Use when user says 'add method', 'new action', 'implement endpoint', or names a LiteSpeed eService action. Do NOT use for modifying `req()` itself or changing transport/auth logic.
---
# litespeed-api-method

## Critical

- **Never** touch `req()`, `resetParams()`, `__construct()`, or `usePost()` — action methods only.
- **Never** throw exceptions — return `['error' => 'Message']` for validation failures; return `false` only comes from `req()` on curl/XML error.
- **Never** call `rawurlencode()` on params you set directly on `$this->params` for known-safe values (serial, IP, etc.) — but DO call `rawurlencode()` inside `req()` (already done for `eService_action`). Credential params in `resetParams()` already use `rawurlencode()`.
- Validate against `$this->validProducts`, `$this->validCpu`, `$this->validPeriod`, `$this->validPayment` before setting any params.
- Use tabs for indentation (`.scrutinizer.yml` enforces this).

## Instructions

1. **Read `src/LiteSpeed.php`** to confirm the action name and param names expected by the LiteSpeed eService API. Verify the method does not already exist before adding.

2. **Add the PHPDoc block** directly above the method. Follow the `@param` / `@return` style used by `cancel()` and `upgrade()`:
   ```php
   /**
    * One-line description of the action.
    *
    * @param mixed $serial  license serial number
    * @param mixed $ipAddress  server IP address
    * @return array|false  parsed XML response array, or false on curl/API error
    */
   ```

3. **Write the method signature** with `false` defaults for optional params (matches `suspend()`, `cancel()`, `upgrade()`):
   ```php
   public function myAction($serial = false, $ipAddress = false, $optionalParam = false)
   {
   ```

4. **Validate required enum inputs first**, before touching `$this->params`. Return early on failure:
   ```php
   if (!in_array($cpu, $this->validCpu)) {
       return ['error' => 'Invalid CPU'];
   }
   if (!in_array($payment, $this->validPayment)) {
       return ['error' => 'Invalid Payment Method'];
   }
   ```
   Verify all validation guards are in place before proceeding.

5. **Set params conditionally** for optional values (use the `!== false` guard), unconditionally for required ones:
   ```php
   // Required param — always set
   $this->params['license_serial'] = $serial;
   // Optional param — guard with !== false
   if ($ipAddress !== false) {
       $this->params['server_ip'] = $ipAddress;
   }
   if ($optionalParam !== false) {
       $this->params['my_param'] = $optionalParam;
   }
   ```
   Param key names must match the LiteSpeed eService API field names exactly (e.g. `license_serial`, `server_ip`, `reason`, `order_payment`, `upgrade_cpu`).

6. **Call `$this->req()`** as the final statement, passing the PascalCase action string that the API expects:
   ```php
   return $this->req('MyAction');
   ```
   Verify the action string matches the API spec (existing examples: `'Ping'`, `'Order'`, `'Cancel'`, `'ReleaseLicense'`, `'Suspend'`, `'Unsuspend'`, `'Upgrade'`, `'Query'`).

7. **Run tests** to confirm nothing is broken:
   ```bash
   vendor/bin/phpunit --bootstrap vendor/autoload.php tests/ -v
   ```

## Examples

**User says:** "Add a `transfer` method that moves a license to a new IP, taking serial and newIp as required params."

**Actions taken:**
1. Read `src/LiteSpeed.php` — confirm `transfer()` does not exist; identify API action name is `'Transfer'`, params are `license_serial` and `new_server_ip`.
2. Insert before the `usePost()` method:

```php
/**
 * Transfer a license to a new IP address.
 *
 * @param mixed $serial   license serial number
 * @param mixed $newIp    new server IP address
 * @return array|false  parsed XML response array, or false on curl/API error
 */
public function transfer($serial, $newIp)
{
	$this->params['license_serial'] = $serial;
	$this->params['new_server_ip'] = $newIp;
	return $this->req('Transfer');
}
```

3. Run `vendor/bin/phpunit --bootstrap vendor/autoload.php tests/ -v` — all tests pass.

**Result:** New method follows the same tab-indented, param-then-req pattern as `release()` and `cancel()`.

## Common Issues

- **`Call to undefined function xml2array()`** — `req()` calls `function_requirements('xml2array')` only from the constructor. If you instantiate the class outside MyAdmin (e.g. in a standalone test), ensure the helper is loaded. In tests, stub or mock `req()` rather than calling it directly.
- **Params from a previous call bleed into the new one** — `resetParams()` is called only in `__construct()`, not before each method. If you call two action methods on the same instance, the second call carries over params from the first. Create a fresh `LiteSpeed` instance per request, or call `$this->resetParams()` at the top of your new method if cross-contamination is a risk.
- **Validation silently skipped** — if you pass `false` (the default) for an enum param and forget to guard with `!== false` before the `in_array()` check, the validation will always fail and return `['error' => '...']`. Use `!== false` guard before any `in_array()` call on optional params (see `upgrade()` pattern for serial/IP vs. `order()` pattern for required enum params).
- **Wrong indentation causes `.scrutinizer.yml` failures** — all indentation must be tabs, not spaces. If your editor inserts spaces, the static-analysis run will flag style violations.