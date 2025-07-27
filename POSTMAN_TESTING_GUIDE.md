# 🚀 Postman Testing Guide for Invoice API with Fiscalization

## 📋 Table of Contents
1. [Setup Instructions](#setup-instructions)
2. [Import Collection and Environment](#import-collection-and-environment)
3. [Authentication Setup](#authentication-setup)
4. [Complete Testing Workflow](#complete-testing-workflow)
5. [Fiscalization Testing](#fiscalization-testing)
6. [Troubleshooting](#troubleshooting)
7. [Expected Responses](#expected-responses)

---

## 🛠️ Setup Instructions

### Prerequisites
- **Postman Desktop App** installed
- **Laravel Invoice API** running on `http://localhost:8000`
- **Database** seeded with admin user
- **Fiscalization API** configured and accessible

### Default Admin Credentials
```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

---

## 📥 Import Collection and Environment

### Step 1: Import Collection
1. Open **Postman**
2. Click **Import** button
3. Select **Upload Files**
4. Choose `Invoice_API_Postman_Collection.json`
5. Click **Import**

### Step 2: Import Environment
1. Click **Import** again
2. Select **Upload Files**
3. Choose `Invoice_API_Postman_Environment.json`
4. Click **Import**
5. Select the **Invoice API Environment** from the dropdown

---

## 🔐 Authentication Setup

### Step 1: Login to Get Token
1. Navigate to **Authentication > Login**
2. Click **Send**
3. **Expected Response:**
   ```json
   {
     "token": "1|abc123def456...",
     "user": {
       "id": 1,
       "name": "Admin User",
       "email": "admin@example.com"
     }
   }
   ```

### Step 2: Set Environment Variable
1. Copy the `token` value from the response
2. Click the **Environment** dropdown (top right)
3. Select **Invoice API Environment**
4. Set `token` variable to the copied value
5. Click **Save**

---

## 🔄 Complete Testing Workflow

### Phase 1: Client Management

#### 1.1 Create a Client
- **Request:** `Clients > Create Client`
- **Expected Status:** `201 Created`
- **Response:**
  ```json
  {
    "id": 2,
    "name": "Test Client for Fiscalization",
    "email": "test@example.com",
    "tax_id": "SKA",
    "address": "Test Address",
    "phone": "123456789"
  }
  ```
- **Action:** Copy the `id` and set `client_id` environment variable

#### 1.2 Get All Clients
- **Request:** `Clients > Get All Clients`
- **Expected Status:** `200 OK`
- **Verify:** Your created client appears in the list

#### 1.3 Get Client by ID
- **Request:** `Clients > Get Client by ID`
- **Update URL:** Replace `1` with your `client_id`
- **Expected Status:** `200 OK`

### Phase 2: Invoice Management

#### 2.1 Create Invoice
- **Request:** `Invoices > Create Invoice`
- **Update Body:** Replace `client_id: 1` with your actual `client_id`
- **Expected Status:** `201 Created`
- **Response:**
  ```json
  {
    "id": 15,
    "client_id": 2,
    "invoice_number": "INV-TEST-001",
    "fiscal_status": "pending",
    "items": [...]
  }
  ```
- **Action:** Copy the `id` and set `invoice_id` environment variable

#### 2.2 Get All Invoices
- **Request:** `Invoices > Get All Invoices`
- **Expected Status:** `200 OK`
- **Verify:** Your created invoice appears in the list

#### 2.3 Get Invoice by ID
- **Request:** `Invoices > Get Invoice by ID`
- **Update URL:** Replace `1` with your `invoice_id`
- **Expected Status:** `200 OK`

### Phase 3: Fiscalization Testing

#### 3.1 Test Fiscalization Management
- **Request:** `Fiscalization Management > Get Fiscalization Customers`
- **Expected Status:** `200 OK`
- **Purpose:** Verify connection to fiscalization system

#### 3.2 Fiscalize Invoice
- **Request:** `Invoices > Fiscalize Invoice`
- **Update URL:** Replace `1` with your `invoice_id`
- **Expected Status:** `200 OK`
- **Expected Response:**
  ```json
  {
    "status": "success",
    "data": {
      "body": [{
        "qrcode_url": "https://eFiskalizimi-app-test.tatime.gov.al/...",
        "iic": "ABC123DEF456...",
        "fiscal_invnum": "231/2025",
        "iic_signature": "long_signature_here..."
      }]
    },
    "verification_url": "https://eFiskalizimi-app-test.tatime.gov.al/..."
  }
  ```

#### 3.3 Test Verification URL
- **Action:** Copy the `verification_url` from the response
- **Test:** Open the URL in a browser
- **Expected:** Albanian tax authority verification page loads

### Phase 4: Import Testing (Optional)

#### 4.1 Upload Excel File
- **Request:** `Imports > Upload Excel File`
- **Body:** Select an Excel file with invoice data
- **Expected Status:** `201 Created`
- **Action:** Copy the `id` and set `import_id` environment variable

#### 4.2 Get Import Status
- **Request:** `Imports > Get Import by ID`
- **Update URL:** Replace `1` with your `import_id`
- **Expected Status:** `200 OK`

#### 4.3 Fiscalize Import (Bulk Excel Fiscalization)
- **Request:** `Imports > Fiscalize Import (Bulk Excel Fiscalization)`
- **Update URL:** Replace `1` with your `import_id`
- **Expected Status:** `200 OK`
- **Expected Response:**
  ```json
  {
    "import_id": 1,
    "total_invoices": 2,
    "successful": 2,
    "failed": 0,
    "overall_status": "success",
    "results": [
      {
        "invoice_id": 15,
        "invoice_number": "INV-2025-001",
        "status": "success",
        "verification_url": "https://eFiskalizimi-app-test.tatime.gov.al/..."
      },
      {
        "invoice_id": 16,
        "invoice_number": "INV-2025-002",
        "status": "success",
        "verification_url": "https://eFiskalizimi-app-test.tatime.gov.al/..."
      }
    ]
  }
  ```

---

## 🎯 Fiscalization Testing

### Success Indicators
✅ **Status:** `"success"` in response  
✅ **IIC:** Long hexadecimal fiscal number generated  
✅ **QR Code URL:** Working verification URL provided  
✅ **Fiscal Status:** Invoice status changes to `"sent"`  

### Common Issues & Solutions

#### Issue: "Client not found" Error
- **Cause:** Client doesn't exist in fiscalization system
- **Solution:** The system automatically creates clients and falls back to existing ones

#### Issue: Date Validation Error
- **Cause:** Invoice date is too old
- **Solution:** Use future dates (after current time restrictions)

#### Issue: VAT Percentage Error
- **Cause:** Incorrect tax rate configuration
- **Solution:** System uses correct `tax_rate_id: 2` and `item_unit_id: 21`

#### Issue: Authentication Error
- **Cause:** Invalid or expired token
- **Solution:** Re-login and update the `token` environment variable

---

## 🔧 Troubleshooting

### Environment Variables Not Working
1. **Check Environment Selection:** Ensure "Invoice API Environment" is selected
2. **Verify Variable Names:** Use exact names: `base_url`, `token`, `client_id`, `invoice_id`
3. **Save Changes:** Always click "Save" after updating variables

### API Not Responding
1. **Check Server:** Ensure Laravel server is running (`php artisan serve`)
2. **Verify URL:** Check `base_url` variable is correct
3. **Check Logs:** Review Laravel logs for errors

### Fiscalization Fails
1. **Check Configuration:** Verify fiscalization API credentials in `.env`
2. **Network Issues:** Ensure fiscalization API is accessible
3. **Date Issues:** Use future dates for invoices
4. **Client Issues:** Verify client has valid tax_id

---

## 📊 Expected Responses

### Successful Login
```json
{
  "token": "1|abc123def456...",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com"
  }
}
```

### Successful Invoice Creation
```json
{
  "id": 15,
  "client_id": 2,
  "invoice_number": "INV-TEST-001",
  "invoice_date": "2025-07-27 20:00:00",
  "total_without_tax": "100.00",
  "total_tax": "20.00",
  "total_with_tax": "120.00",
  "fiscal_status": "pending",
  "created_at": "2025-07-27T20:00:00.000000Z",
  "updated_at": "2025-07-27T20:00:00.000000Z"
}
```

### Successful Fiscalization
```json
{
  "status": "success",
  "data": {
    "body": [{
      "sales_invoice_header_id": 295,
      "qrcode_url": "https://eFiskalizimi-app-test.tatime.gov.al/invoice-check/#/verify?iic=ABC123...",
      "iic": "ABC123DEF456...",
      "fiscal_invnum": "231/2025",
      "iic_signature": "long_signature_here...",
      "display_qrcode": "https://elif12.2rmlab.com/s/...",
      "short_qrcode_url": "https://elif12.2rmlab.com/s/..."
    }],
    "status": {
      "code": 600,
      "message": "Success!"
    }
  },
  "verification_url": "https://eFiskalizimi-app-test.tatime.gov.al/invoice-check/#/verify?iic=ABC123...&tin=SKA&crtd=2025-07-27T20:00:00+02:00&ord=15&bu=li519qp911&cr=ny172di313&sw=dx582kn875&prc=120.00"
}
```

### Error Response Example
```json
{
  "status": "error",
  "data": {
    "status": {
      "code": 999999,
      "message": "Error message in Albanian"
    }
  }
}
```

---

## 🎉 Testing Checklist

- [ ] **Environment imported and selected**
- [ ] **Login successful and token set**
- [ ] **Client created successfully**
- [ ] **Invoice created successfully**
- [ ] **Fiscalization completed successfully**
- [ ] **Verification URL works in browser**
- [ ] **All CRUD operations tested**
- [ ] **Error handling verified**

---

## 📞 Support

If you encounter issues:
1. **Check Laravel logs:** `storage/logs/laravel.log`
2. **Verify API configuration:** Check `.env` file
3. **Test fiscalization connection:** Use the test endpoints
4. **Review this guide:** Ensure all steps followed correctly

**Happy Testing! 🚀** 