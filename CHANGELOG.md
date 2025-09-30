# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.1] - 2025-09-30

### Fixed
- **PHP 8.4+ Compatibility**: Fixed typed property initialization issues
  - Made `$since` and `$until` properties nullable (`?\DateTime`)
  - Added proper null checks before accessing these properties
  - Fixed "Typed property must not be accessed before initialization" errors
- **createInvoice Method**: Enhanced to handle cases where period properties are not set
  - Added `isset()` checks for `$this->since` and `$this->until`
  - Graceful fallback for invoice descriptions when period is not defined
  - Safe due date setting only when `$until` is available
- **createOrder Method**: Fixed order date setting with proper null checks
  - Uses invoice start date as fallback when `$this->since` is not set
  - Maintains backward compatibility with existing workflows

### Improved
- **Error Handling**: Better handling of uninitialized properties throughout the codebase
- **Documentation**: Updated class and method docblocks with comprehensive descriptions
- **Code Quality**: Enhanced PSR-12 compliance and type safety

## [1.2.0] - 2025-01-17

### Added
- **Enhanced Audit Reporting**: Comprehensive transaction tracking and detailed reporting
- **MultiFlexi Report Format**: Standardized reporting for MultiFlexi platform integration
- **MonthOffset Calculation**: Enhanced month offset logic with automatic sign correction
  - Positive values automatically converted to negative for past periods
  - Continue mode for automatic period calculation from last generated order
- **Timezone Conversion**: Proper UTC to local timezone conversion for accurate date filtering
  - IPEX API returns UTC timestamps, converted to Europe/Prague timezone
  - Accurate month matching for invoice filtering

### Enhanced
- **Command Line Interface**: Added `--continue` / `-c` option for automatic period detection
- **Date Processing**: Improved date handling with timezone awareness
- **API Integration**: Better IPEX API parameter handling with monthOffset support

### Fixed
- **Invoice Filtering**: Resolved empty filteredInvoices array issues
- **Month Calculation**: Fixed monthOffset sign logic for proper API parameter usage

## [1.1.0] - Previous Release

### Features
- IPEX to AbraFlexi integration
- Postpaid order generation
- Invoice creation from prepared orders
- Call list email notifications
- Prepaid call list processing
- MultiFlexi platform compatibility

### Configuration
- Environment-based configuration
- AbraFlexi and IPEX API integration
- Customizable invoicing thresholds
- Email notification settings