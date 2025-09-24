# MultiFlexi Report Format

The abraflexi-ipex integration now supports the official MultiFlexi report schema for standardized application reporting.

## Schema Compliance

Reports conform to the MultiFlexi Application Report Schema available at:
https://raw.githubusercontent.com/VitexSoftware/php-vitexsoftware-multiflexi-core/refs/heads/main/multiflexi.report.schema.json

## Activation

MultiFlexi-compliant reports are generated automatically when:

1. **MULTIFLEXI_REPORT_FORMAT** environment variable is set to `true`
2. **Output filename contains "multiflexi"** (e.g., `multiflexi_report.json`)
3. **MULTIFLEXI environment variable** is present (set by MultiFlexi platform)

## Usage Examples

### Command Line
```bash
# Generate MultiFlexi report with explicit environment variable
MULTIFLEXI_REPORT_FORMAT=true abraflexi-ipex-postpaid-orders

# Generate MultiFlexi report with filename detection
abraflexi-ipex-postpaid-orders -o multiflexi_orders_report.json

# Generate standard detailed report (default)
abraflexi-ipex-postpaid-orders -o orders_report.json
```

### Environment Configuration
```bash
export MULTIFLEXI_REPORT_FORMAT=true
export RESULT_FILE="multiflexi_ipex_orders.json"
```

## Report Structure

### Orders Report Example

```json
{
    "status": "warning",
    "timestamp": "2025-09-24T11:15:30+00:00",
    "message": "Processed 25 IPEX invoices for period 2025-08-01 to 2025-08-31. Created 20 orders (3456.78 CZK), skipped 3, found 2 duplicates.",
    "artifacts": {
        "orders": [
            "https://abraflexi.example.com/api/objednavka-prijata/123",
            "https://abraflexi.example.com/api/objednavka-prijata/124",
            "https://abraflexi.example.com/api/objednavka-prijata/125"
        ]
    },
    "metrics": {
        "exit_code": 0,
        "processed_count": 25,
        "created_count": 20,
        "skipped_count": 3,
        "duplicate_count": 2,
        "total_amount": 3456.78,
        "period_from": "2025-08-01",
        "period_to": "2025-08-31"
    }
}
```

### Invoices Report Example

```json
{
    "status": "success",
    "timestamp": "2025-09-24T11:20:45+00:00", 
    "message": "Processed 15 customers for invoicing. Created 8 invoices (12345.67 CZK), 5 below limit (234.56 CZK), 1 skipped, 1 duplicates.",
    "artifacts": {
        "invoices": [
            "https://abraflexi.example.com/api/faktura-vydana/456",
            "https://abraflexi.example.com/api/faktura-vydana/457",
            "https://abraflexi.example.com/api/faktura-vydana/458"
        ]
    },
    "metrics": {
        "exit_code": 0,
        "processed_count": 15,
        "created_count": 8,
        "skipped_count": 0,
        "duplicate_count": 1,
        "total_invoiced_amount": 12345.67,
        "total_below_limit_amount": 234.56,
        "below_limit_count": 5,
        "skip_list_count": 1,
        "no_customer_count": 0,
        "invoicing_limit": 50.0,
        "period_from": "2025-08-01",
        "period_to": "2025-08-31"
    }
}
```

## Schema Fields

### Required Fields

- **status**: `"success" | "error" | "warning"` - Overall result status
- **timestamp**: ISO8601 timestamp of completion

### Optional Fields

- **message**: Human-readable summary of the operation
- **artifacts**: Object containing arrays of created resource URLs
- **metrics**: Detailed numerical metrics about the operation

## Status Determination

- **success**: All operations completed successfully with no issues
- **warning**: Operations completed but with some skipped items or duplicates
- **error**: Critical failure occurred (non-zero exit code)

## Artifacts

The artifacts section contains URLs to the resources created by the application:

- **orders**: Array of AbraFlexi order URLs 
- **invoices**: Array of AbraFlexi invoice URLs

These URLs can be used by MultiFlexi or other systems to access the created documents directly.

## Metrics

Comprehensive numerical data about the processing:

### Common Metrics
- `exit_code`: Application exit code
- `processed_count`: Total items processed
- `created_count`: Successfully created items
- `skipped_count`: Items skipped  
- `duplicate_count`: Duplicate items found
- `period_from`/`period_to`: Date range processed

### Order-Specific Metrics
- `total_amount`: Total value of created orders (CZK)

### Invoice-Specific Metrics
- `total_invoiced_amount`: Total value of created invoices (CZK)
- `total_below_limit_amount`: Total value below invoicing limit (CZK)
- `below_limit_count`: Number of customers below invoicing limit
- `skip_list_count`: Number of customers in skip list
- `no_customer_count`: Number of orders without valid customers
- `invoicing_limit`: Current invoicing threshold

## MultiFlexi Integration

When running within MultiFlexi platform:

1. **MULTIFLEXI** environment variable is automatically set
2. **RESULT_FILE** points to appropriate report location
3. Reports are automatically consumed by MultiFlexi for dashboard display
4. Artifacts are linked to the job execution for easy access

## Backward Compatibility

The system maintains full backward compatibility:

- Default behavior generates detailed audit reports
- MultiFlexi format is only used when explicitly requested
- All existing command line options and environment variables work unchanged
- Standard audit reports remain available for debugging and detailed analysis