# 📊 Excel Fiscalization Guide

## 🎯 Overview

The Invoice API now supports **bulk fiscalization of Excel files**! You can upload an Excel file with multiple invoices and fiscalize them all at once.

## 🚀 How It Works

### **Two-Step Process:**

1. **📤 Upload Excel File** → Creates invoices in the system
2. **⚡ Bulk Fiscalize** → Sends all invoices to Albanian tax authority

### **Workflow:**
```
Excel File → Upload → Import → Bulk Fiscalize → All Invoices Fiscalized
```

## 📋 Excel File Requirements

### **File Format:**
- **Format:** `.xlsx` or `.xls`
- **Encoding:** UTF-8
- **Headers:** First row must contain column headers

### **Required Columns (in order):**

| Column | Header | Description | Example | Required |
|--------|--------|-------------|---------|----------|
| A | client_name | Client's full name | "Test Company Ltd" | ✅ |
| B | invoice_number | Unique invoice number | "INV-2025-001" | ✅ |
| C | invoice_date | Invoice date (YYYY-MM-DD) | "2025-07-27" | ✅ |
| D | total_without_tax | Subtotal without tax | 100.00 | ✅ |
| E | total_tax | Tax amount | 20.00 | ✅ |
| F | total_with_tax | Total with tax | 120.00 | ✅ |
| G | item_description | Item description | "Test Item" | ✅ |
| H | item_quantity | Quantity | 1 | ✅ |
| I | item_unit | Unit of measurement | "piece" | ✅ |
| J | item_price | Unit price | 100.00 | ✅ |
| K | item_tax | Item tax amount | 20.00 | ✅ |
| L | item_total | Item total | 120.00 | ✅ |

### **Sample Excel Data:**
```
client_name,invoice_number,invoice_date,total_without_tax,total_tax,total_with_tax,item_description,item_quantity,item_unit,item_price,item_tax,item_total
"Test Company Ltd","INV-2025-001","2025-07-27",100.00,20.00,120.00,"Test Item",1,"piece",100.00,20.00,120.00
"Another Client","INV-2025-002","2025-07-27",200.00,40.00,240.00,"Another Item",2,"pieces",100.00,40.00,240.00
"Third Client","INV-2025-003","2025-07-27",150.00,30.00,180.00,"Third Item",1,"piece",150.00,30.00,180.00
```

## 🔄 Complete Workflow

### **Step 1: Upload Excel File**
```bash
POST /api/imports
Content-Type: multipart/form-data

file: [your-excel-file.xlsx]
```

**Response:**
```json
{
  "id": 1,
  "file_path": "imports/abc123.xlsx",
  "status": "completed",
  "created_by": 1,
  "created_at": "2025-07-27T20:00:00.000000Z"
}
```

### **Step 2: Bulk Fiscalize**
```bash
POST /api/imports/1/fiscalize
Authorization: Bearer your-token
```

**Response:**
```json
{
  "import_id": 1,
  "total_invoices": 3,
  "successful": 3,
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
    },
    {
      "invoice_id": 16,
      "invoice_number": "INV-2025-002",
      "status": "success",
      "verification_url": "https://eFiskalizimi-app-test.tatime.gov.al/..."
    },
    {
      "invoice_id": 17,
      "invoice_number": "INV-2025-003",
      "status": "success",
      "verification_url": "https://eFiskalizimi-app-test.tatime.gov.al/..."
    }
  ]
}
```

## 🎯 Postman Testing

### **Complete Excel Fiscalization Test:**

1. **Upload Excel File:**
   - `Imports > Upload Excel File`
   - Select your Excel file
   - Copy the `id` from response

2. **Bulk Fiscalize:**
   - `Imports > Fiscalize Import (Bulk Excel Fiscalization)`
   - Update URL: replace `1` with your import `id`
   - **🎉 Success!** All invoices fiscalized

## 📊 Response Statuses

### **Overall Status:**
- **`success`** - All invoices fiscalized successfully
- **`partial`** - Some invoices succeeded, some failed
- **`error`** - Import not completed or other error

### **Individual Invoice Status:**
- **`success`** - Invoice fiscalized with verification URL
- **`error`** - Fiscalization failed with error message

## ⚠️ Important Notes

### **Date Requirements:**
- **Use future dates** (after current time restrictions)
- **Format:** `YYYY-MM-DD` or `YYYY-MM-DD HH:MM:SS`
- **Example:** `"2025-07-27"` or `"2025-07-27 20:00:00"`

### **Tax Calculations:**
- **System uses 20% Albanian VAT**
- **Ensure calculations are accurate**
- **All amounts in Albanian Lek (ALL)**

### **Client Management:**
- **Clients are auto-created** from Excel data
- **Tax ID defaults to "SKA"** if not provided
- **System handles client creation** in fiscalization system

## 🔧 Error Handling

### **Common Errors:**

#### **Import Errors:**
- **Missing required fields** - Check all 12 columns are present
- **Invalid date format** - Use YYYY-MM-DD format
- **Duplicate invoice numbers** - Ensure unique invoice numbers

#### **Fiscalization Errors:**
- **Date validation** - Use future dates
- **Client not found** - System auto-creates clients
- **VAT percentage** - System uses correct tax rates

### **Partial Success:**
- **Some invoices succeed, others fail**
- **Check individual results** for specific errors
- **Retry failed invoices** individually if needed

## 🚀 Benefits

### **Time Saving:**
- **Upload once, fiscalize all**
- **No manual individual processing**
- **Batch error handling**

### **Consistency:**
- **Standardized process**
- **Automatic client creation**
- **Unified error reporting**

### **Scalability:**
- **Handle large Excel files**
- **Process hundreds of invoices**
- **Efficient bulk operations**

## 📞 Support

### **Testing:**
- Use the **Postman collection** for testing
- Check **Laravel logs** for detailed errors
- Verify **Excel format** matches requirements

### **Troubleshooting:**
- **Check import status** before fiscalizing
- **Verify date formats** in Excel
- **Ensure unique invoice numbers**

**Happy Excel Fiscalization! 🎉** 