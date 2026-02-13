# 📚 Factory Resource & Inventory Management System
## Complete User Manual (v2.0)

**Version:** 2.0  
**Last Updated:** February 13, 2026  
**Currency:** AED (UAE Dirham)

---

## 📖 Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Dashboard Overview](#dashboard-overview)
4. [Goods Receipt Notes (GRN) - NEW](#goods-receipt-notes)
5. [Resources Management](#resources-management)
6. [Projects Management](#projects-management)
7. [Inventory Management](#inventory-management)
8. [Daily Inventory Reports](#daily-inventory-reports)
9. [Transaction History](#transaction-history)
10. [User Management](#user-management)
11. [Common Workflows](#common-workflows)
12. [Understanding the System Logic](#system-logic)
13. [Troubleshooting & FAQs](#troubleshooting-faqs)
14. [Glossary](#glossary)

---

## Introduction

### What's New in Version 2.0?

✨ **Multi-Item GRN System** - Receive multiple items in a single Goods Receipt Note  
✨ **Smart Unit Conversion** - Support for 40+ different units (sheet, piece, kg, meter, etc.)  
✨ **Direct Project Allocation** - Allocate items directly to projects when receiving goods  
✨ **Automatic Transaction Creation** - No manual commands needed - allocations happen instantly  
✨ **Project-Segregated Reports** - When viewing multiple projects, see separate inventory sections for each  
✨ **Smart Consumption Tracking** - Consumption at any project automatically reflects in system-wide inventory  
✨ **Enhanced Excel Export** - Download reports organized by project with automatic formatting  

### System Overview

The Factory Resource & Inventory Management System provides **real-time, accurate inventory tracking** across your central warehouse (Hub) and multiple construction projects.

**Key Features:**
- ✅ **Receive goods** from suppliers with automatic hub registration
- ✅ **Allocate materials** to specific projects (automatically removes from hub)
- ✅ **Track consumption** at project sites (removes items from system)
- ✅ **Transfer items** between projects without central hub involvement
- ✅ **Generate reports** showing inventory by project and system-wide
- ✅ **Audit trail** - complete transaction history for compliance

---

## Getting Started

### Accessing the System

1. **Open Your Web Browser**
   - Use Chrome, Firefox, Safari, or Edge
   - Type the system URL in the address bar
   - Example: `https://factory-resource-manager.local`

2. **Enter Your Credentials**
   - **Email:** Your email address
   - **Password:** Your password (case-sensitive)
   - Click **"Sign In"**

3. **You'll See the Dashboard**
   - The main navigation menu is on the left side
   - Your name appears in the top-right corner
   - The date and overall statistics are visible at the top

### Understanding the Interface

The left sidebar shows your main navigation options:
- 📊 **Dashboard** - Overview statistics
- 📦 **Goods Receipt Notes** - Receive materials
- 📋 **Resources** - View all materials in system
- 🏗️ **Projects** - Manage construction sites
- 📈 **Daily Inventory Report** - Generate inventory reports
- 📜 **Transaction History** - View all movements
- ⚙️ **Admin** - System settings (admin only)

---

## Dashboard Overview

The Dashboard displays:
- **Total Resources** - Number of different materials you track
- **Total Projects** - Number of active construction sites
- **Hub Inventory Value** - Current value of central warehouse stock
- **Outstanding Allocations** - Items allocated but not yet consumed

**What You See:**
- Quick access to create new GRN
- Recent activity summary
- Key inventory statistics
- Project status at a glance

---

## Goods Receipt Notes (GRN) - NEW SYSTEM

### What is a GRN?

A **Goods Receipt Note (GRN)** is a formal record of materials received from a supplier. In version 2.0, a single GRN can contain **multiple items** in different units, making the receiving process much faster.

### Creating a New GRN

**Step 1: Start the GRN**
- Navigate to **📦 Goods Receipt Notes** menu
- Click **"Create GRN"** button
- You'll see the GRN form

**Step 2: Fill Basic Information**

| Field | Description | Required |
|-------|-------------|----------|
| **Supplier** | Who are you buying from? | ✓ Yes |
| **Delivery Reference** | Supplier's delivery/invoice number | Optional |
| **Receipt Date** | When did goods arrive? | ✓ Yes |
| **Project** | Allocate directly to a project? | Optional |
| **Notes** | Any additional information | Optional |

**Important: The "Project" Field**
- **If LEFT EMPTY** → Items go to Central Hub (can allocate later)
- **If SELECTED** → Items are allocated directly to that project and immediately removed from Hub stock
- This saves time if you know which project needs the materials

**Step 3: Add Line Items**

Click **"Add Item"** to add line items to your GRN. For each item:

| Field | Description | Example |
|-------|-------------|---------|
| **Resource** | Select the material | "0.6MMX4X8 LAMINATE - SUD WHITE OAK" |
| **Quantity Received** | How much did you receive? | 100 |
| **Unit** | What unit is it in? | "sheet", "piece", "kg", "meter" |
| **Unit Price** | Price per unit | 123.00 AED |

**Available Units** (system automatically converts to base unit):
- **Count:** piece, dozen, unit, carton
- **Weight:** gram, kg, ton
- **Length:** cm, meter, mm, inch
- **Area:** sheet, m², hectare
- **Volume:** liter, gallon, m³

The system automatically converts all quantities to the resource's base unit for inventory calculations.

**Step 4: Review and Save**

Before saving:
- ✓ Verify all item quantities are correct
- ✓ Check that the supplier is correct
- ✓ Confirm the receipt date
- ✓ If selecting a project, ensure it's the right one

Click **"Create"** button to save the GRN.

### What Happens When You Save?

**Automatic Transaction Creation:**

When you create a GRN, the system **automatically creates inventory transactions** - no manual steps needed!

If **Project NOT Selected:**
```
1. GOODS_RECEIPT: Items arrive at Central Hub
   Example: +100 sheets to Hub
```

If **Project IS Selected:**
```
1. GOODS_RECEIPT: Items arrive at Central Hub
   Example: +100 sheets to Hub

2. ALLOCATION_IN: Items allocated to project
   Example: +100 sheets to Project A

3. ALLOCATION_OUT: Items removed from Hub
   Example: -100 sheets from Hub
```

**Result:**
- Hub inventory increased, then decreased (no net change if allocated)
- Project inventory increased
- Notifications show: "✅ GRN Created & Resources Allocated! 100 sheet → Project A"

### Editing or Deleting a GRN

**You Cannot Edit:** Once created, GRNs cannot be edited (ensures audit trail integrity)

**If You Made a Mistake:**
1. Contact your administrator
2. They can delete the GRN and all associated transactions
3. Create a new corrected GRN

### GRN Status Tracking

Each GRN shows:
- ✅ **GRN Number** - Unique identifier (e.g., GRN-2026-00001)
- 📅 **Receipt Date** - When items arrived
- 📦 **Line Items** - Number of different items received
- 🏗️ **Project** - Which project (if allocated directly)
- 👤 **Created By** - Who registered the GRN

---

## Resources Management

### What is a Resource?

A **Resource** is any material you track in the system. Examples:
- Construction materials (cement, steel, sand)
- Tools (drills, saws, levels)
- Equipment (temporary site facilities)
- Consumables (paint, nails, wire)

### Viewing All Resources

1. Navigate to **📋 Resources**
2. You'll see a list of all materials in the system
3. Each shows:
   - Item Code (SKU)
   - Description
   - Category
   - Base Unit
   - Current Hub Stock
   - Unit Price

### Creating a New Resource

Only administrators can create resources. If you need to add a new material:

1. Ask your administrator
2. They'll add it with:
   - Item Code (e.g., STL-001)
   - Full Description
   - Category (e.g., "Steel", "Lumber")
   - Base Unit (e.g., "kg", "meter", "sheet")
   - Unit Price (for costing)

### Understanding Base Units

Every resource has a **base unit** - the standard unit for calculations:
- When you receive items in different units, they're converted to the base unit
- All inventory calculations use the base unit
- Reports show both original units and base units

**Example:**
```
Resource: Steel Rebar
Base Unit: kg

You receive:
- 500 pieces → automatically converts to kg
- 2 rolls → if 1 roll = 50 kg, converts to 100 kg
- Total hub inventory: 600 kg
```

---

## Projects Management

### What is a Project?

A **Project** is a construction site where materials are used. The system tracks inventory separately for each project.

### Viewing Projects

1. Navigate to **🏗️ Projects**
2. You'll see:
   - Project name and code
   - Location
   - Status
   - Current resources at that project
   - Total project inventory value

### Creating a Project

1. Click **"Create Project"** button
2. Enter:
   - **Project Name** (e.g., "Downtown Office Complex")
   - **Project Code** (e.g., "PRJ-DOC-2026-001")
   - **Location** (e.g., "Downtown Dubai")
   - **Description** (optional)
3. Click **"Save"**

### Project Inventory View

Click on any project to see:
- **Resources at Project Site** - All materials currently at this project with quantities
- **Total Inventory Value** - AED value of all materials
- **Recent Allocations** - Latest items received
- **Consumption History** - Materials used recently

---

## Inventory Management

### How Inventory Works

The system tracks inventory at **two locations:**

**1. Central Hub (Warehouse)**
- Your main storage facility
- Items arrive here when you receive goods
- Items are removed when allocated to projects or consumed

**2. Projects (Construction Sites)**
- Materials allocated to specific projects
- Items are removed when consumed at the project
- Can transfer to other projects without returning to hub

### Allocating Materials to Projects

If you received goods to the Hub (didn't select project), you can allocate them later:

1. Navigate to **📦 Goods Receipt Notes**
2. Find the GRN you want to allocate from
3. Click **"Allocate"** button
4. Select:
   - **Resource** - Which material
   - **Quantity** - How much to allocate
   - **Project** - Where to send it
5. Click **"Allocate"**

**What Happens:**
- Quantity automatically removed from Hub
- Quantity added to selected Project
- Two transactions created instantly
- Notification confirms: "✅ Allocated 100 sheets from Hub to Project A"

### Recording Consumption

When materials are used at a project:

1. Navigate to **📦 Goods Receipt Notes** or use the **Transaction** menu
2. Click **"Record Consumption"**
3. Enter:
   - **Project** - Where was it consumed
   - **Resource** - What was used
   - **Quantity** - How much
   - **Date** - When was it used
   - **Reason** (optional) - "Normal use", "Waste", "Testing", etc.
4. Click **"Record"**

**Important:** Consumption removes items from the **entire system** (both project and hub total inventory decreases)

### Transferring Between Projects

To move materials from one project to another:

1. Navigate to **Transaction History** 
2. Click **"New Transfer"**
3. Enter:
   - **Source Project** - Where it's coming from
   - **Resource** - What to transfer
   - **Quantity** - How much
   - **Destination Project** - Where it's going
4. Click **"Transfer"**

**What Happens:**
- Material removed from source project
- Material added to destination project
- Hub inventory unchanged (internal transfer)

---

## Daily Inventory Reports

### Why Generate Reports?

Reports give you **snapshot views** of inventory at any point in time. Use them for:
- ✓ Daily reconciliation
- ✓ Project planning
- ✓ Cost tracking
- ✓ Audit documentation
- ✓ Supplier reconciliation

### Generating a System-Wide Report

A **System-Wide Report** shows the **entire company's inventory** combining all projects and the hub:

**Steps:**
1. Navigate to **📊 Daily Inventory Report**
2. Select **Report Date** (which day to report on)
3. **Leave "Filter by Projects" EMPTY**
4. Click **"Generate Report"**

**What You See:**
```
Total Items: 150
Opening Value: AED 45,200
In Value: AED 12,000 (goods received today)
Out Value: AED 8,500 (consumed today)
Closing Value: AED 48,700
```

**Column Meanings:**
- **Opening Qty** - How much we had at start of day
- **In Qty** - How much was received (GOODS_RECEIPT or PURCHASE)
- **Out Qty** - How much was consumed (removed from system)
- **Closing Qty** - How much we have at end of day = Opening + In - Out

**Important:** System-wide reports:
- ✓ Include consumption from ANY project (removes from total)
- ✓ EXCLUDE internal allocations (project transfers don't affect total)
- ✓ Show real additions and removals only

### Generating Project-Specific Reports

A **Project-Specific Report** shows inventory for one or more projects:

**Steps:**
1. Navigate to **📊 Daily Inventory Report**
2. Select **Report Date**
3. Select **one or more projects** in "Filter by Projects"
4. Click **"Generate Report"**

**What Changes:**
- If you select **1 project** → Shows that project's inventory flat list
- If you select **multiple projects** → Shows **separate sections** for each project

### Multi-Project Reports - NEW Feature

When generating reports for 2+ projects, each project is shown in its **own section**:

```
📍 Downtown Office Complex
├─ Opening Value: AED 10,200
├─ In Value: AED 5,000
├─ Out Value: AED 3,200
├─ Closing Value: AED 12,000
└─ Items Table:
   • 0.6MMX4X8 LAMINATE: 213 sheets
   • WATER PUMP: 2 pieces
   └─ Downtown Totals

[Blank Line]

📍 Data Center Facility
├─ Opening Value: AED 35,000
├─ In Value: AED 7,000
├─ Out Value: AED 5,300
├─ Closing Value: AED 36,700
└─ Items Table:
   • 0.6MMX4X8 LAMINATE: 100 sheets
   • FIRE RATED CORE: 50 sheets
   └─ Data Center Totals
```

**Benefits:**
- ✓ Clear visibility: "Downtown has 213 sheets, Data Center has 100 sheets"
- ✓ Can't confuse inventories
- ✓ Perfect for multi-site reconciliation
- ✓ Better for project-specific costing

### Downloading Reports

After generating a report:

1. Click **"Download Excel"** button
2. A CSV file downloads to your computer
3. Open in Excel, Google Sheets, or Numbers
4. All formatting and calculations are preserved

**Excel Includes:**
- All columns: Code, Description, Opening, In, Out, Closing, Value
- Proper number formatting (2 decimal places)
- Subtotals by project (if multi-project report)
- Grand totals at bottom
- UTF-8 encoding for special characters

---

## Transaction History

### What are Transactions?

A **Transaction** records every movement of material in the system. You cannot edit or delete transactions (for audit integrity).

### Transaction Types

The system records these transaction types:

| Type | Direction | Meaning | Example |
|------|-----------|---------|---------|
| **GOODS_RECEIPT** | IN ⬆️ | Items received from supplier | Goods arriving at Hub |
| **ALLOCATION_IN** | IN ⬆️ | Items allocated to a project | Materials sent to Project A |
| **ALLOCATION_OUT** | OUT ⬇️ | Items removed from hub during allocation | Materials leave Hub |
| **CONSUMPTION** | OUT ⬇️ | Items used/removed at project | 50 sheets used at Project A |
| **DIRECT_CONSUMPTION** | OUT ⬇️ | Items used directly at hub | Testing material at warehouse |
| **TRANSFER_IN** | IN ⬆️ | Items received from another project | Materials from Project A arrive |
| **TRANSFER_OUT** | OUT ⬇️ | Items sent to another project | Materials sent to Project B |
| **PURCHASE** | IN ⬆️ | Direct purchase to hub | Old system transactions |

### Viewing Transaction History

1. Navigate to **📜 Transaction History**
2. You'll see all transactions with:
   - Date and time
   - Resource name
   - Transaction type (color-coded)
   - Quantity and unit price
   - Project (if applicable)
   - User who recorded it

### Transaction Details

Click on any transaction to see:
- Complete information
- Transaction date and time
- Resource and quantity
- Associated GRN number (if from GRN)
- User notes
- Created by (which user)

### What You Cannot Do

- ❌ Edit transactions (immutable record)
- ❌ Delete individual transactions
- ❌ Manually change quantities

**If there's an error:**
- Contact your administrator
- They can delete the GRN (which deletes all associated transactions)
- Create a corrected GRN

---

## User Management

### Accessing User Settings

1. Click your **name** in top-right corner
2. Select **"Profile Settings"** or **"Account"**
3. You can update:
   - Name
   - Email
   - Password
   - Profile photo

### Changing Your Password

1. Go to **Profile Settings**
2. Click **"Change Password"**
3. Enter:
   - Current password
   - New password
   - Confirm new password
4. Click **"Update"**

**Password Requirements:**
- At least 8 characters
- Mix of uppercase and lowercase
- At least one number
- At least one special character (!@#$%^&*)

### Admin Functions (Administrators Only)

Administrators can:
- ⚙️ Create new user accounts
- ⚙️ Assign user roles
- ⚙️ Reset passwords
- ⚙️ Delete accounts
- ⚙️ Create resources
- ⚙️ Delete GRNs (with all associated transactions)
- ⚙️ View audit logs

Access these from **⚙️ Admin** menu.

---

## Common Workflows

### Workflow 1: Receive Goods to Hub (Then Allocate Later)

**Scenario:** You receive materials from a supplier, but don't know which project they'll go to yet.

**Steps:**
1. Go to **📦 Goods Receipt Notes**
2. Click **"Create GRN"**
3. Fill:
   - Supplier: "Abdul Hamid Trading"
   - Receipt Date: Today
   - **Project: Leave EMPTY**
4. Add items:
   - Resource: "0.6MMX4X8 LAMINATE"
   - Quantity: 500
   - Unit: sheet
5. Click **"Create"**
6. System creates **1 transaction**: GOODS_RECEIPT to Hub

**Later, When Project is Ready:**
1. Go to **📦 Goods Receipt Notes**
2. Find the GRN
3. Click **"Allocate"**
4. Select:
   - Resource: Same material
   - Quantity: 300 (allocate part of it)
   - Project: "Downtown Office Complex"
5. System creates **2 transactions**:
   - ALLOCATION_IN: 300 to Project
   - ALLOCATION_OUT: 300 from Hub

**Result:**
- Hub has 200 sheets remaining
- Project has 300 sheets

---

### Workflow 2: Receive and Allocate Directly

**Scenario:** You get a delivery that's meant for a specific project.

**Steps:**
1. Go to **📦 Goods Receipt Notes**
2. Click **"Create GRN"**
3. Fill:
   - Supplier: "Global Supplies Ltd"
   - Receipt Date: Today
   - **Project: "Project A Construction"** ← Select project
4. Add items:
   - Resource: "FIRE RATED CHIPBOARD CORE"
   - Quantity: 100
   - Unit: sheet
   - Unit Price: 85.00
5. Click **"Create"**
6. System automatically creates **3 transactions**:
   - GOODS_RECEIPT: 100 to Hub
   - ALLOCATION_IN: 100 to Project A
   - ALLOCATION_OUT: 100 from Hub
7. Get notification: "✅ GRN Created & Resources Allocated! 100 sheet → Project A"

**Result:**
- Hub: No change (received then allocated out)
- Project A: +100 sheets immediately

---

### Workflow 3: Track Daily Consumption

**Scenario:** Each day, project site supervisors record materials used.

**Steps**:
1. Go to **📦 Goods Receipt Notes**
2. Click **"New Consumption Record"**
3. Fill:
   - Date: Today
   - Project: "Project A"
   - Resource: "0.6MMX4X8 LAMINATE"
   - Quantity Used: 50
   - Unit: sheet (automatically matches resource unit)
   - Reason: "Normal construction use"
4. Click **"Record"**
5. System creates **1 transaction**: CONSUMPTION (-50)

**Result:**
- Project A inventory: -50 sheets
- Hub inventory: Unchanged (not involved)
- System-wide total: -50 sheets (removed from system)
- Can see in reports: "Out Qty: 50"

---

### Workflow 4: Daily Inventory Report for Management

**Scenario:** Manager wants daily inventory snapshot for reporting.

**Steps:**
1. Go to **📊 Daily Inventory Report**
2. Select: Report Date = Today
3. Leave projects empty (system-wide)
4. Click **"Generate Report"**
5. Review:
   - Total items: 150
   - Opening Value: AED 45,200
   - Items received: 5,000 AED
   - Items consumed: 3,500 AED
   - Closing Value: AED 46,700
6. Click **"Download Excel"** to save

**Result:** Excel file ready to send to finance team

---

### Workflow 5: Compare Multiple Projects

**Scenario:** You manage 3 projects and need to see inventory at each simultaneously.

**Steps:**
1. Go to **📊 Daily Inventory Report**
2. Select: Report Date = Today
3. Select projects:
   - ✓ Downtown Office Complex
   - ✓ Data Center Facility
   - ✓ Shopping Mall Renovation
4. Click **"Generate Report"**
5. System displays:
   ```
   📍 Downtown Office Complex
   [Items and totals]
   
   📍 Data Center Facility
   [Items and totals]
   
   📍 Shopping Mall Renovation
   [Items and totals]
   ```
6. Click **"Download Excel"** → Each project in separate section

**Result:** Clear view of who has what, perfect for resource planning

---

## Understanding the System Logic

### The Three-Tier Transaction System

Version 2.0 uses a **three-tier system** for allocating goods to projects:

**TIER 1: Receipt at Hub**
```
GOODS_RECEIPT transaction
Items: 100 sheets
Location: Central Hub
Quantity: +100 (adds to hub total)
Result: Hub now has these items
```

**TIER 2: Allocation to Project**
```
ALLOCATION_IN transaction
Items: 100 sheets
Location: Project A
Quantity: +100 (adds to project)
Result: Project A now has these items
```

**TIER 3: Removal from Hub**
```
ALLOCATION_OUT transaction
Items: 100 sheets
Location: Central Hub
Quantity: -100 (removes from hub)
Result: Hub no longer has these items
```

**Why Three Tiers?**
- ✓ Complete audit trail
- ✓ Can see exact timing of allocation
- ✓ Can track if allocation was intentional
- ✓ Can calculate hub stock at any point in time

### System-Wide vs Project-Specific Inventory

**System-Wide Inventory** (Hub Total)
- Includes ONLY real additions: GOODS_RECEIPT, PURCHASE
- Includes ONLY real removals: CONSUMPTION, DIRECT_CONSUMPTION
- **Excludes allocations** (internal transfers don't change system total)
- Formula: Hub = Received - Consumed

**Project-Specific Inventory**
- Includes: ALLOCATION_IN, TRANSFER_IN (what arrives)
- Includes: ALLOCATION_OUT, TRANSFER_OUT, CONSUMPTION (what leaves)
- Formula: Project = Allocated_In + Transferred_In - Consumed - Transferred_Out - Allocated_Out

**Example:**
```
Day 1: Receive 1000 sheets to Hub
  Hub: 1000 sheets ✓
  System-wide report shows: In: 1000 sheets

Day 2: Allocate 600 sheets to Project A, 400 to Project B
  Hub: 0 sheets (all allocated)
  Project A: 600 sheets
  Project B: 400 sheets
  System-wide report: Still shows In: 1000 (allocations don't count)

Day 3: Project A uses 100 sheets
  Hub: 0 sheets
  Project A: 500 sheets
  Project B: 400 sheets
  System-wide report shows: Out: 100 sheets (consumption reduces total)
```

### Understanding Reports

**System-Wide Report Shows:**
- Real material flow into company (received from suppliers)
- Real material flow out of company (consumed by projects)
- DOES NOT show internal transfers (hub ↔ project movements)

**Project Report Shows:**
- What a project received (allocation)
- What a project used (consumption)
- What was transferred (to/from other projects)
- INCLUDES all movements including internal transfers

---

## Troubleshooting & FAQs

### "The closing quantity is zero, but I didn't consume everything!"

**Possible Causes:**
1. You selected a **project to report on** but the consumption was **at a different project**
2. You're looking at a **system-wide report** and expected to see project-specific inventory

**Solution:**
- For system-wide: Report shows total company inventory only
- For project-specific: Select the correct project in the filter
- Check transaction history to see exactly where consumption was recorded

---

### "GRN shows for one project but I need to allocate to a different project"

**This Cannot Be Changed**

Once a GRN is created with a project, the allocation is locked. 

**Solution:**
Contact your admin to:
1. Delete the GRN (removes all associated transactions)
2. Create a new GRN with the correct project selected

---

### "I don't see consumption in the system-wide report"

**Possible Reason:**
Consumption IS in the report, but in the "Out Qty" column, which reduces your closing balance.

**Check This:**
- Look for "Out Qty" column (red colored)
- If showing 50, that means 50 units were consumed
- Closing = Opening + In - Out

**If "Out Qty" is 0:**
- Consumption was not recorded yet, OR
- Consumption was recorded for a specific project (which is correct)

---

### "Can I transfer materials between projects?"

**Yes**, but it works differently from allocation:

**Allocation** (Hub → Project):
- Takes from central hub
- Removes from hub stock

**Transfer** (Project → Project):
- Takes from one project
- Gives to another project  
- Hub unaffected
- Good for sharing materials between nearby sites

**Steps:**
1. Go to **Transaction History**
2. Click **"Record Transfer"**
3. Source Project: Where it comes from
4. Destination Project: Where it goes
5. Quantity and resource

---

### "Excel download failed when I selected multiple projects"

**Fixed in Version 2.0!** The export now properly handles multi-project reports.

**If still having issues:**
1. Refresh the page
2. Try downloading again
3. If persists, contact your admin with:
   - Date of report
   - Which projects were selected
   - Error message received

---

### "How do I know if a resource is running low?"

**Currently:** Check the resource page manually

**Best Practice:**
1. Go to **📋 Resources**
2. Look at "Current Hub Stock" column
3. Compare to typical monthly usage
4. Alert supervisor if below 1 month's supply

**Future Feature:** System will have low-stock alerts coming soon.

---

### "Can I edit or delete transactions?"

**No.** By design:
- ✓ Ensures audit trail integrity
- ✓ Prevents accidental data loss
- ✓ Maintains compliance records

**If there's an error:**
Contact administrator to:
1. Delete the GRN (removes GRN + all 3 transactions)
2. Create corrected GRN

---

### "How far back can I generate reports?"

System allows reports for any historical date.

**Best Practice:**
- Monthly reconciliation: 1st day of month
- Weekly reports: Every Sunday
- Daily reports: Every evening

---

### "What if I receive goods with the wrong unit?"

Don't worry! The system converts units automatically:

1. Create GRN with whatever unit was on delivery
2. System automatically converts to the resource's **base unit**
3. Reports always show base unit
4. Transaction history shows both original and base units

**Example:**
```
Received: 2 rolls of steel
Unit: roll
System: If 1 roll = 50 kg, converts to 100 kg automatically
```

---

## Glossary

| Term | Definition |
|------|-----------|
| **Hub/Central Hub** | Your main warehouse where all received goods are initially stored |
| **GRN** | Goods Receipt Note - formal document recording material arrival from supplier |
| **Allocation** | Moving materials from Hub to a Project |
| **Consumption** | Using materials at a project or hub (removes from system) |
| **Transaction** | Record of any material movement (cannot be edited) |
| **Base Unit** | Standard unit for a resource (all calculations use this) |
| **System-Wide Inventory** | Total company inventory (all hubs and projects combined) |
| **Project Inventory** | Materials specifically at one construction site |
| **Transfer** | Moving materials between projects (doesn't include hub) |
| **Unit Conversion** | Automatic conversion between different units (kg, sheet, piece, etc.) |
| **Closing Balance** | Quantity at end of day = Opening + In - Out |
| **Audit Trail** | Complete history of all transactions for compliance |

---

## System Architecture (For Administrators)

### Database Transactions

All GRN creations use **database transactions** for reliability:
1. GRN record created
2. Line items created
3. All inventory transactions created
4. All committed together or all rolled back if error

### Automatic Transaction Creation

Uses **DB::afterCommit()** callback:
- Ensures all database operations complete first
- Creates transactions after GRN committed
- No manual command lines needed
- Instant feedback via notifications

### Unit Conversion Table

System supports automatic conversion between:
- Weight: gram ↔ kg ↔ ton
- Length: mm ↔ cm ↔ meter ↔ inch
- Area: m² ↔ hectare
- Volume: liter ↔ gallon ↔ m³
- Count: piece ↔ dozen ↔ carton
- Plus many more...

---

## Technical Support

### Getting Help

**In-System Issues:**
- Check this manual first
- Review Transaction History for details
- Contact your administrator with:
  - What you were trying to do
  - What error you received
  - When it happened
  - Screenshots

**System Administrator:**
- Can reset passwords
- Can delete GRNs (with audit)
- Can manage users
- Can create resources
- Can download system logs

**Emergency Issues:**
- System down?
- Cannot login?
- Critical data error?
- Notify administrator immediately

---

## Version History

**Version 2.0** (February 2026)
- ✨ Multi-item GRN system
- ✨ Smart unit conversion
- ✨ Direct project allocation
- ✨ Project-segregated reports
- ✨ Automatic transaction creation
- 🔧 Fixed consumption reporting
- 🔧 Enhanced Excel export

**Version 1.0** (January 2026)
- Initial release
- Basic GRN system
- Single-item transactions
- Manual transaction creation

---

**© 2026 Factory Resource Management System**  
**All Rights Reserved**

For questions or support, contact your system administrator.
