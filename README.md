# abraflexi-ipex

Ipex ⛗ AbraFlexi integration

<p align="center">
    <img src="multiflexi/262dabf1-d7b1-42c8-91a1-fe991631547c.svg?raw=true" alt="Postpaid IPEX to AbraFlexi Invoices" width="120" />
    <img src="multiflexi/51b247aa-fd48-44d7-8494-8c86b05e5a66.svg?raw=true" alt="Postpaid IPEX to AbraFlexi Orders" width="120" />
    <img src="multiflexi/74816157-8540-49c7-bfbd-423e8ed4fbfd.svg?raw=true" alt="Prepaid IPEX to AbraFlexi" width="120" />
</p>

## Table of Contents

- [Introduction](#introduction)  
- [Features](#features)
- [Technical Requirements](#technical-requirements)
- [Installation](#debianubuntu-installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Documentation](#documentation)
- [Contributing](#contributing)
- [License](#license)

## Introduction

The `abraflexi-ipex` project integrates [Ipex](https://www.ipex.cz/), a VoIP service, with [AbraFlexi](https://www.abra.eu/flexi/), an economic ERP system. The primary function of this integration is to generate invoices in AbraFlexi from Ipex VoIP calls.

## Features

- **Generate AbraFlexi Orders** from Ipex Calls & send Call List to customer
- **Generate AbraFlexi Invoices** from AbraFlexi orders when amount reach threshold
- **Send pre-paid Call list** to customer
- **Enhanced Audit Reporting** with comprehensive transaction tracking
- **MultiFlexi Report Schema** compliance for standardized integration
- **Duplicate Prevention** for orders and invoices
- **Any-time Execution** with proper period-specific processing
- **Detailed Metrics** and status reporting for compliance

> Czech documentation for the invoicing threshold logic is available in `README.cs.md`.

### New in Version 1.2.1

- **PHP 8.4+ Compatibility**: Fixed typed property initialization — `$since` / `$until` are now nullable
- **`createOrder` fix**: `datObj` falls back to `dateStart` when the IPEX payload lacks a `datetime` key, preventing a fatal AbraFlexi "null must be a date" error for new customers

### New in Version 1.2.0

- **[Enhanced Audit Reporting](AUDIT_REPORTS.md)**: Comprehensive transaction tracking and detailed reporting
- **[MultiFlexi Report Format](MULTIFLEXI_REPORTS.md)**: Standardized reporting for MultiFlexi platform integration
- **PHP 8.4+ Compatibility**: Full support for PHP 8.4 with proper typed property handling
- **Improved Error Handling**: Better handling of uninitialized properties and edge cases
- **MonthOffset Calculation**: Enhanced month offset logic with automatic sign correction for past periods
- **Timezone Conversion**: Proper UTC to local timezone conversion for accurate date filtering

## Technical Requirements

- **PHP**: 8.2 or later (tested with PHP 8.4)
- **AbraFlexi**: Compatible with current AbraFlexi versions
- **IPEX API**: B2B API access required
- **Extensions**: PHP extensions for HTTP clients and JSON processing

### PHP 8.4+ Compatibility

This application is fully compatible with PHP 8.4 and includes:
- Proper typed property initialization
- Nullable property handling for optional date ranges
- Enhanced error handling for uninitialized properties
- PSR-12 coding standard compliance

## Configuration

The [.env](.env.example) file contains the necessary configuration for the integration. Below are the key environment variables:

- `APP_DEBUG`: Enable or disable debug mode (true/false)
- `EASE_LOGGER`: Logger type (e.g., syslog|console)
- `EMAIL_FROM`: Email address for sending emails
- `DIGEST_FROM`: Email address for sending digests
- `SEND_INFO_TO`: Email address to send information
- `ABRAFLEXI_URL`: URL of the AbraFlexi instance
- `ABRAFLEXI_LOGIN`: Login username for AbraFlexi
- `ABRAFLEXI_PASSWORD`: Password for AbraFlexi
- `ABRAFLEXI_COMPANY`: Company identifier in AbraFlexi
- `ABRAFLEXI_ORDERTYPE`: Order type code in AbraFlexi
- `ABRAFLEXI_PRODUCT`: Product code in AbraFlexi
- `ABRAFLEXI_DOCTYPE`: Document type code in AbraFlexi
- `ABRAFLEXI_SKIPLIST`: List of items to skip during synchronization
- `ABRAFLEXI_MINIMAL_INVOICING`: Minimum invoice amount threshold — invoices below this value are skipped (default: `50`)
- `ABRAFLEXI_CREATE_EMPTY_ORDERS`: Create orders even when the billed amount is zero, to confirm the month was processed (default: `true`)
- `IPEX_URL`: URL of the Ipex API
- `IPEX_LOGIN`: Login username for Ipex
- `IPEX_PASSWORD`: Password for Ipex
- `ATTACH_CALL_LIST_PDF`: Attach a PDF call list to the created order (default: `true`)
- `SEND_CALL_LIST_EMAIL`: Send the call list PDF to the customer by email (default: `true`)
- `MULTIFLEXI_JOB_ID`: When set, appends the MultiFlexi job ID to order notes for traceability

### IPEX Midnight Boundary Behavior

1. IPEX billing periods can start at the last day of a month at `00:00`.
2. Typical API example: `31.12.2025 00:00`.
3. For monthly processing in this integration, this timestamp is treated as the next day boundary, i.e. `1.1.2026`.
4. Therefore, during order generation, `datTermin` is stored as `dateStart + 1 day` to avoid off-by-one-day period shifts when querying from `00:00`.

### Exact Mechanism for Zero-Amount Orders

1. When `ABRAFLEXI_CREATE_EMPTY_ORDERS=true`, an order is created even for months with `0 CZK` amount.
2. That order stays in `stavDoklObch.pripraveno` like other monthly orders.
3. Invoicing is evaluated against the sum of `sumCelkem` from all prepared customer orders.
4. Until the sum exceeds `ABRAFLEXI_MINIMAL_INVOICING`, no invoice is created and all orders (including zero-amount ones) remain prepared.
5. Once the threshold is exceeded, the resulting invoice is built from the prepared orders for that customer, including months that had `0 CZK` orders.
6. After invoice creation, source orders are marked as `stavDoklObch.hotovo`.

Note: If `ABRAFLEXI_CREATE_EMPTY_ORDERS=false`, zero-amount orders are not created, so they cannot be included in a future invoice.

## Usage

To use the `abraflexi-ipex` integration, run the following commands:

- For postpaid calls to generate orders and send call list to customer:

    ```sh
    abraflexi-ipex-postpaid-orders
    
    # Process specific month (any time execution)
    abraflexi-ipex-postpaid-orders -m -2  # Process 2 months ago
    
    # Continue mode - automatically calculate next period from last generated order
    abraflexi-ipex-postpaid-orders --continue
    abraflexi-ipex-postpaid-orders -c  # Short form
    
    # Generate MultiFlexi-compliant report
    abraflexi-ipex-postpaid-orders -o multiflexi_orders_report.json
    ```

### Command Line Options

- `-m, --monthOffset`: Specify the number of months back to process (always negative for past months)
- `-c, --continue`: Automatically calculate the next period based on the last generated order
- `-o, --output`: Specify output file for reports (default: stdout)
- `-e, --environment`: Specify custom environment file path

- For previously saved orders to generate invoices:

    ```sh
    abraflexi-ipex-postpaid-invoices
    
    # Generate MultiFlexi-compliant report
    abraflexi-ipex-postpaid-invoices -o multiflexi_invoices_report.json
    ```

Example output:

```
04/15/2026 08:00:01 ⚙ ❲IPEXPostPaidOrders⦒SpojeNet\AbraFlexiIpex\Ipex❳ IPEXPostPaidOrders EaseCore v1.50.1 (PHP 8.4.16)
04/15/2026 08:00:05 🌼 ❲IPEXPostPaidOrders⦒AbraFlexi\ObjednavkaPrijata❳ #1/34 Soukromá základní škola Cesta k úspěchu 9.64 CZK
04/15/2026 08:00:05 ℹ ❲IPEXPostPaidOrders⦒SpojeNet\AbraFlexiIpex\Ipex❳ PDF call list generation skipped (not needed for attachment or email)
...
```

- To send prepaid call list to customer:

    ```sh
    abraflexi-ipex-prepaid
    ```

- For initial setup:

    ```sh
    abraflexi-ipex-setup
    ```

## Documentation

Additional documentation is available:

- **[CHANGELOG.md](CHANGELOG.md)**: Detailed changelog with version history and changes
- **[PHP84_COMPATIBILITY.md](PHP84_COMPATIBILITY.md)**: Technical guide for PHP 8.4+ compatibility and typed properties
- **[AUDIT_REPORTS.md](AUDIT_REPORTS.md)**: Enhanced audit reporting documentation
- **[MULTIFLEXI_REPORTS.md](MULTIFLEXI_REPORTS.md)**: MultiFlexi report format specification

## Contributing

We welcome contributions to the `abraflexi-ipex` project. To contribute, please follow these steps:

1. Fork the repository.
2. Create a new branch for your feature or bugfix.
3. Make your changes and commit them with clear messages.
4. Push your changes to your fork.
5. Create a pull request to the main repository.

## MultiFlexi

AbraFlexi-Ipex is ready for run as [MultiFlexi](https://multiflexi.eu) application.
See the full list of ready-to-run applications within the MultiFlexi platform on the [application list page](https://www.multiflexi.eu/apps.php).

[![MultiFlexi App](https://github.com/VitexSoftware/MultiFlexi/blob/main/doc/multiflexi-app.svg)](https://www.multiflexi.eu/apps.php)

## Debian/Ubuntu Installation

For Linux, .deb packages are available. Please use the repo:

```shell
    echo "deb http://repo.vitexsoftware.com $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/vitexsoftware.list
    sudo wget -O /etc/apt/trusted.gpg.d/vitexsoftware.gpg http://repo.vitexsoftware.cz/KEY.gpg
    sudo apt update
    sudo apt install abraflexi-ipex
```

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.
