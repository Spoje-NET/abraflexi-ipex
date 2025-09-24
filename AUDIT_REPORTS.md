# Enhanced Audit Reporting

The abraflexi-ipex integration now provides comprehensive audit reporting for all created orders and invoices with detailed amounts and tracking information.

## Features

- **Complete Transaction Tracking**: Every order and invoice creation is tracked with full details
- **Duplicate Prevention Reporting**: Identifies and reports attempts to create duplicate records
- **Amount Summaries**: Total amounts processed, created, and skipped
- **Period Tracking**: Shows which time period was processed
- **Customer Information**: Full customer details including names and codes
- **Status Categorization**: Clear status for each processed item

## Report Structure

### Orders Report

```json
{
    "customer_ext_id": {
        "customerName": "Customer Name",
        "ipexCustomerId": "123",
        "price": 150.50,
        "dateStart": "2025-08-01",
        "dateEnd": "2025-08-31",
        "status": "created|duplicate|skipped",
        "order": "OBP0123/2025",
        "amount": 150.50,
        "orderUrl": "https://abraflexi.example.com/api/objednavka-prijata/123",
        "createdAt": "2025-09-24 10:45:00"
    },
    "_audit": {
        "summary": {
            "processedCount": 25,
            "createdCount": 20,
            "skippedCount": 3,
            "duplicateCount": 2,
            "totalAmount": 3456.78,
            "processedPeriod": {
                "from": "2025-08-01",
                "to": "2025-08-31"
            },
            "processedAt": "2025-09-24 10:45:00"
        },
        "createdOrders": [
            {
                "orderCode": "OBP0123/2025",
                "customerExtId": "CUST001",
                "customerName": "Example Customer",
                "amount": 150.50,
                "price": 150.50,
                "period": "2025-08-01 to 2025-08-31",
                "orderUrl": "https://abraflexi.example.com/api/objednavka-prijata/123",
                "createdAt": "2025-09-24 10:45:00"
            }
        ],
        "skippedOrders": [...],
        "duplicateOrders": [...]
    }
}
```

### Invoices Report

```json
{
    "customer_ext_id": {
        "customerCode": "CUST001",
        "customerName": "Customer Name",
        "orderCount": 3,
        "uninvoicedAmount": 456.78,
        "status": "created|duplicate|below_limit|skipped_skiplist",
        "invoice": "FAK0123/2025",
        "invoiceAmount": 456.78,
        "invoiceUrl": "https://abraflexi.example.com/api/faktura-vydana/123",
        "createdAt": "2025-09-24 10:45:00"
    },
    "_audit": {
        "summary": {
            "processedCount": 15,
            "createdCount": 8,
            "belowLimitCount": 5,
            "skipListCount": 1,
            "duplicateCount": 1,
            "noCustomerCount": 0,
            "totalInvoicedAmount": 12345.67,
            "totalBelowLimitAmount": 234.56,
            "invoicingLimit": 50.0,
            "processedPeriod": {
                "from": "2025-08-01",
                "to": "2025-08-31"
            },
            "processedAt": "2025-09-24 10:45:00"
        },
        "createdInvoices": [
            {
                "invoiceCode": "FAK0123/2025",
                "customerCode": "CUST001",
                "customerName": "Example Customer",
                "amount": 456.78,
                "orderCount": 3,
                "orderCodes": ["OBP0123/2025", "OBP0124/2025", "OBP0125/2025"],
                "period": "2025-08-01 to 2025-08-31",
                "invoiceUrl": "https://abraflexi.example.com/api/faktura-vydana/123",
                "createdAt": "2025-09-24 10:45:00"
            }
        ],
        "belowLimitInvoices": [...],
        "skippedInvoices": [...],
        "duplicateInvoices": [...],
        "noCustomerOrders": [...]
    }
}
```

## Status Types

### Orders
- **created**: Order was successfully created
- **duplicate**: Order already exists for this period
- **skipped**: No calls or zero amount

### Invoices
- **created**: Invoice was successfully created
- **duplicate**: Invoice already exists for this period
- **below_limit**: Amount below invoicing threshold
- **skipped_skiplist**: Customer is in skip list
- **no_customer**: Customer code could not be resolved
- **not_ipex_customer**: Customer not found in IPEX
- **failed**: Invoice creation failed

## Usage

### Console Output
When `APP_DEBUG=true` or `EASE_LOGGER=console` is set, audit summaries are displayed:

```
=== AUDIT SUMMARY FOR ORDERS ===
Processed: 25 items
Created: 20 orders (3456.78 CZK)
Skipped: 3 orders
Duplicates: 2 orders
--- CREATED ORDERS ---
OBP0123/2025: Example Customer (150.50 CZK) - Period: 2025-08-01 to 2025-08-31
...
Period: 2025-08-01 to 2025-08-31
Processed at: 2025-09-24 10:45:00
=== END AUDIT SUMMARY ===
```

### JSON Output
Full audit details are always included in the JSON output under the `_audit` key, providing complete traceability for compliance and auditing purposes.

## Benefits

1. **Full Traceability**: Every transaction is tracked with timestamps and amounts
2. **Duplicate Prevention**: Prevents accidental duplicate processing
3. **Compliance**: Detailed records for auditing and compliance requirements
4. **Debugging**: Easy identification of issues and processing status
5. **Financial Control**: Complete tracking of all monetary amounts
6. **Period Management**: Clear identification of which periods have been processed

## Backward Compatibility

The enhanced reports maintain backward compatibility with existing integrations while adding the new `_audit` section with detailed information.