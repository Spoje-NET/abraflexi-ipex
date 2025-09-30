# PHP 8.4+ Compatibility Guide

This document outlines the changes made to ensure full compatibility with PHP 8.4+ and proper handling of typed properties.

## Overview

With PHP 8.0+, typed properties must be explicitly initialized before they can be accessed. This application has been updated to handle this requirement properly while maintaining backward compatibility.

## Changes Made

### 1. Nullable Property Declarations

**Before:**
```php
public \DateTime $since;
public \DateTime $until;
```

**After:**
```php
public ?\DateTime $since = null;
public ?\DateTime $until = null;
```

### 2. Safe Property Access

All access to `$since` and `$until` properties now includes proper null checks:

```php
// Safe access pattern used throughout the codebase
if (isset($this->since, $this->until)) {
    // Safe to use both properties
    $period = $this->since->format('Y-m-d') . ' to ' . $this->until->format('Y-m-d');
}

// Safe single property access
if (isset($this->since)) {
    $startDate = clone $this->since;
}
```

### 3. Method-Specific Improvements

#### `createInvoice()` Method
- Added null checks before accessing period properties
- Graceful fallback for invoice descriptions when period is undefined
- Safe due date setting only when `$until` is available

#### `createOrder()` Method
- Uses invoice start date as fallback when `$this->since` is not set
- Maintains functionality for both period-aware and period-agnostic workflows

#### `getIpexInvoices()` Method
- Properly initializes `$since` and `$until` at method start
- Safe to access these properties throughout the method execution

## Usage Patterns

### 1. With Period Definition (VoIPPostpaidOrders.php)
```php
$ipexer = new Ipex();
$report = $ipexer->processIpexPostpaidOrders(
    $ipexer->getIpexInvoices(['monthOffset' => -2])
);
```
In this case, `getIpexInvoices()` initializes the period properties.

### 2. Without Period Definition (VoIPPostpaidInvoices.php)
```php
$ipexer = new Ipex();
$report = $ipexer->processIpexPostpaidInvoices();
```
In this case, methods handle null period properties gracefully.

## Benefits

1. **PHP 8.4+ Compatibility**: Full support for strict typed property requirements
2. **Backward Compatibility**: Existing workflows continue to function unchanged
3. **Error Prevention**: Eliminates "Typed property must not be accessed before initialization" errors
4. **Flexibility**: Supports both period-aware and period-agnostic processing modes

## Best Practices

When working with this codebase:

1. Always check `isset()` before accessing `$since` or `$until` properties
2. Provide fallback logic when period properties are not available
3. Use nullable types (`?Type`) for optional properties
4. Initialize properties with sensible defaults when possible

## Testing

The changes have been tested with:
- PHP 8.2.29 (production environment)
- PHP 8.4.x (development environment)
- Various workflow scenarios (with and without period definition)

All existing functionality remains intact while providing robust error handling for uninitialized properties.