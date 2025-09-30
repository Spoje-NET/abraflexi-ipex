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
- `ABRAFLEXI_MINIMAL_INVOICING`: do not create too low invoices (default is 50))
- `ABRAFLEXI_CREATE_EMPTY_ORDERS`: just be sure that month was processed
- `IPEX_URL`: URL of the Ipex API
- `IPEX_LOGIN`: Login username for Ipex
- `IPEX_PASSWORD`: Password for Ipex

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
01/17/2025 21:55:09 ⚙ ❲IPEXPostPaidInvoices⦒SpojeNet\AbraFlexiIpex\Ipex❳ IPEXPostPaidInvoices EaseCore 1.45.0 (PHP 8.2.27)
01/17/2025 21:55:10 ⚠ ❲IPEXPostPaidInvoices⦒SpojeNet\AbraFlexiIpex\Ipex❳ Ipex Customer Without externalId: code:01183
01/17/2025 21:55:10 ⚠ ❲IPEXPostPaidInvoices⦒SpojeNet\AbraFlexiIpex\Ipex❳ Ipex Customer Without externalId: code:03489
01/17/2025 21:55:10 ⚠ ❲IPEXPostPaidInvoices⦒SpojeNet\AbraFlexiIpex\Ipex❳ Unknown AbraFlexi customer. No invoice created.
01/17/2025 21:55:10 ⚠ ❲IPEXPostPaidInvoices⦒SpojeNet\AbraFlexiIpex\Ipex❳ Ipex Customer Without externalId: code:01846
01/17/2025 21:55:10 ⚠ ❲IPEXPostPaidInvoices⦒SpojeNet\AbraFlexiIpex\Ipex❳ Ipex Customer Without externalId: code:02509
{
    "code:01183": {
        "invoice": "Not an Ipex customer: code:01183 ?"
    },
    "code:03489": {
        "invoice": "Not an Ipex customer: code:03489 ?"
    },
    "nocustomer": [
        "OBP0022\/2025",
        "OBP0044\/2025",
        "OBP0122\/2025",
        "OBP0296\/2025"
    ],
    "code:01846": {
        "invoice": "Not an Ipex customer: code:01846 ?"
    },
    "code:02509": {
        "invoice": "Not an Ipex customer: code:02509 ?"
    }
}01/17/2025 21:55:10 🌼 ❲IPEXPostPaidInvoices⦒SpojeNet\AbraFlexiIpex\Ipex❳ Saving result to php://stdout
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
    sudo wget -O /etc/apt/trusted.gpg.d/vitexsoftware.gpg http://repo.vitexsoftware.cz/keyring.gpg
    sudo apt update
    sudo apt install abraflexi-ipex
```

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.
