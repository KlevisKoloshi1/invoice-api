# Fiscalization Testing Guide

This guide provides multiple ways to test the fiscalization functionality in your Laravel invoice API.

## 🧪 Testing Methods

### 1. Automated Tests (Recommended)

Run the comprehensive test suite:

```bash
# Run all tests
php artisan test

# Run only fiscalization tests
php artisan test tests/Feature/InvoiceFiscalizationTest.php

# Run with verbose output
php artisan test --verbose tests/Feature/InvoiceFiscalizationTest.php
```

The test suite includes:
- ✅ Successful fiscalization
- ✅ Error handling
- ✅ Authentication requirements
- ✅ Role-based access control
- ✅ Invalid invoice handling
- ✅ Network error handling
- ✅ Payload structure validation

### 2. Artisan Command Testing

Use the custom artisan command to test fiscalization:

```bash
# Create test data and fiscalize
php artisan test:fiscalization

# Test with existing invoice (replace 1 with actual invoice ID)
php artisan test:fiscalization 1
```

This command will:
- Create test data if needed
- Show invoice details
- Perform fiscalization
- Display results

### 3. Direct API Testing

Test the fiscalization endpoint directly:

```bash
# First, get an authentication token
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "password"}'

# Use the token to fiscalize an invoice (replace TOKEN and INVOICE_ID)
curl -X POST http://localhost:8000/api/invoices/INVOICE_ID/fiscalize \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json"
```

### 4. Standalone Script Testing

Test the fiscalization API directly without Laravel:

```bash
php test_fiscalization.php
```

This script:
- Loads environment variables
- Makes direct API calls
- Shows detailed request/response
- Handles errors gracefully

### 5. Verification URL Testing

Test the verification URL generation:

```bash
php test_verification_url.php
```

This script:
- Simulates successful fiscalization response
- Generates verification URL in Albanian tax authority format
- Shows URL parameters and structure
- Validates URL format compliance

## 🔧 Prerequisites

### Environment Setup

Ensure your `.env` file has the correct fiscalization configuration:

```env
FISCALIZATION_API_URL=https://elif12.2rmlab.com/live/api/sales.php
FISCALIZATION_SERVER_CONFIG={"Url_API":"https://elif12.2rmlab.com/live/api","DB_Config":"elif_config","Company_DB_Name":"Elif_001_1202260_07-2024","HardwareId":"cfe8a423409129b0c36b418c71385eec","UserInfo":{"user_id":8001950,"username":"fiscaluser","password":null,"token":"6c3b1ef34f5e69711e3d52bc8a78ef4811039acdb383a84b8a6d17757e904734a318006cb0069ada6f132400be18701493e487ced6b7f6dfdb1da61a0e21f929"}}
```

### Database Setup

```bash
# Run migrations
php artisan migrate

# Seed test data
php artisan db:seed
```

### Test Data Requirements

For fiscalization to work, you need:
- ✅ Admin user with valid credentials
- ✅ Client with tax_id
- ✅ Invoice with items
- ✅ Valid invoice data (dates, amounts, etc.)

## 📋 Test Scenarios

### Scenario 1: Successful Fiscalization
1. Create invoice with valid data
2. Ensure client has tax_id
3. Call fiscalization endpoint
4. Verify QR code URL is returned
5. Check invoice status is updated

### Scenario 2: Error Handling
1. Test with invalid invoice ID
2. Test with missing client data
3. Test with network errors
4. Verify error responses

### Scenario 3: Authentication & Authorization
1. Test without authentication
2. Test with public user role
3. Test with admin user role
4. Verify proper access control

## 🔍 Debugging

### Check Invoice Status

```bash
# View invoice details
php artisan tinker
>>> App\Models\Invoice::with(['client', 'items'])->find(1)
```

### Check Fiscalization Response

```bash
# View fiscalization response in database
php artisan tinker
>>> $invoice = App\Models\Invoice::find(1)
>>> $invoice->fiscal_response
```

### Log Analysis

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check for fiscalization-related errors
grep -i fiscal storage/logs/laravel.log
```

## 🚨 Common Issues

### Issue 1: "Invoice not found"
- Ensure invoice exists in database
- Check invoice ID is correct
- Verify database connection

### Issue 2: "Unauthorized"
- Check authentication token
- Verify user role is 'admin'
- Ensure token hasn't expired

### Issue 3: "API Error"
- Check fiscalization API credentials
- Verify network connectivity
- Review API response for specific errors

### Issue 4: "Missing client data"
- Ensure client has tax_id
- Check client-invoice relationship
- Verify client data is complete

## 📊 Expected Results

### Successful Response
```json
{
  "status": "success",
  "data": {
    "body": [{
      "qrcode_url": "https://example.com/qr-code",
      "fiscal_number": "8CD406762EE243885B3F041F0A3135E3",
      "status": "success"
    }],
    "verification_url": "https://eFiskalizimi-app-test.tatime.gov.al/invoice/check/#/verify?iic=8CD406762EE243885B3F041F0A3135E3&tin=L81310069K&crtd=2024-11-14T13:59:00+01:00&ord=1&bu=li519qp911&cr=fk681zu051&sw=dx582kn875&prc=120.00"
  },
  "verification_url": "https://eFiskalizimi-app-test.tatime.gov.al/invoice/check/#/verify?iic=8CD406762EE243885B3F041F0A3135E3&tin=L81310069K&crtd=2024-11-14T13:59:00+01:00&ord=1&bu=li519qp911&cr=fk681zu051&sw=dx582kn875&prc=120.00"
}
```

### Verification URL Format
The system generates an Albanian tax authority verification URL with the following parameters:

- **iic**: Invoice Identification Code (fiscal number)
- **tin**: Tax Identification Number (client's tax ID)
- **crtd**: Created date/time in ISO 8601 format
- **ord**: Order number (invoice ID)
- **bu**: Business unit identifier
- **cr**: Cash register identifier
- **sw**: Software identifier
- **prc**: Price (total amount with tax)

Example URL:
```
https://eFiskalizimi-app-test.tatime.gov.al/invoice/check/#/verify?iic=8CD406762EE243885B3F041F0A3135E3&tin=L81310069K&crtd=2024-11-14T13:59:00+01:00&ord=1&bu=li519qp911&cr=fk681zu051&sw=dx582kn875&prc=120.00
```

### Error Response
```json
{
  "status": "error",
  "message": "Error description"
}
```

## 🛠️ Troubleshooting Commands

```bash
# Clear cache
php artisan cache:clear
php artisan config:clear

# Regenerate autoload files
composer dump-autoload

# Check environment
php artisan env

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo()
```

## 📞 Support

If you encounter issues:
1. Check the logs first
2. Verify environment configuration
3. Test with standalone script
4. Review API documentation
5. Contact the fiscalization API provider

## 🔄 Continuous Testing

For ongoing testing, consider:
- Setting up automated tests in CI/CD
- Regular manual testing with real data
- Monitoring fiscalization success rates
- Logging and alerting for failures 