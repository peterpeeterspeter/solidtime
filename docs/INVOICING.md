# Invoice Generation Guide

Timeclocker makes it easy to create professional, EU-compliant invoices directly from your tracked time. This guide covers everything you need to know about invoice generation, customization, and management.

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Creating Invoices](#creating-invoices)
3. [EU Compliance Features](#eu-compliance-features)
4. [Invoice Customization](#invoice-customization)
5. [Invoice Management](#invoice-management)
6. [PDF Generation](#pdf-generation)
7. [Troubleshooting](#troubleshooting)

---

## Quick Start

### Creating Your First Invoice

1. **Track some time** on a project (billable entries)
2. Go to **Time Entries** page
3. **Select entries** you want to invoice (click checkboxes or press `Space`)
4. Click **"Create Invoice"** button or press `I`
5. **Review** generated invoice
6. **Generate PDF** and send to client

**Keyboard Shortcut**: `⌘/Ctrl + Shift + E` to go to Time Entries, then `I` to create invoice.

---

## Creating Invoices

### Method 1: From Time Entries (Recommended)

This is the fastest way to create invoices from tracked time.

**Steps:**
1. Navigate to **Time Entries** page (`⌘/Ctrl + Shift + E`)
2. **Filter entries** by:
   - Date range (e.g., "Last month")
   - Project or client
   - Billable status
3. **Select entries** to invoice:
   - Click individual checkboxes
   - Or press `⌘/Ctrl + A` to select all visible
   - Or use `Space` to toggle selection
4. Click **"Create Invoice"** or press `I`
5. Review auto-generated invoice details
6. Click **"Generate PDF"**

**Automatic Calculations:**
- Timeclocker automatically:
  - Groups time entries by project/task
  - Calculates hours and amounts
  - Applies billable rates
  - Calculates subtotals and tax
  - Generates unique invoice number

### Method 2: Manual Invoice Creation

For services not tracked in Timeclocker or one-off invoices.

**Steps:**
1. Navigate to **Invoices** page (`⌘/Ctrl + Shift + I`)
2. Click **"New Invoice"** button
3. Fill in invoice details:
   - **Client Information**: Select existing or add new
   - **Invoice Date**: Defaults to today
   - **Due Date**: Auto-calculated based on payment terms
   - **Line Items**: Add manually with descriptions and amounts
   - **Tax Rate**: Set VAT/GST percentage
   - **Notes**: Payment terms, thank you message, etc.
4. Click **"Save Draft"** or **"Generate PDF"**

### Method 3: From Command Palette

**Quick Action:**
1. Press `⌘/Ctrl + K` to open Command Palette
2. Type "invoice" or "create invoice"
3. Press `Enter`
4. Follow invoice creation wizard

---

## EU Compliance Features

Timeclocker invoices are fully compliant with EU regulations, including GDPR and VAT directives.

### Required Fields (Auto-Generated)

✅ **Your Business Information:**
- Legal business name
- Full address (street, postal code, city, country)
- VAT number (if applicable)
- Company registration number
- Email and phone

✅ **Client Information:**
- Client name (individual or company)
- Full address
- VAT number (for B2B transactions)
- Email

✅ **Invoice Details:**
- Unique invoice number (sequential)
- Issue date
- Due date (based on payment terms)
- Currency (EUR, USD, GBP, etc.)
- Line items with descriptions
- Subtotal, tax breakdown, and total

### VAT Handling

**VAT Registration:**
1. Go to **Settings** → **Invoice Settings**
2. Enter your **VAT number** (format: DE123456789, NL123456789B01, etc.)
3. Select **default tax rate** (19%, 21%, etc. based on country)
4. Save settings

**Automatic VAT Calculation:**
- Timeclocker automatically calculates VAT based on:
  - Your location (where service is provided)
  - Client location (where service is received)
  - Service type (digital services, consulting, etc.)

**VAT Scenarios:**

| Scenario | VAT Applied? | Notes |
|----------|--------------|-------|
| 🇩🇪 DE business → 🇩🇪 DE client | ✅ Yes (19%) | Domestic transaction |
| 🇩🇪 DE business → 🇳🇱 NL business (VAT #) | ❌ No (Reverse Charge) | B2B EU cross-border |
| 🇩🇪 DE business → 🇳🇱 NL individual | ✅ Yes (19%) | B2C EU cross-border |
| 🇩🇪 DE business → 🇺🇸 US client | ❌ No | Non-EU transaction |

### Reverse Charge Mechanism

For **EU B2B transactions** (business to business within EU):

**What is Reverse Charge?**
- You don't charge VAT to the client
- Client pays VAT in their own country
- Required when both parties have valid VAT numbers

**How Timeclocker Handles It:**
1. Enter client's VAT number in client settings
2. When creating invoice, Timeclocker:
   - Verifies VAT number format
   - Applies reverse charge if applicable
   - Adds note: *"Reverse charge applies - VAT is payable by the recipient"*
   - Shows 0% VAT on invoice

**Example Invoice Text:**
```
Subtotal: €1,000.00
VAT (0% - Reverse Charge): €0.00
Total: €1,000.00

* Reverse charge applies - VAT is payable by the recipient
```

### Multi-Currency Support

**Supported Currencies:**
- EUR (Euro) - Default for EU
- USD (US Dollar)
- GBP (British Pound)
- CHF (Swiss Franc)
- All major currencies via ISO 4217 codes

**Setting Currency:**
1. Go to **Settings** → **Invoice Settings**
2. Select **Default Currency**
3. Currency symbol appears on all invoices

**Per-Invoice Currency:**
- When creating invoice, select different currency if needed
- Exchange rates are not automatically applied
- Enter amounts in selected currency

---

## Invoice Customization

### Branding Your Invoices

Make invoices look professional and on-brand.

**Logo Upload:**
1. Go to **Settings** → **Invoice Branding**
2. Upload your logo (PNG, JPG, SVG)
3. Recommended size: 400x200px (transparent background)
4. Logo appears in top-left of all invoices

**Color Customization:**
1. Select **Primary Color** (appears in headers, borders)
2. Defaults to Timeclocker cyan (#0891b2)
3. Use your brand color for consistency

**Business Details:**
1. **Company Name**: Legal business name
2. **Address**: Full postal address
3. **Contact Info**: Email, phone, website
4. **Tax IDs**: VAT number, company registration number
5. **Bank Details** (optional):
   - Bank name
   - IBAN
   - BIC/SWIFT code

**Footer Customization:**
- Add custom footer text (e.g., "Thank you for your business!")
- Include payment terms or legal disclaimers
- Max 500 characters

### Invoice Templates

**Default Template:**
Timeclocker provides a clean, professional template that includes:
- Your logo and business details
- Client information
- Invoice number and dates
- Line items table
- Subtotal, tax, and total
- Payment terms
- Bank details
- Footer text

**Template Sections:**

```
┌─────────────────────────────────────────┐
│ [YOUR LOGO]          INVOICE            │
│ Your Business Details  Invoice #: 2025-001│
│                        Date: 2025-11-05  │
│                        Due: 2025-12-05   │
├─────────────────────────────────────────┤
│ Bill To:                                │
│ Client Name                             │
│ Client Address                          │
│ VAT: DE123456789                        │
├─────────────────────────────────────────┤
│ Description       Qty  Rate    Amount   │
│ ─────────────────────────────────────   │
│ Web Development   40h  €100    €4,000   │
│ UI Design         10h  €120    €1,200   │
├─────────────────────────────────────────┤
│                    Subtotal:  €5,000.00 │
│                    VAT (19%):   €950.00 │
│                    Total:     €5,950.00 │
├─────────────────────────────────────────┤
│ Payment Terms: Net 30 days              │
│ Bank: Example Bank                      │
│ IBAN: DE89370400440532013000            │
│ BIC: COBADEFFXXX                        │
├─────────────────────────────────────────┤
│ Thank you for your business!            │
└─────────────────────────────────────────┘
```

### Line Item Configuration

**When Creating Invoice from Time Entries:**

**Grouping Options:**
- **By Project**: All time under project name
- **By Task**: Separate line for each task
- **By Date**: Daily breakdown (useful for detailed tracking)
- **Consolidated**: Single line "Professional Services"

**Example - Grouped by Project:**
```
Description: Website Development Project
Quantity: 40 hours
Unit Price: €100/hour
Amount: €4,000.00
```

**Example - Grouped by Task:**
```
1. Frontend Development - 20h @ €100/h = €2,000
2. Backend API - 15h @ €100/h = €1,500
3. Testing & Deployment - 5h @ €100/h = €500
```

**Editing Line Items:**
- Click any line item to edit
- Change description, quantity, or rate
- Totals recalculate automatically
- Add non-time line items (expenses, flat fees)

---

## Invoice Management

### Invoice Statuses

Timeclocker tracks invoice lifecycle with status indicators:

| Status | Color | Meaning | Actions Available |
|--------|-------|---------|-------------------|
| 📝 Draft | Yellow | Being edited, not sent | Edit, Delete, Generate PDF, Mark as Sent |
| 📤 Sent | Blue | Sent to client, awaiting payment | Mark as Paid, Send Reminder |
| ✅ Paid | Green | Payment received | Archive, Download PDF |
| ⚠️ Overdue | Red | Past due date, unpaid | Send Reminder, Mark as Paid |
| ❌ Cancelled | Gray | Cancelled or voided | Delete, Archive |

**Status Workflow:**
```
Draft → Sent → Paid
  ↓       ↓
Cancel  Overdue → Paid
```

### Invoice List & Filtering

**Navigate to Invoices:**
- Click **Invoices** in sidebar
- Or press `⌘/Ctrl + Shift + I`

**Filter Invoices:**
- **By Status**: All, Draft, Sent, Paid, Overdue
- **By Client**: Select from dropdown
- **By Date Range**: Last week, month, quarter, year, custom
- **Search**: Invoice number, client name, amount

**Bulk Actions:**
- Select multiple invoices (checkboxes)
- Mark as Sent/Paid
- Download PDFs (generates ZIP)
- Delete drafts

### Invoice Statistics Dashboard

At the top of Invoices page, view:

**📊 Stats Cards:**
- **Total Revenue**: Sum of all paid invoices
- **Outstanding**: Sum of sent + overdue invoices
- **Paid Invoices**: Count of paid invoices
- **Overdue**: Count of overdue invoices

**Example:**
```
┌──────────────┬──────────────┬──────────────┬──────────────┐
│ Total Revenue│ Outstanding  │ Paid Invoices│   Overdue    │
│   €25,000    │    €5,000    │      42      │      3       │
└──────────────┴──────────────┴──────────────┴──────────────┘
```

---

## PDF Generation

### Generating Invoice PDFs

**From Invoice List:**
1. Click **📥 Download** icon next to invoice
2. PDF downloads immediately
3. Filename: `invoice-[number].pdf` (e.g., `invoice-2025-001.pdf`)

**From Invoice Detail:**
1. Open invoice (click to view)
2. Click **"Generate PDF"** button
3. Or press `⌘/Ctrl + Shift + S`

**From Time Entries:**
1. Select time entries
2. Click **"Create Invoice"**
3. In preview, click **"Generate PDF"**

### PDF Technical Details

**PDF Features:**
- **Format**: A4 size (210mm × 297mm)
- **Resolution**: 300 DPI (print-ready)
- **File Size**: ~50-200 KB (optimized)
- **Fonts**: Professional sans-serif (system fonts)
- **Colors**: High contrast, printer-friendly

**PDF Generation Service:**
- Powered by **Gotenberg** (HTML to PDF conversion)
- Server-side rendering for consistent output
- Works on all devices and browsers

**Print Settings:**
- **Margins**: Optimized for printing and filing
- **Page Breaks**: Automatic for long invoices
- **Background**: White (no dark mode in PDF)

### Downloading Multiple Invoices

**Bulk Download:**
1. Go to **Invoices** page
2. Select multiple invoices (checkboxes)
3. Click **"Download Selected"**
4. Generates ZIP file with all PDFs

**Filename Convention:**
- Single: `invoice-2025-001.pdf`
- Bulk: `invoices-2025-11-05.zip`

---

## Advanced Features

### Payment Terms

**Default Payment Terms:**
Set in **Settings** → **Invoice Settings**

**Options:**
- **Net 7 days**: Due within 7 days
- **Net 14 days**: Due within 14 days
- **Net 30 days**: Due within 30 days (most common)
- **Net 60 days**: Due within 60 days
- **Due on Receipt**: Immediate payment

**Due Date Calculation:**
- Automatically calculated from issue date + payment terms
- Example: Invoice issued Nov 5 + Net 30 = Due Dec 5

**Custom Payment Terms:**
- Add custom text in invoice notes
- Example: "50% due on receipt, 50% on completion"

### Invoice Numbering

**Default Format:**
`[PREFIX][YEAR]-[NUMBER]`

**Example:**
- `INV2025-0001`
- `INV2025-0002`
- `INV2025-0003`

**Customization:**
1. Go to **Settings** → **Invoice Settings**
2. Set **Invoice Prefix** (e.g., "INV", "BILL", your company initials)
3. Set **Next Number** (if you have existing invoices)
4. Choose **Reset Yearly** (yes/no)

**Reset Options:**
- **Reset Yearly**: Number resets to 0001 each year (recommended)
- **Continuous**: Number keeps incrementing forever

**Legal Requirements (EU):**
- Invoice numbers must be **unique**
- Must be **sequential** (no gaps)
- Cannot be changed after invoice is finalized
- Timeclocker enforces these rules automatically

### Email Integration

**Sending Invoices via Email:**
1. Open invoice
2. Click **"Send Invoice"** button
3. Email modal opens with:
   - **To**: Client email (from client record)
   - **Subject**: "Invoice [NUMBER] from [YOUR BUSINESS]"
   - **Body**: Professional email template (editable)
   - **Attachment**: PDF invoice
4. Click **"Send"**

**Email Template:**
```
Subject: Invoice INV2025-001 from Your Business

Dear [Client Name],

Please find attached invoice INV2025-001 for the services
provided during [date range].

Invoice Details:
- Amount Due: €5,950.00
- Due Date: December 5, 2025
- Payment Terms: Net 30 days

Bank Transfer Details:
Bank: Example Bank
IBAN: DE89370400440532013000
BIC: COBADEFFXXX

If you have any questions, please don't hesitate to contact us.

Thank you for your business!

Best regards,
Your Business
```

**Email Status Tracking:**
- ✅ Sent: Email delivered successfully
- ⏱️ Pending: Queued for sending
- ❌ Failed: Error sending (check logs)

### Recurring Invoices

> **Coming Soon**: Recurring invoices feature is planned for Phase 3.

For now, duplicate previous invoices:
1. Open invoice
2. Click **"Duplicate"**
3. Update dates and details
4. Generate new PDF

---

## Troubleshooting

### Common Issues

**Q: Invoice number is skipped (e.g., 001, 002, 005)**
- **A**: Draft invoices reserve numbers. Delete unused drafts to clean up sequence.

**Q: VAT calculation seems wrong**
- **A**: Check:
  - Your VAT number is entered correctly
  - Client VAT number (for reverse charge)
  - Default tax rate in settings
  - Line item tax rates

**Q: PDF generation fails or times out**
- **A**: Check:
  - Gotenberg service is running (`docker ps`)
  - Invoice has valid data (no empty required fields)
  - Check server logs for errors

**Q: Logo doesn't appear in PDF**
- **A**: Ensure:
  - Logo is uploaded (Settings → Invoice Branding)
  - File format is PNG, JPG, or SVG
  - File size < 5 MB
  - Logo URL is accessible from server

**Q: Currency symbol shows as "?" or wrong symbol**
- **A**: Currency is set correctly in invoice settings. Browser and PDF use Unicode symbols.

**Q: Client VAT number format invalid**
- **A**: VAT format varies by country:
  - DE: DE123456789 (9 digits)
  - NL: NL123456789B01 (12 characters)
  - FR: FRXX123456789 (11 digits)
  - See [VIES](https://ec.europa.eu/taxation_customs/vies/) for validation

**Q: Can't edit sent invoice**
- **A**: For legal compliance, sent invoices are locked. Options:
  - Cancel invoice and create new one
  - Create credit note (coming soon)
  - Contact support for special cases

### Error Messages

**"Invoice number already exists"**
- Duplicate invoice number detected
- Check existing invoices or reset numbering

**"Client VAT number invalid"**
- Format doesn't match country standards
- Use VIES validator or check format

**"PDF generation failed"**
- Server error during PDF creation
- Check Gotenberg logs and retry

**"Cannot modify finalized invoice"**
- Invoice status is Sent/Paid
- Create new invoice or credit note instead

---

## Best Practices

### For Freelancers

1. **Set up branding early** - Professional invoices build trust
2. **Use consistent numbering** - Start at 0001 and increment
3. **Track payment status** - Mark invoices as paid promptly
4. **Send invoices promptly** - Within 24 hours of work completion
5. **Include bank details** - Make it easy for clients to pay

### For Agencies

1. **Create client templates** - Save time on repeat clients
2. **Group by project** - Clear breakdown for clients
3. **Add detailed descriptions** - Transparency builds trust
4. **Use shorter payment terms** - Net 14 or Net 7 for better cash flow
5. **Automate reminders** - Don't chase payments manually

### Legal & Compliance

1. **Verify VAT numbers** - Use VIES database before invoicing
2. **Keep all invoices** - EU law requires 10 years retention
3. **Export for accounting** - Monthly export to accountant
4. **Document everything** - Keep records of all changes
5. **Use reverse charge correctly** - Avoid VAT penalties

---

## FAQ

**Can I customize invoice template design?**
- Basic customization (logo, colors, footer) is available now
- Full template editor coming in Phase 3

**Do I need Gotenberg for PDF generation?**
- Yes, Gotenberg is required for server-side PDF generation
- See [self-hosting docs](./SELF_HOSTING.md) for setup

**Can I import existing invoices?**
- Not yet, but you can manually create past invoices
- Set next invoice number to continue your sequence

**Are invoices stored securely?**
- Yes, all invoice data is encrypted at rest
- PDFs are stored in secure, non-public directory
- Access logs are maintained for GDPR compliance

**Can I invoice in multiple currencies?**
- Yes, select currency per invoice
- Default currency set in settings
- No automatic exchange rate conversion

**How do I handle refunds?**
- Credit notes feature coming in Phase 3
- For now, create negative invoice or manual adjustment

---

## Next Steps

- ✅ Set up your [invoice branding](../settings/invoice-branding)
- ✅ Configure [payment details](../settings/invoice-settings)
- ✅ Create your [first invoice](../invoices/create)
- 📖 Learn [keyboard shortcuts](./KEYBOARD_SHORTCUTS.md)
- 📖 Explore [API documentation](./API.md) for integrations

**Need Help?**
- [Contact Support](mailto:support@timeclocker.io)
- [Report Bug](https://github.com/yourorg/timeclocker/issues/new)
- [Request Feature](https://github.com/yourorg/timeclocker/discussions/new)

**Last Updated**: Phase 2 (November 2025)
