# 🚀 Quick Postman Setup Script

## ⚡ 5-Minute Setup Guide

### Step 1: Start Your Laravel Server
```bash
cd /path/to/invoice-api
php artisan serve
```

### Step 2: Import Postman Files
1. **Download these files:**
   - `Invoice_API_Postman_Collection.json`
   - `Invoice_API_Postman_Environment.json`

2. **Import into Postman:**
   - Open Postman
   - Click **Import** → **Upload Files**
   - Select both JSON files
   - Click **Import**

3. **Select Environment:**
   - Click the environment dropdown (top right)
   - Select **"Invoice API Environment"**

### Step 3: Get Authentication Token
1. **Run Login Request:**
   - Navigate to: `Authentication > Login`
   - Click **Send**
   - Copy the `token` from response

2. **Set Token Variable:**
   - Click environment dropdown
   - Set `token` = your copied token
   - Click **Save**

### Step 4: Test Basic Flow
1. **Create Client:**
   - `Clients > Create Client`
   - Copy the `id` from response
   - Set `client_id` environment variable

2. **Create Invoice:**
   - `Invoices > Create Invoice`
   - Update body: replace `client_id: 1` with your `client_id`
   - Copy the `id` from response
   - Set `invoice_id` environment variable

3. **Fiscalize Invoice:**
   - `Invoices > Fiscalize Invoice`
   - Update URL: replace `1` with your `invoice_id`
   - **🎉 Success!** You should get a verification URL

### Step 5: Test Verification
- Copy the `verification_url` from fiscalization response
- Open in browser
- Should show Albanian tax authority verification page

## ✅ Success Checklist
- [ ] Server running on `http://localhost:8000`
- [ ] Collection imported
- [ ] Environment selected
- [ ] Token set and working
- [ ] Client created
- [ ] Invoice created
- [ ] Fiscalization successful
- [ ] Verification URL works

## 🆘 Quick Troubleshooting

### "Unauthorized" Error
- Re-login and update token
- Check environment is selected

### "Client not found" Error
- ✅ **This is FIXED!** System auto-creates clients

### "Date validation" Error
- Use future dates: `"2025-07-27 20:00:00"`

### "VAT percentage" Error
- ✅ **This is FIXED!** System uses correct tax rates

## 📞 Need Help?
- Check `POSTMAN_TESTING_GUIDE.md` for detailed instructions
- Review `API_DOCS.md` for endpoint documentation
- Check Laravel logs: `storage/logs/laravel.log`

**You're all set! 🎉** 