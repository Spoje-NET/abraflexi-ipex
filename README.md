# abraflexi-ipex

Ipex ⛗ AbraFlexi integration

## Table of Contents

- [Introduction](#introduction)
- [Features](#features)
- [Installation](#debianubuntu-installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Contributing](#contributing)
- [License](#license)

## Introduction

The `abraflexi-ipex` project integrates [Ipex](https://www.ipex.cz/), a VoIP service, with [AbraFlexi](https://www.abra.eu/flexi/), an economic ERP system. The primary function of this integration is to generate invoices in AbraFlexi from Ipex VoIP calls.

## Features

- Generate AbraFlexi Orders from Ipex Calls & send Call List to customer.
- Generate AbraFlexi Invoices from AbraFlexi orders when amount reach treshold.
- Send pre-paid Call list to customer.

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
- `ABRAFLEXI_MINIMAL_INVOICING`: do not create cheap invoices
- `ABRAFLEXI_CREATE_EMPTY_ORDERS`: just be sure that month was processed
- `IPEX_URL`: URL of the Ipex API
- `IPEX_LOGIN`: Login username for Ipex
- `IPEX_PASSWORD`: Password for Ipex

## Usage

To use the `abraflexi-ipex` integration, run the following commands:

- For postpaid calls to generate orders and send call list to customer:

    ```sh
    abraflexi-ipex-postpaid-orders
    ```

- For previously saved orders to generate invoices:

    ```sh
    abraflexi-ipex-prepaid-invoices
    ```

Example output:

`
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

`

- To send prepaid call list to customer:

    ```sh
    abraflexi-ipex-prepaid
    ```

- For initial setup:

    ```sh
    abraflexi-ipex-setup
    ```

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
