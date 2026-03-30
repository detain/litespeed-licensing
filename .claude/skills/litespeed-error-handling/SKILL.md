---
name: litespeed-error-handling
description: Documents and applies the project's error pattern: validation returns `['error' => 'msg']`, failed cURL returns `false`, errors accumulate in `$this->error[]`. Use when adding new methods, handling return values, or debugging failed API calls. Do NOT use for adding exceptions — the project intentionally avoids them, and do NOT use for non-LiteSpeed API integrations.
---
# litespeed-error-handling

## Critical

- **Never throw exceptions.** This project uses array returns and `$this->error[]` accumulation only.
- **Never return `null`.** Failed cURL → `false`. Validation failure → `['error' => 'message']`.
- **`$this->error[]` is append-only** (`$this->error[] = $msg` or `array_merge`). Do not overwrite it.
- Validation must happen **before** setting `$this->params` and before calling `$this->req()`.

## Instructions

1. **Add input validation at the top of the method.** Check each constrained param against its `$this->valid*` list using `in_array()`. Return early on failure:
   ```php
   if (!in_array($product, $this->validProducts)) {
       return ['error' => 'Invalid Product'];
   }
   ```
   Verify: every constrained param has a guard before any `$this->params[...]` assignment.

2. **Set params and call `$this->req($action)`.** Return its result directly — do not wrap it:
   ```php
   $this->params['order_product'] = $product;
   return $this->req('Order');
   ```
   Verify: `resetParams()` is called in `__construct()`, not at the start of each method.

3. **Inside `req()`: handle cURL failure.** `curl_exec()` returns `false` on network error. Append to `$this->error[]` and return `false`:
   ```php
   $this->rawResponse = curl_exec($ch);
   if (!$this->rawResponse) {
       $error = 'There was some error in connecting to LiteSpeed. ...';
       $this->error[] = $error;
       return false;
   }
   ```
   Verify: `$this->error[]` uses `[]` append syntax, not `=` assignment.

4. **Inside `req()`: handle API-level errors from XML response.** After `xml2array()`, check `$this->response['error']`. On error, merge into `$this->error[]` and return `false`; on success, return `$this->response`:
   ```php
   $this->response = xml2array($this->rawResponse);
   if (empty($this->response['error'])) {
       unset($this->response['error']);
       return $this->response;
   } else {
       $this->error = array_merge($this->error, $this->response['error']);
       return false;
   }
   ```
   Verify: success path calls `unset($this->response['error'])` before returning.

5. **Log before and after the request** using `myadmin_log()`:
   ```php
   myadmin_log('licenses', 'info', "LiteSpeed URL: $url\npstring: $pstring\n", __LINE__, __FILE__);
   // ... curl_exec ...
   myadmin_log('licenses', 'info', 'LiteSpeed Response '.var_export($this->response, true), __LINE__, __FILE__);
   ```

6. **Callers must check the return value before using it:**
   ```php
   $result = $ls->order('LSWS', '1', 'monthly', 'credit');
   if ($result === false) {
       // inspect $ls->error[]
   } elseif (isset($result['error'])) {
       // validation failed before req() was called
   } else {
       // $result['LiteSpeed_eService']['result'] === 'success' | 'incomplete' | 'error'
   }
   ```

## Examples

**User says:** "Add a method to move a license to a new IP."

**Actions taken:**
1. No constrained enum params → no validation guards needed.
2. Set `$this->params`, call `$this->req('MoveLicense')`, return result.
3. Caller checks `=== false` and inspects `$this->error[]` on failure.

**Result:**
```php
public function moveLicense($serial, $newIp)
{
    $this->params['license_serial'] = $serial;
    $this->params['new_server_ip'] = $newIp;
    return $this->req('MoveLicense');
}

// Caller:
$result = $ls->moveLicense('gv06-kXsU-SHBr-pL4N', '1.2.3.4');
if ($result === false) {
    var_dump($ls->error);
}
```

## Common Issues

- **`$result` is `false` but `$ls->error` is empty:** cURL succeeded but `xml2array()` returned an unexpected structure — dump `$ls->rawResponse` to inspect the raw XML.
- **Validation guard never fires:** You compared against a hardcoded string instead of the `$this->valid*` list. Use `in_array($value, $this->validProducts)` not `$value === 'LSWS'`.
- **`$this->error[]` gets reset between calls:** `resetParams()` only clears `$this->params`, not `$this->error`. If you need a fresh error state, instantiate a new `LiteSpeed` object.
- **`req()` returns an array with `['error' => ...]` instead of `false`:** This means validation ran inside the calling method (not `req()`), which is correct — it short-circuits before `req()` is ever called. This is expected behavior for invalid input.