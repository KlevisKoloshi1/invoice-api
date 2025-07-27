# Postman Fiscalization Testing Guide

This guide will walk you through the complete process of generating a fiscalized invoice using Postman, from authentication to receiving the verification URL that is **dynamically generated based on your actual invoice data**.

## Prerequisites

1. **Postman installed** on your computer
2. **Laravel API running** (make sure your server is started with `php artisan serve`)
3. **Database seeded** with test data (run `php artisan db:seed`)

## Step 1: Authentication

### 1.1 Login to Get Bearer Token

**Request:**
- **Method:** POST
- **URL:** `http://localhost:8000/api/login`
- **Headers:**
  ```
  Content-Type: application/json
  ```
- **Body (raw JSON):**
  ```json
  {
    "email": "admin@example.com",
    "password": "password"
  }
  ```

**Expected Response:**
```json
{
  "status": "success",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com",
      "role": "admin"
    },
    "token": "1|abc123def456ghi789..."
  }
}
```

**Important:** Copy the `token` value from the response - you'll need it for all subsequent requests.

## Step 2: Create Test Data (Optional)

If you don't have test data, you can create it using the API endpoints:

### 2.1 Create a Client

**Request:**
- **Method:** POST
- **URL:** `http://localhost:8000/api/clients`
- **Headers:**
  ```
  Content-Type: application/json
  Authorization: Bearer YOUR_TOKEN_HERE
  ```
- **Body (raw JSON):**
  ```json
  {
    "name": "Test Client",
    "email": "client@test.com",
    "phone": "+355123456789",
    "address": "Test Address, Tirana",
    "tax_id": "L81310069K"
  }
  ```

### 2.2 Create an Invoice

**Request:**
- **Method:** POST
- **URL:** `http://localhost:8000/api/invoices`
- **Headers:**
  ```
  Content-Type: application/json
  Authorization: Bearer YOUR_TOKEN_HERE
  ```
- **Body (raw JSON):**
  ```json
  {
    "client_id": 1,
    "invoice_number": "INV-001",
    "invoice_date": "2024-11-14",
    "due_date": "2024-12-14",
    "subtotal": 100.00,
    "tax_rate": 20.00,
    "tax_amount": 20.00,
    "total_with_tax": 120.00,
    "items": [
      {
        "description": "Test Product",
        "quantity": 1,
        "unit_price": 100.00,
        "total": 100.00
      }
    ]
  }
  ```

**Note:** Replace `YOUR_TOKEN_HERE` with the actual token you received from the login step.

## Step 3: Fiscalize the Invoice

### 3.1 Get Invoice ID

First, list your invoices to get the ID of the invoice you want to fiscalize:

**Request:**
- **Method:** GET
- **URL:** `http://localhost:8000/api/invoices`
- **Headers:**
  ```
  Authorization: Bearer YOUR_TOKEN_HERE
  ```

### 3.2 Fiscalize the Invoice

**Request:**
- **Method:** POST
- **URL:** `http://localhost:8000/api/invoices/{id}/fiscalize`
- **Headers:**
  ```
  Authorization: Bearer YOUR_TOKEN_HERE
  ```
- **Body:** None (empty)

**Example URL:** `http://localhost:8000/api/invoices/1/fiscalize`

## Step 4: Analyze the Response

### 4.1 Successful Fiscalization Response

If fiscalization is successful, you'll receive a response like this:

```json
{
  "status": "success",
  "data": {
    "body": [
      {
        "qrcode_url": "https://example.com/qr-code",
        "fiscal_number": "8CD406762EE243885B3F041F0A3135E3",
        "status": "success"
      }
    ],
    "verification_url": "https://eFiskalizimi-app-test.tatime.gov.al/invoice/check/#/verify?iic=8CD406762EE243885B3F041F0A3135E3&tin=L81310069K&crtd=2024-11-14T13:59:00+01:00&ord=1&bu=li519qp911&cr=fk681zu051&sw=dx582kn875&prc=120.00"
  },
  "verification_url": "https://eFiskalizimi-app-test.tatime.gov.al/invoice/check/#/verify?iic=8CD406762EE243885B3F041F0A3135E3&tin=L81310069K&crtd=2024-11-14T13:59:00+01:00&ord=1&bu=li519qp911&cr=fk681zu051&sw=dx582kn875&prc=120.00"
}
```

### 4.2 Key Response Elements

- **`status`**: "success" indicates successful fiscalization
- **`data.body[0].fiscal_number`**: The fiscal identification code (IIC)
- **`verification_url`**: The Albanian tax authority verification URL
- **`data.body[0].qrcode_url`**: QR code URL for the fiscalized invoice

## Step 5: Verify the Fiscalized Invoice

### 5.1 Open the Verification URL

Copy the `verification_url` from the response and open it in your browser. This URL will take you to the Albanian tax authority's verification page where you can:

- View the fiscalized invoice details
- Verify the fiscal number
- Download or print the fiscal receipt

### 5.2 URL Parameters Explained

The verification URL contains these parameters, **all generated from your actual invoice data**:

- **`iic`**: Invoice Identification Code (fiscal number from API response)
- **`tin`**: Tax Identification Number (client's tax ID from database)
- **`crtd`**: Created date/time in ISO 8601 format (invoice date + current time)
- **`ord`**: Order number (actual invoice ID from database)
- **`bu`**: Business unit identifier (configurable)
- **`cr`**: Cash register identifier (configurable)
- **`sw`**: Software identifier (configurable)
- **`prc`**: Price (actual total amount with tax from invoice)

## Step 6: How the URL is Generated from Invoice Data

The verification URL is **dynamically generated** using your actual invoice data:

### 6.1 Data Sources

| Parameter | Source | Example |
|-----------|--------|---------|
| `iic` | Fiscal API response | `8CD406762EE243885B3F041F0A3135E3` |
| `tin` | Client's tax_id field | `L81310069K` |
| `crtd` | Invoice date + current time | `2024-11-14T13:59:00+01:00` |
| `ord` | Invoice ID | `1` |
| `prc` | Invoice total_with_tax | `120.00` |

### 6.2 Example with Real Data

If your invoice has:
- **ID**: 2
- **Invoice Number**: INV-2024-001-UPDATED
- **Date**: 2024-01-15
- **Total**: 1800.00
- **Client Tax ID**: 123456789

The generated URL will be:
```
https://eFiskalizimi-app-test.tatime.gov.al/invoice/check/#/verify?iic=8CD406762EE243885B3F041F0A3135E3&tin=123456789&crtd=2024-01-15T15:19:05+01:00&ord=2&bu=li519qp911&cr=fk681zu051&sw=dx582kn875&prc=1800.00
```

Notice how:
- `ord=2` (actual invoice ID)
- `tin=123456789` (actual client tax ID)
- `prc=1800.00` (actual invoice total)
- `crtd=2024-01-15T15:19:05+01:00` (actual invoice date + current time)

## Step 7: Troubleshooting

### 7.1 Common Issues

**Authentication Error (401):**
- Make sure you're using the correct Bearer token
- Check if the token has expired
- Verify you're logged in as an admin user

**Invoice Not Found (404):**
- Verify the invoice ID exists
- Check if the invoice belongs to your account

**Fiscalization Error:**
- Check the `fiscal_response` field in the response for error details
- Verify your fiscalization service configuration
- Ensure the invoice has all required fields

### 7.2 Error Response Example

```json
{
  "status": "error",
  "data": {
    "error": "Fiscalization service unavailable"
  }
}
```

## Step 8: Postman Collection Setup

### 8.1 Create a Collection

1. Open Postman
2. Click "New" → "Collection"
3. Name it "Invoice API Fiscalization"

### 8.2 Add Environment Variables

1. Click "Environments" → "New"
2. Add these variables:
   - `base_url`: `http://localhost:8000`
   - `token`: (leave empty, will be set after login)
   - `invoice_id`: (leave empty, will be set after creating invoice)

### 8.3 Create Request Templates

**Login Request:**
- Save the token to environment variable in "Tests" tab:
```javascript
if (pm.response.code === 200) {
    const response = pm.response.json();
    pm.environment.set("token", response.data.token);
}
```

**Invoice Creation:**
- Save the invoice ID to environment variable in "Tests" tab:
```javascript
if (pm.response.code === 201) {
    const response = pm.response.json();
    pm.environment.set("invoice_id", response.data.id);
}
```

## Complete Workflow Summary

1. **Login** → Get Bearer token
2. **Create Client** (if needed) → Get client ID
3. **Create Invoice** → Get invoice ID
4. **Fiscalize Invoice** → Get verification URL (generated from invoice data)
5. **Open Verification URL** → Verify fiscalized invoice

## Testing Different Scenarios

### Test with Different Invoice Amounts
Try fiscalizing invoices with different amounts to test the `prc` parameter in the verification URL.

### Test with Different Tax Rates
Create invoices with different tax rates to verify the calculations.

### Test Error Scenarios
- Try fiscalizing an already fiscalized invoice
- Test with invalid invoice data
- Test with network connectivity issues

## Key Points About URL Generation

✅ **Dynamic Generation**: The URL is generated using your actual invoice data  
✅ **Real Invoice ID**: The `ord` parameter uses the actual invoice ID from your database  
✅ **Real Client Tax ID**: The `tin` parameter uses the client's actual tax ID  
✅ **Real Amount**: The `prc` parameter uses the actual invoice total with tax  
✅ **Real Date**: The `crtd` parameter uses the actual invoice date + current time  
✅ **Configurable**: Business unit, cash register, and software identifiers can be configured  

This guide provides a complete workflow for testing the fiscalization feature using Postman, ensuring that the verification URL is generated using your real invoice data rather than hardcoded values. 