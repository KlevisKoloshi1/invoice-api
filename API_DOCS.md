# Invoice API Documentation

## Authentication

### POST /api/login
- **Body:**
  ```json
  { "email": "admin@example.com", "password": "password" }
  ```
- **Response:**
  ```json
  { "token": "...", "user": { ... } }
  ```
- **Errors:** 401 Invalid credentials

---

## Imports

### GET /api/imports
- **Auth:** Admin/Public
- **Query:** `per_page` (optional)
- **Response:** Paginated list of imports

### POST /api/imports
- **Auth:** Admin
- **Body:** `file` (form-data, .xlsx/.xls)
- **Response:** Import object
- **Errors:** 422 (validation), 500 (import error)

### GET /api/imports/{id}
- **Auth:** Admin/Public
- **Response:** Import object

### PUT /api/imports/{id}
- **Auth:** Admin
- **Body:** Partial update fields
- **Response:** Updated import object

### DELETE /api/imports/{id}
- **Auth:** Admin
- **Response:** `{ "message": "Import deleted" }`

### POST /api/imports/{id}/fiscalize
- **Auth:** Admin
- **Description:** Fiscalize all invoices from an Excel import in bulk
- **Response:** Bulk fiscalization results
- **Success Response:**
  ```json
  {
    "import_id": 1,
    "total_invoices": 5,
    "successful": 5,
    "failed": 0,
    "overall_status": "success",
    "results": [
      {
        "invoice_id": 15,
        "invoice_number": "INV-2025-001",
        "status": "success",
        "data": {
          "body": [{
            "qrcode_url": "https://eFiskalizimi-app-test.tatime.gov.al/...",
            "iic": "ABC123DEF456...",
            "fiscal_invnum": "231/2025"
          }]
        },
        "verification_url": "https://eFiskalizimi-app-test.tatime.gov.al/..."
      }
    ]
  }
  ```
- **Partial Success Response:**
  ```json
  {
    "import_id": 1,
    "total_invoices": 5,
    "successful": 3,
    "failed": 2,
    "overall_status": "partial",
    "results": [
      {
        "invoice_id": 15,
        "invoice_number": "INV-2025-001",
        "status": "success",
        "verification_url": "https://..."
      },
      {
        "invoice_id": 16,
        "invoice_number": "INV-2025-002",
        "status": "error",
        "error": "Date validation failed"
      }
    ]
  }
  ```

---

## Invoices

### GET /api/invoices
- **Auth:** Admin/Public
- **Query:** `per_page` (optional)
- **Response:** Paginated list of invoices

### POST /api/invoices
- **Auth:** Admin
- **Body:**
  ```json
  {
    "client_id": 1,
    "invoice_number": "INV-001",
    "invoice_date": "2024-07-23",
    "total_without_tax": 100,
    "total_tax": 20,
    "total_with_tax": 120,
    "items": [
      {
        "description": "Item 1",
        "quantity": 2,
        "unit": "pcs",
        "price": 50,
        "tax": 10,
        "total": 60
      }
    ]
  }
  ```
- **Response:** Invoice object
- **Errors:** 422 (validation)

### GET /api/invoices/{id}
- **Auth:** Admin/Public
- **Response:** Invoice object

### PUT /api/invoices/{id}
- **Auth:** Admin
- **Body:** Partial update fields
- **Response:** Updated invoice object

### DELETE /api/invoices/{id}
- **Auth:** Admin
- **Response:** `{ "message": "Invoice deleted" }`

### POST /api/invoices/{id}/fiscalize
- **Auth:** Admin
- **Response:** Fiscalization result (success/error, fiscal data, verification URL)
- **Success Response:**
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

---

## Fiscalization Management

### POST /api/fiscalization/customers
- **Auth:** Admin
- **Body:**
  ```json
  {
    "name": "Customer Name",
    "tax_id": "123456789",
    "address": "Customer Address",
    "phone": "1234567890",
    "email": "customer@email.com"
  }
  ```
- **Response:** Customer creation result

### GET /api/fiscalization/customers
- **Auth:** Admin
- **Response:** List of existing customers in fiscalization system

### POST /api/fiscalization/items
- **Auth:** Admin
- **Body:**
  ```json
  {
    "item_code": "ITEM001",
    "item_name": "Item Name",
    "price": 100.00
  }
  ```
- **Response:** Item creation result

### GET /api/fiscalization/items
- **Auth:** Admin
- **Response:** List of existing items in fiscalization system

---

## Clients

### GET /api/clients
- **Auth:** Admin/Public
- **Query:** `per_page` (optional)
- **Response:** Paginated list of clients

### POST /api/clients
- **Auth:** Admin
- **Body:**
  ```json
  {
    "name": "Client Name",
    "email": "client@email.com",
    "tax_id": "123456789",
    "address": "Address",
    "phone": "1234567890"
  }
  ```
- **Response:** Client object
- **Errors:** 422 (validation)

### GET /api/clients/{id}
- **Auth:** Admin/Public
- **Response:** Client object

### PUT /api/clients/{id}
- **Auth:** Admin
- **Body:** Partial update fields
- **Response:** Updated client object

### DELETE /api/clients/{id}
- **Auth:** Admin
- **Response:** `{ "message": "Client deleted" }`

---

## Error Codes
- 401: Unauthorized (invalid token or role)
- 403: Forbidden (wrong role)
- 422: Validation error
- 500: Server error

---

## Notes
- All endpoints require Bearer token authentication (see `/api/login`).
- Admin endpoints require an admin user. Public users have read-only access.
- For Excel import, the file must be a valid .xlsx or .xls file with required columns.
- Fiscalization status and response are stored in the invoice object after fiscalization. 