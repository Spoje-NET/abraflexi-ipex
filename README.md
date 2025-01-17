# abraflexi-ipex

Ipex ⛗ AbraFlexi integration

## Table of Contents

- [Introduction](#introduction)
- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Contributing](#contributing)
- [License](#license)

## Introduction

The `abraflexi-ipex` project integrates [Ipex](https://www.ipex.cz/), a VoIP service, with [AbraFlexi](https://www.abra.eu/flexi/), an economic ERP system. The primary function of this integration is to generate invoices in AbraFlexi from Ipex VoIP calls.

## Features

- Synchronize data between Ipex and AbraFlexi
- Automated invoice generation
- Support for multiple document types
- Configurable logging options
- Secure authentication and data transfer

## Installation

To install the `abraflexi-ipex` integration, follow these steps:

1. Clone the repository:

    ```sh
    git clone https://github.com/Spoje-NET/abraflexi-ipex.git
    cd abraflexi-ipex
    ```

2. Install dependencies using Composer:

    ```sh
    composer install
    ```

3. Set up the environment configuration by copying the example file:

    ```sh
    cp .env.example .env
    ```

4. Edit the `.env` file to match your configuration:

    ```sh
    nano .env
    ```

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
- `IPEX_URL`: URL of the Ipex API
- `IPEX_LOGIN`: Login username for Ipex
- `IPEX_PASSWORD`: Password for Ipex

## Usage

To use the `abraflexi-ipex` integration, run the following commands:

- For postpaid integration:

    ```sh
    ./bin/abraflexi-ipex-postpaid
    ```

- For prepaid integration:

    ```sh
    ./bin/abraflexi-ipex-prepaid
    ```

- For setup:

    ```sh
    ./bin/abraflexi-ipex-setup
    ```

## Contributing

We welcome contributions to the `abraflexi-ipex` project. To contribute, please follow these steps:

1. Fork the repository.
2. Create a new branch for your feature or bugfix.
3. Make your changes and commit them with clear messages.
4. Push your changes to your fork.
5. Create a pull request to the main repository.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.

MultiFlexi
----------

AbraFlexi-Ipex is ready for run as [MultiFlexi](https://multiflexi.eu) application.
See the full list of ready-to-run applications within the MultiFlexi platform on the [application list page](https://www.multiflexi.eu/apps.php).

[![MultiFlexi App](https://github.com/VitexSoftware/MultiFlexi/blob/main/doc/multiflexi-app.svg)](https://www.multiflexi.eu/apps.php)

Debian/Ubuntu
-------------

For Linux, .deb packages are available. Please use the repo:

```shell
    echo "deb http://repo.vitexsoftware.com $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/vitexsoftware.list
    sudo wget -O /etc/apt/trusted.gpg.d/vitexsoftware.gpg http://repo.vitexsoftware.cz/keyring.gpg
    sudo apt update
    sudo apt install abraflexi-ipex
```
