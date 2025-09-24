# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

`abraflexi-ipex` is a PHP integration project that connects Ipex VoIP service with AbraFlexi ERP system. The primary purpose is to generate AbraFlexi invoices and orders from Ipex VoIP call data.

### Core Integration Flow
1. **Postpaid Orders**: Fetch Ipex call data → Generate AbraFlexi orders → Send call lists to customers
2. **Invoice Generation**: Process existing AbraFlexi orders → Generate invoices when amounts reach threshold
3. **Prepaid Processing**: Send prepaid call lists to customers
4. **Audit Reporting**: Generate comprehensive transaction reports with duplicate prevention
5. **MultiFlexi Integration**: Schema-compliant reporting for standardized platform integration

## Development Commands

### Package Management
```bash
composer install         # Install dependencies
composer update          # Update dependencies
```

### Testing
```bash
make tests               # Run PHPUnit test suite
vendor/bin/phpunit tests # Direct PHPUnit execution
```

### Code Quality
```bash
make static-code-analysis              # Run PHPStan analysis
make static-code-analysis-baseline     # Generate PHPStan baseline
make cs                               # Fix code style with PHP-CS-Fixer
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php
```

### Application Commands
```bash
# Core application executables
abraflexi-ipex-postpaid-orders    # Generate orders from postpaid calls
abraflexi-ipex-postpaid-invoices  # Generate invoices from orders
abraflexi-ipex-prepaid            # Send prepaid call lists
abraflexi-ipex-setup              # Initial setup/configuration

# Enhanced features (v1.2.0+)
abraflexi-ipex-postpaid-orders -m -2                      # Process specific month
abraflexi-ipex-postpaid-orders -o multiflexi_report.json  # MultiFlexi format
MULTIFLEXI_REPORT_FORMAT=true abraflexi-ipex-postpaid-orders  # Force MultiFlexi format
```

## Architecture & Structure

### Core Classes
- **`SpojeNet\AbraFlexiIpex\Ipex`**: Main integration class handling VoIP data processing and AbraFlexi integration
- **`SpojeNet\AbraFlexiIpex\CallsListing`**: Handles call data formatting and PDF generation

### Key Dependencies
- **`spojenet/flexibee`**: AbraFlexi PHP client library
- **`spojenet/ipexb2b`**: Ipex API client
- **`vitexsoftware/ease-html`**: HTML/email utilities
- **`mpdf/mpdf`**: PDF generation for call reports

### Application Entry Points
Located in `bin/` directory:
- Each executable corresponds to a specific business process
- All executables delegate to PHP classes in `src/`

### Configuration
Environment variables defined in `.env.example`:
- **AbraFlexi Settings**: URL, credentials, company, order types, products
- **Ipex API**: URL and credentials  
- **Email Configuration**: From addresses, notification recipients
- **Processing Options**: Debug mode, minimal invoicing thresholds, skip lists

### MultiFlexi Integration
Project includes MultiFlexi application definitions in `multiflexi/`:
- JSON schema validation required (see rule about schema compliance)
- Each `.app.json` must conform to MultiFlexi application schema
- Validate with: `multiflexi-cli application validate-json --json multiflexi/[filename].app.json`

### Debian Packaging
Full Debian packaging support in `debian/` directory with:
- Jenkins CI/CD pipelines
- Package installation and configuration scripts
- Repository distribution via VitexSoftware APT repository

## Testing Strategy

- **PHPUnit Configuration**: `phpunit.xml` with coverage enabled
- **Test Location**: `tests/` directory mirrors `src/` structure  
- **Coverage**: Source directory (`src/`) is included in coverage analysis
- Many test methods currently marked as incomplete (`@todo Complete`)

## Quality Assurance

- **PHPStan**: Static analysis with memory limit disabled for large codebases
- **PHP-CS-Fixer**: Code style enforcement with custom configuration
- **Composer Normalize**: Maintains consistent `composer.json` formatting

## Integration Points

### AbraFlexi Integration
- Customer matching via external IDs
- Order creation with IPEX_POSTPAID product codes
- Invoice generation with configurable minimum thresholds
- Document type and order type management

### Ipex API Integration  
- Postpaid invoice data retrieval with date ranges
- Customer list synchronization
- Call detail processing for billing

### Email Notifications
- Order confirmation emails to customers
- Administrative notifications for processing results
- PDF call reports attached to emails
