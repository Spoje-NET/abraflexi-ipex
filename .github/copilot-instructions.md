# GitHub Copilot Instructions for AbraFlexi-IPEX

<!-- Custom workspace instructions for GitHub Copilot -->
<!-- Learn more: https://docs.github.com/en/copilot/using-github-copilot/prompt-engineering-for-github-copilot -->

## 📋 Project Overview
This is a PHP-based MultiFlexi application that integrates IPEX VoIP services with AbraFlexi accounting system. The application processes call data, generates invoices, and manages customer billing.

## 🔧 Technical Standards

### Language & Framework Requirements
- **PHP Version**: 8.4+ (use modern PHP features like readonly properties, enums, etc.)
- **Coding Standard**: PSR-12 compliance mandatory
- **Architecture**: Follow SOLID principles and clean code practices
- **Framework**: MultiFlexi framework for application structure

### Code Quality Requirements
- **Documentation**: Every class, method, and function MUST have comprehensive DocBlocks with:
  - Purpose description
  - @param tags with types and descriptions
  - @return tags with types and descriptions
  - @throws tags for exceptions
  - @example tags where helpful
- **Type Safety**: Always use strict typing (`declare(strict_types=1)`) and type hints
- **Error Handling**: Use proper exception handling with meaningful error messages
- **Variables**: Use descriptive, intention-revealing variable names
- **Constants**: Replace magic numbers/strings with named constants or enums
- **Performance**: Consider performance implications, especially for batch operations

### Testing Requirements
- **Framework**: PHPUnit for all tests
- **Coverage**: Every new class MUST have corresponding test files
- **Location**: Tests go in `tests/` directory mirroring `src/` structure
- **Naming**: Test classes should end with `Test` suffix
- **Standards**: Follow PSR-12 in test code as well

- **Standards**: Follow PSR-12 in test code as well

### Internationalization
- **Library**: Use i18n library for all user-facing strings
- **Function**: Wrap translatable strings with `_()` function
- **Language**: All code, comments, and error messages in English

### Security & Performance
- **Security**: Never expose sensitive information in code or logs
- **Validation**: Always validate user inputs and API responses
- **Logging**: Use appropriate log levels and structured logging
- **Optimization**: Profile and optimize database queries and API calls

## 🔍 Development Workflow

### Mandatory Quality Checks
1. **PHP Syntax Check**: After EVERY PHP file edit, run `php -l filename.php`
2. **Schema Validation**: Before committing, validate JSON files against schemas
3. **Test Execution**: Run relevant tests after code changes
4. **Code Review**: Ensure code follows all standards before committing

### Git Commit Standards
- **Format**: Use imperative mood ("Add feature" not "Added feature")
- **Length**: Keep subject line under 50 characters
- **Body**: Include detailed description for complex changes
- **Scope**: Make atomic commits focused on single changes

## 📊 MultiFlexi Application Standards

### JSON Configuration Files
All `multiflexi/*.app.json` files MUST conform to the official schema:
- **Schema URL**: https://raw.githubusercontent.com/VitexSoftware/php-vitexsoftware-multiflexi-core/refs/heads/main/multiflexi.app.schema.json
- **Validation**: Always validate against schema before modifying
- **Structure**: Follow exact schema requirements for all properties
- **Environment Variables**: Use proper types (string, int, bool) and meaningful descriptions

### Report Generation
Generated reports MUST conform to the MultiFlexi report schema:
- **Schema URL**: https://raw.githubusercontent.com/VitexSoftware/php-vitexsoftware-multiflexi-core/refs/heads/main/multiflexi.report.schema.json
- **Format**: JSON format with proper structure
- **Content**: Include all required fields (timestamp, status, summary, etc.)

### Validation Commands
```bash
# JSON syntax validation
find multiflexi/ -name "*.json" -exec python3 -m json.tool {} \; -print

# Schema validation (requires: pip install jsonschema requests)
python3 -c "
import json, requests, jsonschema, glob
schema = requests.get('https://raw.githubusercontent.com/VitexSoftware/php-vitexsoftware-multiflexi-core/refs/heads/main/multiflexi.app.schema.json').json()
[jsonschema.validate(json.load(open(f)), schema) or print(f'✅ {f} is valid') for f in glob.glob('multiflexi/*.json')]
"
```

## 🔌 API Integration Standards

### IPEX API Integration
- **Documentation**: Always refer to https://restapi.ipex.cz/swagger.json
- **Authentication**: Use proper API credentials management
- **Error Handling**: Handle API timeouts, rate limits, and error responses
- **Data Validation**: Validate all API responses before processing
- **Caching**: Implement appropriate caching for frequently accessed data

### AbraFlexi Integration
- **SDK**: Use official AbraFlexi PHP SDK
- **Transactions**: Wrap related operations in transactions
- **Error Recovery**: Implement proper error recovery mechanisms
- **Data Integrity**: Ensure data consistency between systems

## 🎯 Code Generation Guidelines

When generating code:

1. **Start with interfaces/contracts** before implementations
2. **Use dependency injection** for better testability  
3. **Implement proper logging** at appropriate levels
4. **Add configuration validation** for environment variables
5. **Include comprehensive error handling** with user-friendly messages
6. **Write tests first** (TDD approach preferred)
7. **Document complex business logic** with inline comments
8. **Use meaningful method/class names** that express intent

## 🚫 Avoid These Patterns

- Hard-coded values (use configuration/environment variables)
- Silent failures (always log errors appropriately)
- Overly complex methods (follow single responsibility principle)
- Missing type declarations (always use strict typing)
- Inadequate error messages (provide context and solutions)
- Tight coupling between classes (use dependency injection)
- Direct database queries in business logic (use repositories/services)

## 📚 Helpful Context

### Project Structure
- `src/` - Main application code
- `tests/` - PHPUnit test files
- `multiflexi/` - MultiFlexi application configurations
- `bin/` - Executable scripts
- `debian/` - Debian packaging files

### Key Dependencies
- AbraFlexi PHP SDK
- MultiFlexi Core Framework
- mPDF for PDF generation
- Monolog for logging
- PHPUnit for testing

This project integrates VoIP call data from IPEX with AbraFlexi accounting system to automate billing processes for telecommunications services.

