# 📚 Factory Resource & Inventory Management System
## Complete User Manual

**Version:** 1.0  
**Last Updated:** January 31, 2026  
**Currency:** AED (UAE Dirham)

---

## 📖 Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Dashboard Overview](#dashboard-overview)
4. [Resources Management](#resources-management)
5. [Projects Management](#projects-management)
6. [Transaction History](#transaction-history)
7. [User Management](#user-management)
8. [Reports & Exports](#reports-exports)
9. [Common Workflows](#common-workflows)
10. [Troubleshooting & FAQs](#troubleshooting-faqs)
11. [Glossary](#glossary)

---

## Introduction

### What is This System?

The Factory Resource & Inventory Management System is a web-based application designed to help you track and manage construction materials and resources across multiple projects. Think of it as a digital warehouse manager that knows exactly where every bag of cement, every steel rod, and every tool is located at any given time.

### What Can It Do?

✅ **Track Inventory** - Know exactly how much of each material you have  
✅ **Manage Projects** - Organize materials by construction projects  
✅ **Record Purchases** - Keep track of what you buy and how much it costs  
✅ **Allocate Materials** - Assign materials from your warehouse to project sites  
✅ **Track Consumption** - Record how much material is used each day  
✅ **Transfer Between Projects** - Move materials from one project to another  
✅ **Generate Reports** - Download Excel reports for accounting and auditing  
✅ **Multi-User Access** - Multiple people can use the system with different permissions

### Who Should Use This System?

- **Project Managers** - Track material usage across projects
- **Warehouse Managers** - Manage central inventory
- **Accountants** - Generate cost reports and financial summaries
- **Site Supervisors** - Record daily material consumption
- **Administrators** - Manage users and system settings

### Key Concepts to Understand

Before you start, let's understand three important concepts:

**1. The Hub (Central Warehouse)**
- This is your main storage location
- When you purchase materials, they arrive at the Hub
- Materials stay at the Hub until you allocate them to a project

**2. Projects (Construction Sites)**
- These are your active construction projects
- Materials are allocated from the Hub to projects
- Each project has its own inventory

**3. Transactions (Movement History)**
- Every material movement is recorded as a transaction
- Transactions cannot be edited or deleted (for audit purposes)
- You can always see the complete history of any material

---

## Getting Started

### Accessing the System

1. **Open Your Web Browser**
   - Use Chrome, Firefox, Safari, or Edge
   - Type the system URL in the address bar (provided by your administrator)
   - Example: `https://spacebuilderinv.com`

2. **You'll See the Login Page**
   - The page will show a login form
   - If you're already logged in, you'll go straight to the dashboard

### Logging In

1. **Enter Your Credentials**
   - **Email Address:** The email your administrator gave you
   - **Password:** Your personal password (case-sensitive)

2. **Click "Sign In"**
   - If your credentials are correct, you'll see the Dashboard
   - If not, you'll see an error message - double-check your email and password

3. **First Time Login?**
   - Your administrator will provide your initial password
   - You should change it after first login (see User Management section)

### Understanding Your Role

There are two types of users:

**👤 Regular User**
- Can view and manage resources
- Can view and manage projects
- Can record transactions
- Can generate reports
- **Cannot** manage other users

**👨‍💼 Administrator (Admin)**
- Has all Regular User permissions
- **Plus:** Can create and manage other users
- **Plus:** Can access system settings

💡 **How to check your role:** Look at the left sidebar. If you see a "Users" menu item, you're an Admin.

---

## Dashboard Overview

When you log in, the first screen you see is the **Dashboard**. This is your control center.

### Dashboard Layout

The dashboard has three main areas:

#### 1. Top Navigation Bar
- **Left Side:** System logo and name
- **Center:** Current page name
- **Right Side:** 
  - 🔔 Notifications (bell icon)
  - 👤 Your profile (click to logout)
  - 🌙 Dark mode toggle (switch between light/dark theme)

#### 2. Left Sidebar (Main Menu)

The sidebar contains all your navigation options:

**📊 Dashboard**
- Your home page with overview statistics

**📦 Inventory Management**
- **Resources** - View and manage all materials
- **Transaction History** - See all inventory movements

**🏗️ Project Management**
- **Projects** - View and manage construction projects

**⚙️ Administration** (Admin only)
- **Users** - Manage system users

**❓ Help Center** (if available)
- Quick help and FAQs

#### 3. Main Content Area

This shows different content depending on which menu you selected:

**Six Statistics Cards:**

1. **Hub Inventory Value**
   - Shows total value of materials in your central warehouse
   - In AED (UAE Dirham)
   - Green color
   - 📦 Warehouse icon

2. **Allocated Inventory**
   - Shows total value of materials at all project sites
   - In AED
   - Blue color
   - 🚚 Truck icon

3. **Active Projects**
   - Number of currently active projects
   - Shows completed and total projects below
   - Orange color
   - 💼 Briefcase icon

4. **Total Resources**
   - Number of different material types in your catalog
   - Blue color
   - 📦 Cube icon

5. **Today's Transactions**
   - Number of inventory movements today
   - Shows this week's count below
   - Blue color
   - 📈 Chart icon

6. **Total Transactions**
   - All-time count of inventory movements
   - Gray color
   - 📄 Document icon

### What the Statistics Tell You

**Healthy System Indicators:**
- Hub Inventory Value is positive (you have stock)
- Active Projects matches your actual projects
- Today's Transactions shows activity

**Warning Signs:**
- Hub Inventory Value is very low (need to purchase materials)
- No transactions today when work is ongoing (people aren't updating the system)

---

## Resources Management

Resources are the materials, tools, and equipment you track in the system. Each resource represents a type of item (like "Portland Cement" or "Steel Reinforcement Bar").

### Viewing Resources

1. **Click "Resources" in the Left Sidebar**
   - Under "Inventory Management" section
   - You'll see a table of all resources

2. **The Resources Table Shows:**

   **Columns:**
   - **SKU** - Stock Keeping Unit (unique code for the item)
   - **Name** - Descriptive name (e.g., "Portland Cement 50kg Bag")
   - **Category** - Type of material (Raw Materials, Tools, Equipment, etc.)
   - **Base Unit** - How it's measured (kg, liters, pieces, etc.)
   - **Hub Stock** - Current quantity at central warehouse
   - **Total Stock** - Total quantity (warehouse + all projects)
   - **Created At** - When this resource was added to the system

3. **Table Features:**

   **Search:** Type in the search box at the top to find resources
   - Searches SKU, name, and category
   - Example: Type "cement" to find all cement products

   **Sort:** Click any column header to sort
   - Click once: Sort ascending (A-Z, 0-9)
   - Click again: Sort descending (Z-A, 9-0)

   **Filter:** Click "Filter" button to filter by:
   - Category (select one or more)
   - Stock status (in stock, low stock, out of stock)
   - Date range (when created)

   **Columns Toggle:** Click "Toggle Columns" to show/hide columns

   **Pagination:** 
   - Bottom right shows "Showing 1-10 of 25"
   - Use ← → arrows to navigate pages
   - Click numbers to jump to specific pages

### Creating a New Resource

**When to create a resource:**
- You've purchased a new type of material you haven't tracked before
- You want to add a new tool or equipment to inventory

**Step-by-Step Process:**

1. **Click "New Resource" Button**
   - Top right of the Resources page
   - Green button with "+" icon

2. **Fill in the Form - Section 1: Basic Information**

   **Name** (Required)
   - Enter a clear, descriptive name
   - Example: "Portland Cement 50kg Bag"
   - Example: "Steel Reinforcement Bar 16mm"
   - ⚠️ Be specific - include size, grade, or specifications

   **SKU** (Required)
   - Stock Keeping Unit - a unique identifier
   - Can be numbers, letters, or both
   - Example: "CEM-P-50", "STEEL-16MM", "001"
   - ⚠️ Must be unique - system won't allow duplicates
   - 💡 Tip: Use a consistent naming scheme

   **Category**
   - Select from dropdown:
     - **Raw Materials** - Cement, sand, gravel, steel, etc.
     - **Tools** - Hammers, drills, measuring tools
     - **Equipment** - Scaffolding, mixers, generators
     - **Consumables** - Gloves, safety gear, cleaning supplies
     - **Others** - Anything that doesn't fit above
   - 💡 This helps you organize and find items later

   **Base Unit** (Required)
   - How this item is measured
   - Select from dropdown with 45+ options:

   **Weight Units:**
   - Kilograms (kg) - Most common for construction materials
   - Grams (g) - Small items
   - Milligrams (mg) - Very small quantities
   - Metric Tons (ton) - Very large quantities
   - Pounds (lb) - Imperial weight
   - Ounces (oz) - Imperial small weight

   **Volume Units:**
   - Liters (L) - Liquids like paint, oil
   - Milliliters (ml) - Small liquid quantities
   - Gallons (gal) - Imperial liquid volume
   - Cubic Meters (m³) - Large volumes

   **Length Units:**
   - Meters (m) - Cables, pipes, rods
   - Centimeters (cm) - Small lengths
   - Millimeters (mm) - Precision measurements
   - Kilometers (km) - Very long distances
   - Feet (ft) - Imperial length
   - Inches (in) - Imperial small length

   **Area Units:**
   - Square Meters (m²) - Tiles, flooring
   - Square Feet (ft²) - Imperial area
   - Square Centimeters (cm²) - Small areas

   **Count Units:**
   - Pieces - Individual countable items
   - Boxes - Packaged items
   - Bags - Bagged materials
   - Rolls - Cable, wire, fabric
   - Sheets - Plywood, metal sheets

   **Other Units:**
   - Pairs - Gloves, boots
   - Sets - Tool sets
   - Bundles - Bundled materials
   - Pallets - Large deliveries
   - Drums - Large containers
   - Cartons - Boxed goods

   ⚠️ **Important:** Choose carefully! This cannot be changed later without administrator help.

   **Description** (Optional)
   - Add any additional details
   - Example: "High-grade Portland cement, suitable for structural work"
   - Example: "16mm diameter steel bars, 12-meter length"

3. **Click "Create" Button**
   - Bottom right of the form
   - Green button

4. **Success!**
   - You'll see a green notification: "Resource created successfully"
   - The new resource appears in your resources list
   - Initial Hub Stock will be 0.00 (add stock through Purchase action)

### Purchasing Materials (Adding Stock)

When you buy materials from a supplier, you record it as a **Purchase**. This adds stock to your Hub (central warehouse).

**Step-by-Step Purchase Process:**

1. **Find the Resource**
   - Go to Resources page
   - Use search or scroll to find the item you purchased

2. **Click the Resource Row**
   - Click anywhere on the row to open the detail view
   - OR click the eye icon (👁️) on the right

3. **Click "Purchase" Button**
   - Top right of the page
   - Green shopping cart icon 🛒

4. **Fill in the Purchase Form**

   **Quantity** (Required)
   - Enter how much you purchased
   - Example: 1000 (if you bought 1000kg of cement)
   - Must be greater than 0
   - Can include decimals: 1500.50

   **Unit Price** (Required)
   - Cost per unit in AED
   - Example: If cement costs 25 AED per bag, enter 25
   - Example: If it costs 15.50 AED per kg, enter 15.50
   - Can include decimals for precise pricing

   **Purchase Date** (Required)
   - When did you buy this?
   - Click the calendar icon to select
   - Default is today's date
   - Cannot select future dates

   **Supplier** (Optional)
   - Name of the company you bought from
   - Example: "ABC Construction Supplies"
   - Example: "XYZ Hardware Store"
   - 💡 Helpful for tracking which suppliers you use

   **Reference/Invoice Number** (Optional)
   - Your purchase order number or invoice number
   - Example: "PO-2026-001"
   - Example: "INV-12345"
   - 💡 Helpful for matching with accounting records

   **Notes** (Optional)
   - Any additional information
   - Example: "Delivered in 20 bags of 50kg each"
   - Example: "Special discount applied"

5. **Review the Preview**
   - The form shows a summary:
     - **Quantity:** What you entered
     - **Unit Price:** What you entered
     - **Total Value:** Automatically calculated (Quantity × Unit Price)
   - Example: 1000 kg × 25 AED = 25,000 AED

6. **Click "Purchase" Button**
   - Bottom of the form
   - Green button

7. **Success!**
   - Green notification: "Purchase recorded successfully"
   - Hub Stock increases by the quantity you purchased
   - Transaction is recorded in Transaction History

**Example Purchase Scenario:**

You bought 2000 kg of cement for 20 AED per kg on January 15, 2026:
- **Quantity:** 2000
- **Unit Price:** 20
- **Purchase Date:** January 15, 2026
- **Supplier:** "National Cement Co."
- **Total Value:** 40,000 AED (calculated automatically)

After purchase, the cement's Hub Stock increases from (let's say) 500 kg to 2500 kg.

### Purchasing with Unit Conversion

Sometimes you buy materials in a different unit than you usually track them.

**Example:** You track cement in kilograms (kg), but you buy it in metric tons.

**The system can convert automatically!**

**Step-by-Step with Conversion:**

1. **Click "Purchase" Button** (same as regular purchase)

2. **Select Purchase Unit**
   - New dropdown appears: "Purchase Unit"
   - Shows compatible units
   - Example: If base unit is "kg", you can select "ton", "g", "lb", etc.

3. **Enter Quantity in Purchase Unit**
   - Example: 2 (if you bought 2 tons)

4. **See Automatic Conversion**
   - System shows: "This equals 2000.00 kg" (base unit)
   - Conversion factor is displayed
   - Example: 1 ton = 1000 kg

5. **Enter Unit Price**
   - ⚠️ Price is per purchase unit (not base unit)
   - Example: 15,000 AED per ton (not per kg)

6. **Review Total**
   - System calculates: 2 tons × 15,000 AED = 30,000 AED

7. **Click "Purchase"**
   - Stock increases by converted amount (2000 kg)
   - Price history stores both units

**Available Conversions:**

The system knows how to convert between:
- **Weight:** mg ↔ g ↔ kg ↔ ton ↔ lb ↔ oz
- **Volume:** ml ↔ liter ↔ gallon ↔ m³
- **Length:** mm ↔ cm ↔ m ↔ km ↔ inch ↔ ft
- **Area:** cm² ↔ m² ↔ ft²

💡 **Tip:** Always double-check the conversion preview before confirming!

### Allocating Materials to Projects

**Allocation** means moving materials from your Hub (central warehouse) to a project site.

**When to allocate:**
- Project is starting and needs materials
- Project is running low on materials
- You want to reserve materials for a specific project

**Step-by-Step Allocation:**

1. **Find the Resource**
   - Go to Resources page
   - Click the resource you want to allocate

2. **Click "Allocate to Project" Button**
   - Top right of the page
   - Blue/yellow icon 📤

3. **Fill in the Allocation Form**

   **Project** (Required)
   - Select which project receives the materials
   - Dropdown shows only **Active** projects
   - If you don't see a project, check its status (must be "Active")

   **Quantity** (Required)
   - How much to send to the project
   - Example: 500 (to send 500 kg)
   - ⚠️ Cannot exceed Hub Stock (current warehouse stock)
   - System shows available quantity

   **Allocation Date** (Required)
   - When are you sending the materials?
   - Usually today's date
   - Cannot be in the future

   **Notes** (Optional)
   - Why are you allocating this?
   - Example: "Initial materials for foundation work"
   - Example: "Additional stock for Phase 2"

4. **Review the Summary**
   - **Current Hub Stock:** Shows how much you have
   - **Allocating:** Shows how much you're sending
   - **Remaining Hub Stock:** Shows what's left after allocation

5. **Click "Allocate" Button**

6. **What Happens:**
   - Hub Stock decreases by allocation quantity
   - Project inventory increases by allocation quantity
   - Two transactions created:
     - **ALLOCATION_OUT** from Hub (negative quantity)
     - **ALLOCATION_IN** to Project (positive quantity)
   - Green notification confirms success

**Example Allocation Scenario:**

Hub has 2500 kg of cement. You allocate 1000 kg to "Building A Project":
- **Before:** Hub Stock = 2500 kg, Project Stock = 0 kg
- **After:** Hub Stock = 1500 kg, Project Stock = 1000 kg

### Viewing Resource Details

To see complete information about a resource:

1. **Click the Resource Row**
   - Or click the eye icon (👁️)

2. **Resource Detail Page Shows:**

   **Basic Information Section:**
   - Name
   - SKU (you can copy it with one click)
   - Category
   - Base Unit
   - Description
   - Created date

   **Current Stock Section:**
   - **Hub Stock:** Quantity at central warehouse
     - Green if in stock
     - Red if zero
   - **Total Stock:** Sum of Hub + all projects
   - **Value Indicator:** Visual bar showing stock level

   **Price Information Section:**
   - **Last Purchase Price:** Most recent unit price you paid
   - **Average Price:** Average of all purchases
   - **Price Trend:** Shows if prices are going up or down

   **Transaction History Section:**
   - Last 50 transactions for this resource
   - Shows:
     - Date
     - Type (Purchase, Allocation, Consumption, Transfer)
     - Project (or "Hub")
     - Quantity (+ for incoming, - for outgoing)
     - Unit Price
     - Total Value
   - Color-coded by transaction type:
     - 🟢 Green: PURCHASE
     - 🟡 Yellow: ALLOCATION_OUT
     - 🔵 Blue: ALLOCATION_IN
     - 🔴 Red: CONSUMPTION
     - ⚪ Gray: TRANSFER_OUT
     - 🟣 Purple: TRANSFER_IN

3. **Action Buttons Available:**
   - **Edit:** Change name, description, category (not SKU or unit)
   - **Purchase:** Buy more stock
   - **Allocate to Project:** Send to a project
   - **Export Transactions:** Download Excel report

### Editing a Resource

You can edit some resource information (but not SKU or base unit).

1. **Open Resource Detail Page**

2. **Click "Edit" Button**
   - Top right, orange/yellow icon

3. **Edit Form Shows:**
   - Name - ✅ Can change
   - SKU - ❌ Cannot change (grayed out)
   - Category - ✅ Can change
   - Base Unit - ❌ Cannot change (grayed out)
   - Description - ✅ Can change

4. **Make Your Changes**

5. **Click "Save Changes"**

⚠️ **Why can't I change SKU or Base Unit?**
- SKU is the unique identifier - changing it would break transaction history
- Base Unit affects all transactions - changing it would make historical data incorrect
- Contact your administrator if you really need to change these

### Exporting Resource Transactions

Download an Excel file of all transactions for a specific resource.

1. **Open Resource Detail Page**

2. **Click "Export Transactions" Button**
   - Top right, green icon 📊

3. **Select Date Range**
   - **Start Date:** Beginning of period
   - **End Date:** End of period
   - Default: Last 30 days
   - 💡 Can select any range

4. **Click "Export" Button**

5. **File Downloads:**
   - Excel file (.xlsx)
   - Filename: `resource_[SKU]_transactions_[dates].xlsx`
   - Example: `resource_CEM-001_transactions_2026-01-01_to_2026-01-31.xlsx`

6. **Excel File Contains:**
   - Transaction Date
   - Transaction Type
   - Project Name (or "Hub")
   - Quantity
   - Unit Price
   - Total Value
   - Running Balance
   - Notes

7. **Use the File For:**
   - Accounting reconciliation
   - Cost analysis
   - Audit trail
   - Printing and filing

---

## Projects Management

Projects represent your construction sites or work locations. Each project has its own inventory of allocated materials.

### Understanding Project Status

Every project has a status that controls what you can do:

**🟡 Pending (Waiting to Start)**
- Project is created but not yet started
- Cannot allocate, consume, or transfer materials
- Use this for projects in planning phase
- **Action:** Change status to "Active" when work begins

**🟢 Active (Currently Working)**
- Project is in progress
- ✅ Can allocate materials from Hub
- ✅ Can record daily consumption
- ✅ Can transfer materials to/from other projects
- ✅ Can complete the project
- This is the main working status

**🔵 Completed (Finished)**
- Project is finished
- Cannot allocate, consume, or transfer materials
- Inventory is locked
- Historical data remains visible
- **Note:** Completing a project returns unused materials to Hub

### Viewing Projects

1. **Click "Projects" in the Left Sidebar**
   - Under "Project Management" section

2. **The Projects Table Shows:**

   **Columns:**
   - **Code** - Unique project identifier (like "PRJ-001")
   - **Name** - Project name (like "Building A Construction")
   - **Status** - Current status (Pending, Active, Completed)
   - **Start Date** - When project began
   - **End Date** - When project should finish
   - **Created At** - When project was added to system

3. **Status Badge Colors:**
   - **Gray badge** = Pending
   - **Green badge** = Active
   - **Blue badge** = Completed

4. **Table Features:**
   - Search by code or name
   - Sort by any column
   - Filter by status or date range
   - Toggle columns visibility

### Creating a New Project

1. **Click "New Project" Button**
   - Top right of Projects page
   - Green button with "+" icon

2. **Fill in the Form - Project Information**

   **Name** (Required)
   - Full project name
   - Example: "Building A Construction"
   - Example: "Villa 123 - Foundation Work"
   - Be descriptive and specific

   **Project Code** (Required)
   - Short unique identifier
   - Example: "PRJ-001", "BLDG-A", "VILLA-123"
   - Cannot have duplicates
   - 💡 Use a consistent coding system

   **Status** (Required)
   - Select from dropdown:
     - **Pending** - Not started yet (default)
     - **Active** - Currently working
     - **Completed** - Already finished
   - 💡 Most new projects start as "Pending"

3. **Fill in Timeline Section**

   **Start Date** (Optional)
   - When did/will project begin?
   - Click calendar icon to select
   - Can be past, present, or future

   **End Date** (Optional)
   - When should project finish?
   - Must be after Start Date
   - System validates this automatically

4. **Fill in Description Section**

   **Description** (Optional)
   - Detailed project information
   - Example: "Construction of 3-story residential building on Plot 45"
   - Can include location, scope, special requirements
   - Up to 65,535 characters (very long!)

5. **Click "Create" Button**

6. **Success!**
   - Green notification appears
   - Project appears in projects list
   - Initial inventory is empty

### Viewing Project Details

1. **Click a Project Row**
   - Or click the eye icon (👁️)

2. **Project Detail Page Shows:**

   **Project Information Section:**
   - Name
   - Code (copyable)
   - Status (colored badge)
   - Start Date
   - End Date
   - Description

   **Project Inventory Section:**
   - Table of all resources allocated to this project
   - For each resource shows:
     - Resource name and SKU
     - Category
     - **Allocated Quantity:** Total sent to project
     - **Consumed Quantity:** Total used so far
     - **Available Quantity:** Allocated - Consumed (what's left)
     - Unit
     - Total Value

   **Transaction History Section:**
   - Last 50 transactions for this project
   - All allocations, consumptions, and transfers
   - Shows:
     - Date
     - Type
     - Resource name
     - Quantity (+ or -)
     - Unit Price
     - Total Value
   - Color-coded by type

3. **Action Buttons (Top Right):**

   **Available for All Statuses:**
   - **Edit:** Change project information
   - **Export Daily Consumption:** Download today's usage report
   - **Export Resource Usage:** Download full transaction report

   **Available ONLY for Active Projects:**
   - **Allocate Resource:** Send materials from Hub
   - **Consume Resource:** Record material usage
   - **Transfer Resource:** Move materials to another project
   - **Complete Project:** Finish and return unused materials

### Allocating Resources to a Project

**Two ways to allocate:**

**Method 1: From Resource Page** (covered earlier)
- Go to Resource → Click Allocate → Select Project

**Method 2: From Project Page**

1. **Open Project Detail Page**
   - Project must be **Active** status

2. **Click "Allocate Resource" Button**
   - Top right, green icon ➕

3. **Fill in the Form**

   **Resource** (Required)
   - Select which material to allocate
   - Dropdown shows all resources
   - Searchable - type to find
   - Shows current Hub stock for each resource

   **Quantity** (Required)
   - How much to allocate
   - System shows available Hub stock
   - Cannot exceed available stock
   - Example: 1000

   **Allocation Date** (Required)
   - When are you allocating?
   - Default: Today
   - Cannot be future date

   **Notes** (Optional)
   - Why allocating?
   - Example: "Materials for Week 3 construction"

4. **Click "Allocate" Button**

5. **What Happens:**
   - Material moves from Hub to Project
   - Project Inventory table updates
   - New row appears (or existing row quantity increases)
   - Transaction History records the movement

### Recording Material Consumption

**Consumption** means recording how much material was actually used/consumed at the project site.

**When to record consumption:**
- End of each workday
- When a task is completed
- When materials are permanently used

**Step-by-Step:**

1. **Open Project Detail Page**
   - Project must be **Active**

2. **Click "Consume Resource" Button**
   - Top right, red icon 🔥

3. **Fill in the Consumption Form**

   **Resource** (Required)
   - Select which material was used
   - Dropdown shows **only resources allocated to this project**
   - Shows available quantity for each
   - If resource not in list, you need to allocate it first

   **Quantity** (Required)
   - How much was consumed/used
   - Example: 250 (if used 250 kg of cement today)
   - Cannot exceed available quantity at project
   - System validates this automatically

   **Consumption Date** (Required)
   - When was it used?
   - Default: Today
   - Cannot be future date
   - Can be past date (if recording late)

   **Notes** (Optional)
   - What was it used for?
   - Example: "Concrete for ground floor columns"
   - Example: "Foundation work - Section A"
   - Helpful for tracking progress

4. **Review the Summary**
   - **Available at Project:** Current stock
   - **Consuming:** What you're recording
   - **Will Remain:** What's left after consumption

5. **Click "Consume" Button**

6. **What Happens:**
   - Available quantity at project decreases
   - Consumed quantity increases
   - Transaction recorded (CONSUMPTION type)
   - ⚠️ Material does NOT return to Hub
   - Material is permanently used

**Example Consumption Scenario:**

Project "Building A" has 1000 kg cement allocated:
- **Before:** Allocated = 1000 kg, Consumed = 0 kg, Available = 1000 kg
- **Record Consumption:** 250 kg
- **After:** Allocated = 1000 kg, Consumed = 250 kg, Available = 750 kg

💡 **Important:** Once consumed, material cannot be "un-consumed". This is permanent.

### Transferring Materials Between Projects

**Transfer** means moving materials from one project to another project.

**When to transfer:**
- One project has excess materials
- Another project urgently needs materials
- Work priorities change
- More efficient than returning to Hub and re-allocating

**Step-by-Step:**

1. **Open Source Project** (project that has the materials)
   - Must be **Active** status

2. **Click "Transfer Resource" Button**
   - Top right, orange icon 🔄

3. **Fill in the Transfer Form**

   **Resource** (Required)
   - Which material to transfer
   - Shows only resources available at this project
   - Shows available quantity

   **To Project** (Required)
   - Which project receives the materials
   - Dropdown shows all **Active** projects
   - **Excludes** current project (can't transfer to self!)
   - Searchable dropdown

   **Quantity** (Required)
   - How much to transfer
   - Cannot exceed available quantity at source project
   - Example: 300

   **Transfer Date** (Required)
   - When is transfer happening?
   - Default: Today
   - Cannot be future

   **Notes** (Optional)
   - Why transferring?
   - Example: "Project B urgently needs cement for deadline"
   - Example: "Reallocating excess materials"

4. **Review the Summary**
   - **From:** Current project name
   - **To:** Destination project name
   - **Available at Source:** Current stock
   - **Transferring:** Transfer amount
   - **Will Remain at Source:** What's left

5. **Click "Transfer" Button**

6. **What Happens:**
   - Source project available quantity decreases
   - Destination project allocated quantity increases
   - **Two transactions created atomically:**
     - TRANSFER_OUT from source (negative)
     - TRANSFER_IN to destination (positive)
   - Both projects' Transaction History updated
   - Green notification confirms success

**Example Transfer Scenario:**

Transfer 300 kg cement from "Building A" to "Building B":

**Building A (Source):**
- **Before:** Available = 750 kg
- **After:** Available = 450 kg
- Transaction: TRANSFER_OUT -300 kg

**Building B (Destination):**
- **Before:** Allocated = 500 kg
- **After:** Allocated = 800 kg
- Transaction: TRANSFER_IN +300 kg

💡 **Atomic Transaction:** Both transfers succeed together or both fail. You can't have a situation where material disappears.

### Completing a Project

When a project is finished, you **Complete** it. This returns all unused materials back to the Hub.

**Before completing, understand:**
- ✅ Unused materials automatically return to Hub
- ❌ Cannot undo project completion
- ❌ Cannot allocate/consume/transfer after completion
- ✅ Transaction history remains visible
- ✅ Can still view project details and generate reports

**Step-by-Step:**

1. **Open Project Detail Page**
   - Project must be **Active**

2. **Review Project Inventory**
   - Check Available Quantities
   - These materials will return to Hub
   - Example: If cement shows Available = 450 kg, that 450 kg goes back

3. **Click "Complete Project" Button**
   - Top right, blue icon ✅

4. **Confirmation Modal Appears**

   **Modal Shows:**
   - **Project Name** and **Code**
   - **Warning Message:** This action cannot be undone
   - **What Will Happen:**
     - Project status → Completed
     - All unused materials → Hub
     - No more transactions allowed
   
   **Material Disposition Section:**
   - Shows each resource with available quantity
   - For each resource, you see:
     - Resource name
     - Available quantity
     - What happens: "Return to Hub"

5. **Click "Complete Project" Button** (in modal)

6. **What Happens:**
   - Project status changes to "Completed" (blue badge)
   - For each resource with available quantity > 0:
     - ALLOCATION_OUT from project (negative)
     - ALLOCATION_IN to Hub (positive)
   - Hub stock increases
   - Project available quantities become zero
   - Green notification: "Project completed successfully"

**Example Completion Scenario:**

"Building A" is finished with:
- Cement: Allocated = 1000 kg, Consumed = 550 kg, Available = 450 kg
- Steel: Allocated = 500 kg, Consumed = 500 kg, Available = 0 kg

After completion:
- Cement: 450 kg returns to Hub
- Steel: Nothing to return (fully consumed)
- Hub cement stock increases by 450 kg
- Project status = Completed

💡 **When should I complete a project?**
- Construction work is 100% done
- No more material consumption will occur
- You want to free up unused materials for other projects

### Editing a Project

You can edit project information at any time, regardless of status.

1. **Open Project Detail Page**

2. **Click "Edit" Button**
   - Top right, orange icon ✏️

3. **Edit Form Shows All Fields:**
   - Name - ✅ Can change
   - Code - ✅ Can change (but be careful!)
   - Status - ✅ Can change (Pending ↔ Active ↔ Completed)
   - Start Date - ✅ Can change
   - End Date - ✅ Can change
   - Description - ✅ Can change

4. **Make Your Changes**

5. **Click "Save Changes"**

**Important Notes:**

**Changing Status:**
- Pending → Active: Now you can allocate/consume/transfer
- Active → Pending: Locks all inventory actions
- Active → Completed: Same as "Complete Project" button (returns materials)
- Completed → Active: ⚠️ Possible but unusual - materials don't come back automatically

**Changing Code:**
- Possible but not recommended
- Affects reporting and references
- Transaction history remains intact

💡 **Best Practice:** Only change status through "Complete Project" button for proper material return.

---

## Transaction History

Transaction History is your complete audit trail. Every material movement is recorded here.

### Understanding Transactions

**What is a transaction?**
- A record of material movement
- Includes: what, when, where, how much, how much it cost
- **Immutable:** Cannot be edited or deleted
- Permanent audit trail

**Transaction Types:**

1. **PURCHASE** 🟢
   - Adding stock through purchasing
   - Always at Hub
   - Increases Hub inventory
   - Has supplier and invoice information

2. **ALLOCATION_OUT** 🟡
   - Sending materials from Hub to project
   - Or from project when completing
   - Decreases source location
   - Negative quantity in ledger

3. **ALLOCATION_IN** 🔵
   - Receiving materials at project from Hub
   - Or receiving at Hub when project completes
   - Increases destination location
   - Positive quantity in ledger

4. **CONSUMPTION** 🔴
   - Using/consuming materials at project
   - Permanent usage
   - Decreases project inventory
   - Negative quantity

5. **TRANSFER_OUT** ⚪
   - Sending materials from one project to another
   - Decreases source project
   - Negative quantity

6. **TRANSFER_IN** 🟣
   - Receiving materials from another project
   - Increases destination project
   - Positive quantity

### Viewing Transaction History

1. **Click "Transaction History"**
   - Left sidebar, under "Inventory Management"

2. **The Transaction Table Shows:**

   **Columns:**
   - **Date** - When transaction occurred
   - **Type** - Transaction type (colored badge)
   - **Resource** - Material name
   - **SKU** - Resource identifier
   - **Project** - Project name (or blank if Hub)
   - **Quantity** - Amount (+ or -)
   - **Unit Price** - Cost per unit (AED)
   - **Total Value** - Quantity × Unit Price (AED)
   - **Created At** - System timestamp

3. **Understanding Quantity Signs:**
   - **Positive (+)** - Material coming IN
     - Purchase: +1000 kg (bought)
     - Allocation In: +500 kg (received at project)
     - Transfer In: +300 kg (received from other project)
   
   - **Negative (-)** - Material going OUT
     - Allocation Out: -500 kg (sent from Hub)
     - Consumption: -250 kg (used at project)
     - Transfer Out: -300 kg (sent to other project)

4. **Color Coding:**
   - 🟢 Green Badge = PURCHASE
   - 🟡 Yellow Badge = ALLOCATION_OUT
   - 🔵 Blue Badge = ALLOCATION_IN
   - 🔴 Red Badge = CONSUMPTION
   - ⚪ Gray Badge = TRANSFER_OUT
   - 🟣 Purple Badge = TRANSFER_IN

### Filtering Transactions

The Transaction History has powerful filtering to find specific transactions.

**Click "Filter" Button** (top right)

**Available Filters:**

1. **Resource**
   - Select one or more resources
   - See only transactions for those materials
   - Example: Select "Portland Cement" to see all cement movements

2. **Project**
   - Select one or more projects
   - See only transactions for those projects
   - Leave empty to include Hub transactions

3. **Transaction Type**
   - Select one or more types
   - Example: Select only "CONSUMPTION" to see all material usage
   - Example: Select "PURCHASE" to see all buying history

4. **Date Range**
   - **From Date:** Start of period
   - **To Date:** End of period
   - Example: January 1 to January 31 for monthly report
   - Leave empty for all dates

5. **Amount Range**
   - **Minimum:** Show only transactions above this value
   - **Maximum:** Show only transactions below this value
   - Useful for finding large purchases or movements

**Apply Filters:**
- Click "Apply" button
- Table updates to show only matching transactions
- Filter badge shows how many filters are active

**Clear Filters:**
- Click "Reset" or "Clear Filters"
- Table shows all transactions again

### Viewing Transaction Details

1. **Click Any Transaction Row**
   - Or click the eye icon (👁️)

2. **Transaction Detail Page Shows:**

   **Transaction Information:**
   - Transaction Date (when it happened)
   - Transaction Type (colored badge)
   - Created At (system timestamp)

   **Resource Information:**
   - Resource Name
   - SKU
   - Category
   - Current Hub Stock
   - Current Total Stock

   **Project Information:**
   - Project Name (or "Central Hub" if at warehouse)
   - Project Code (or "HUB")
   - Project Status

   **Quantity & Pricing:**
   - Quantity (with + or - sign)
   - Unit (kg, liters, pieces, etc.)
   - Unit Price (AED per unit)
   - Total Value (calculated)

   **Additional Details:**
   - Supplier (if purchase)
   - Reference Number (if purchase)
   - Notes (if any)

3. **No Edit or Delete Buttons**
   - ⚠️ Transactions are **immutable**
   - This is intentional for audit trail integrity
   - Cannot be modified once created

**Why can't I edit or delete transactions?**
- Maintains accurate audit trail
- Prevents fraud or accidental data loss
- Ensures accounting accuracy
- Meets regulatory requirements for record keeping

### Searching Transactions

Use the search box at the top of the table:

**Searchable Fields:**
- Resource name
- Resource SKU
- Project name
- Project code
- Supplier name
- Reference number
- Notes

**Search Examples:**
- Type "cement" - finds all cement-related transactions
- Type "Building A" - finds all transactions for that project
- Type "ABC Supplies" - finds all purchases from that supplier
- Type "urgent" - finds all transactions with "urgent" in notes

### Exporting Transaction History

Download transaction data to Excel for analysis or record keeping.

**Method 1: Export All Filtered Transactions**

1. **Apply Filters** (if desired)
   - Example: Filter by date range, resource, or project

2. **Click "Export" Button**
   - Top right of page
   - Downloads all transactions matching current filters

3. **Excel File Contains:**
   - All visible columns
   - Respects current filters
   - Formatted for printing and analysis

**Method 2: Export from Resource Page**
- See "Resources Management" section above
- Exports only transactions for one resource

**Method 3: Export from Project Page**
- See "Reports & Exports" section below
- Exports only transactions for one project

---

## User Management

**⚠️ Admin Only:** This section is only for users with Administrator role.

If you don't see "Users" in the left sidebar, you're not an admin and can skip this section.

### Viewing Users

1. **Click "Users" in the Left Sidebar**
   - Under "Administration" section

2. **The Users Table Shows:**
   - **Name** - Full name
   - **Email** - Login email address
   - **Role** - "admin" or "user"
   - **Created At** - When account was created

3. **Role Badge Colors:**
   - **Purple Badge** = Admin
   - **Gray Badge** = User

### Creating a New User

**⚠️ Important:** You can only create Regular Users, not Admins.

1. **Click "New User" Button**
   - Top right, green button

2. **Fill in the Form**

   **Name** (Required)
   - Full name of the person
   - Example: "Ahmed Hassan"

   **Email** (Required)
   - Their email address
   - Must be unique (not already in system)
   - This is their login username
   - Example: "ahmed@company.com"
   - ⚠️ Must be valid email format

   **Password** (Required)
   - Their initial password
   - Minimum 8 characters
   - Example: "Welcome123"
   - 💡 Tell them to change it after first login

   **Role** (Auto-set)
   - Automatically set to "user"
   - Cannot create admin users
   - Grayed out field

3. **Click "Create" Button**

4. **Success!**
   - User account created
   - Green notification appears
   - User can now log in with provided email and password

5. **Next Steps:**
   - Give the user their login credentials:
     - **URL:** Your system URL
     - **Email:** What you entered
     - **Password:** What you entered
   - Tell them to change password after first login
   - Show them this user manual!

### Editing a User

1. **Click User Row**
   - Or click edit icon (✏️)

2. **Edit Form Shows:**
   - Name - ✅ Can change
   - Email - ✅ Can change (be careful!)
   - Password - ✅ Can change (leave empty to keep current)
   - Role - ❌ Cannot change (grayed out)

3. **Make Changes**
   - Update name if person's name changed
   - Update email if they have new email
   - **Password:** Only fill if resetting password
     - Leave empty to keep current password
     - Fill to set new password

4. **Click "Save Changes"**

**⚠️ Changing Email:**
- User must log in with new email
- Old email stops working immediately
- Inform the user about email change

**Resetting User Password:**
1. Edit user
2. Enter new password in password field
3. Save
4. Inform user of new password
5. They should change it after logging in

### Deleting a User

1. **Click User Row** to select

2. **Click "Delete" Button**
   - Top right, red button
   - ⚠️ Permanent action!

3. **Confirmation Modal:**
   - "Are you sure you want to delete this user?"
   - Shows user name and email

4. **Click "Delete" to Confirm**

5. **What Happens:**
   - User account removed
   - User cannot log in anymore
   - **Transaction history remains intact**
   - User's name still appears on historical transactions

**⚠️ When to delete:**
- Employee left company
- Account was created by mistake
- User no longer needs access

**⚠️ Cannot delete:**
- Your own account (the logged-in admin)
- Users who are currently logged in

### Changing Your Own Password

**All Users (Admin and Regular):**

1. **Click Your Profile Icon**
   - Top right corner
   - Shows your name

2. **Click "Profile" or "Settings"**
   - (May vary by system version)

3. **Find "Change Password" Section**

4. **Fill in:**
   - **Current Password:** Your existing password
   - **New Password:** Your desired password (min 8 chars)
   - **Confirm Password:** Type new password again

5. **Click "Update Password"**

6. **Success:**
   - Green notification
   - Use new password for next login

💡 **Password Best Practices:**
- Use at least 8 characters
- Mix uppercase and lowercase
- Include numbers
- Don't share your password
- Change it regularly
- Don't use the same password on multiple sites

---

## Reports & Exports

The system provides several types of reports to help you track materials, costs, and usage.

### Daily Consumption Report

**What it shows:** All materials consumed on a specific day for a project.

**When to use:**
- Daily reporting to management
- Daily cost tracking
- Verifying site foreman's material usage
- Billing based on daily consumption

**How to generate:**

1. **Open a Project Detail Page**

2. **Click "Export Daily Consumption" Button**
   - Top right, green icon 📊

3. **Select Date**
   - Which day do you want to report on?
   - Default: Today
   - Can select past dates
   - Cannot select future dates

4. **Click "Export" Button**

5. **Excel File Downloads:**
   - Filename: `project_[CODE]_daily_consumption_[DATE].xlsx`
   - Example: `project_BLDG-A_daily_consumption_2026-01-31.xlsx`

6. **Excel File Contains:**

   **Header Section:**
   - Report Title: "Daily Consumption Report"
   - Project Name and Code
   - Report Date
   - Generation Date and Time

   **For Each Resource Used That Day:**
   - Resource Name
   - SKU
   - Category
   - Unit
   - **Opening Balance:** Stock at start of day
   - **Purchases:** Any purchases that day (if any)
   - **Allocations In:** Materials received that day
   - **Transfers In:** Materials received from other projects
   - **Total In:** Sum of incoming materials
   - **Consumption:** Materials used that day
   - **Transfers Out:** Materials sent to other projects
   - **Allocations Out:** Materials returned (if completing)
   - **Total Out:** Sum of outgoing materials
   - **Closing Balance:** Stock at end of day

   **Summary Row:**
   - Totals for all columns
   - Shows total value consumed that day

7. **Use the Report For:**
   - Daily production meetings
   - Cost control
   - Progress tracking
   - Billing clients
   - Comparing planned vs actual usage

**Example Report Scenario:**

Project "Building A" on January 31, 2026:
- Used 250 kg Portland Cement (cost: 6,250 AED)
- Used 100 kg Steel Reinforcement (cost: 8,000 AED)
- Received 500 kg Cement from Hub (allocation)
- Total consumption value: 14,250 AED

Report shows all this with opening/closing balances.

### Resource Usage Report (Transaction Report)

**What it shows:** All transactions for a project within a date range.

**When to use:**
- Monthly reporting
- Project cost analysis
- Audit requirements
- Understanding material flow over time
- Final project accounting

**How to generate:**

1. **Open a Project Detail Page**

2. **Click "Export Resource Usage" Button**
   - Top right, green icon 📈

3. **Select Date Range**
   - **Start Date:** Beginning of period
   - **End Date:** End of period
   - Example: January 1 to January 31 for monthly report
   - Can select any range

4. **Click "Export" Button**

5. **Excel File Downloads:**
   - Filename: `project_[CODE]_resource_usage_[START]_to_[END].xlsx`
   - Example: `project_BLDG-A_resource_usage_2026-01-01_to_2026-01-31.xlsx`

6. **Excel File Contains:**

   **Header Section:**
   - Report Title: "Project Resource Usage Report"
   - Project Name and Code
   - Date Range
   - Generation Date and Time

   **Transaction List:**
   - Transaction Date
   - Transaction Type
   - Resource Name
   - SKU
   - Quantity
   - Unit
   - Unit Price (AED)
   - Total Value (AED)
   - Notes

   **Summary Section:**
   - Total Purchases
   - Total Allocations In
   - Total Transfers In
   - Total Consumption
   - Total Transfers Out
   - Total Allocations Out
   - **Net Change:** Total In - Total Out
   - **Total Value:** Sum of all transactions

7. **Use the Report For:**
   - Monthly cost reports
   - Comparing budgeted vs actual costs
   - Identifying material waste
   - Project profitability analysis
   - Tax and accounting records

**Example Report Scenario:**

Project "Building A" for January 2026:
- Total allocations in: 50,000 AED
- Total consumption: 35,000 AED
- Total transfers out: 5,000 AED
- Net materials remaining: 10,000 AED

### Resource Transaction History Export

**What it shows:** All transactions for a specific resource (material).

**When to use:**
- Tracking price changes over time
- Understanding usage patterns for a material
- Supplier comparison
- Inventory auditing

**How to generate:**

1. **Open a Resource Detail Page**

2. **Click "Export Transactions" Button**
   - Top right, green icon 📊

3. **Select Date Range**
   - Start and End dates
   - Or use presets (Last 30 days, Last 90 days, etc.)

4. **Click "Export" Button**

5. **Excel File Downloads:**
   - Filename: `resource_[SKU]_transactions_[DATES].xlsx`

6. **Excel File Contains:**
   - Transaction Date
   - Type
   - Project (or "Hub")
   - Quantity
   - Unit Price
   - Total Value
   - Supplier (if purchase)
   - Reference Number
   - Notes
   - **Running Balance:** Cumulative quantity

7. **Use the Report For:**
   - Price trend analysis
   - Identifying best suppliers
   - Usage forecasting
   - Stock auditing

### General Transaction History Export

**What it shows:** All transactions system-wide, with filtering.

**How to generate:**

1. **Go to Transaction History Page**

2. **Apply Filters** (optional)
   - Filter by resource, project, type, date range, etc.
   - Only filtered transactions will export

3. **Click "Export" Button**

4. **Excel File Downloads:**
   - Contains all visible transactions
   - Respects current filters and sorting

5. **Use the Report For:**
   - Complete audit trail
   - Accounting reconciliation
   - System-wide cost analysis
   - Year-end reporting

### Report Best Practices

**Daily Workflow:**
1. Start of day: Review yesterday's Daily Consumption Report
2. During day: Record transactions in real-time
3. End of day: Generate Daily Consumption Report
4. Share with project manager

**Weekly Workflow:**
1. Generate Resource Usage Reports for all active projects
2. Review costs vs budget
3. Identify materials running low
4. Plan next week's purchases

**Monthly Workflow:**
1. Generate complete Transaction History export
2. Reconcile with accounting records
3. Archive reports for record keeping
4. Review project costs and update budgets

**Archiving:**
- Download reports regularly
- Store in organized folders by month/year
- Keep for regulatory compliance (usually 7 years)
- Backup important reports

---

## Common Workflows

This section shows complete step-by-step workflows for common tasks.

### Workflow 1: Starting a New Construction Project

**Scenario:** You're starting a new building project called "Villa 205".

**Steps:**

1. **Create the Project**
   - Go to Projects → New Project
   - Name: "Villa 205 Construction"
   - Code: "VILLA-205"
   - Status: "Pending"
   - Start Date: February 1, 2026
   - End Date: August 31, 2026
   - Description: "3-bedroom villa construction on Plot 205"
   - Click Create

2. **Purchase Initial Materials**
   - Go to Resources
   - For each material needed:
     
     **Example: Portland Cement**
     - Click "Portland Cement" resource
     - Click "Purchase"
     - Quantity: 5000 kg
     - Unit Price: 20 AED/kg
     - Purchase Date: January 31, 2026
     - Supplier: "National Cement Co."
     - Click Purchase
     
     **Example: Steel Reinforcement**
     - Click "Steel Reinforcement 16mm" resource
     - Click "Purchase"
     - Quantity: 2000 kg
     - Unit Price: 80 AED/kg
     - Purchase Date: January 31, 2026
     - Supplier: "Steel Suppliers LLC"
     - Click Purchase
     
     Repeat for all materials...

3. **Change Project Status to Active**
   - Go to Projects → Villa 205
   - Click Edit
   - Status: Change to "Active"
   - Click Save Changes

4. **Allocate Materials to Project**
   - Still on Villa 205 detail page
   - Click "Allocate Resource"
   
   **Allocate Cement:**
   - Resource: Portland Cement
   - Quantity: 3000 kg (leaving 2000 kg at Hub as reserve)
   - Date: February 1, 2026
   - Notes: "Initial materials for foundation"
   - Click Allocate
   
   **Allocate Steel:**
   - Click "Allocate Resource" again
   - Resource: Steel Reinforcement 16mm
   - Quantity: 1500 kg
   - Date: February 1, 2026
   - Notes: "Foundation reinforcement"
   - Click Allocate

5. **Project is Ready!**
   - Villa 205 now has 3000 kg cement and 1500 kg steel
   - Hub still has 2000 kg cement and 500 kg steel as reserve
   - Work can begin!

### Workflow 2: Daily Material Consumption Recording

**Scenario:** It's end of workday, and you need to record what was used.

**Steps:**

1. **Go to the Project**
   - Projects → Select your active project

2. **Click "Consume Resource"**

3. **Record Each Material Used**
   
   **Example: Cement Used Today**
   - Resource: Portland Cement
   - Quantity: 250 kg
   - Consumption Date: Today's date (auto-filled)
   - Notes: "Concrete for ground floor columns, Section A"
   - Click Consume
   
   **Example: Steel Used Today**
   - Click "Consume Resource" again
   - Resource: Steel Reinforcement 16mm
   - Quantity: 75 kg
   - Consumption Date: Today
   - Notes: "Column reinforcement, Section A"
   - Click Consume

4. **Generate Daily Report**
   - Click "Export Daily Consumption"
   - Date: Today
   - Click Export
   - Excel file downloads

5. **Share Report**
   - Email the Excel file to project manager
   - Or save to shared drive
   - Or print for filing

6. **Review Remaining Stock**
   - Check Project Inventory section
   - Cement: Was 3000 kg, used 250 kg, remaining 2750 kg ✓
   - Steel: Was 1500 kg, used 75 kg, remaining 1425 kg ✓
   - All good for tomorrow's work!

### Workflow 3: Transferring Materials Between Projects

**Scenario:** Project A has excess cement, Project B urgently needs cement.

**Steps:**

1. **Check Project A Inventory**
   - Go to Project A detail page
   - Review Project Inventory
   - See: Portland Cement - Available: 1000 kg ✓

2. **Check Project B Status**
   - Go to Project B detail page
   - Verify status is "Active" ✓
   - See current cement: 50 kg (running low!)

3. **Initiate Transfer from Project A**
   - Go back to Project A detail page
   - Click "Transfer Resource"

4. **Fill Transfer Form**
   - Resource: Portland Cement
   - To Project: Select "Project B"
   - Quantity: 500 kg (half of Project A's excess)
   - Transfer Date: Today
   - Notes: "Emergency transfer - Project B deadline"
   - Click Transfer

5. **Verify Transfer**
   
   **Check Project A:**
   - Available cement: 500 kg (was 1000 kg) ✓
   - Transaction History shows: TRANSFER_OUT -500 kg
   
   **Check Project B:**
   - Allocated cement: 550 kg (was 50 kg) ✓
   - Transaction History shows: TRANSFER_IN +500 kg

6. **Inform Teams**
   - Call Project B supervisor: "500 kg cement on the way"
   - Update project logs
   - Both projects continue working!

### Workflow 4: Completing a Project

**Scenario:** Villa 205 construction is finished. Time to close the project.

**Steps:**

1. **Final Consumption Recording**
   - First, record any final material usage
   - Go to Villa 205 → Consume Resource
   - Record all remaining consumption
   - Make sure all used materials are accounted for

2. **Review Project Inventory**
   - Check Project Inventory section
   - Note what's remaining:
     - Cement: Available 450 kg
     - Steel: Available 200 kg
     - (These will return to Hub)

3. **Click "Complete Project"**

4. **Review Completion Modal**
   - Shows project name: "Villa 205"
   - Shows materials returning:
     - Portland Cement: 450 kg → Return to Hub
     - Steel Reinforcement: 200 kg → Return to Hub
   - Warning: "This action cannot be undone"

5. **Confirm Completion**
   - Click "Complete Project" in modal

6. **Verify Completion**
   
   **Project Villa 205:**
   - Status: Changed to "Completed" (blue badge) ✓
   - Available quantities: All zero ✓
   - Transaction History: Shows ALLOCATION_OUT entries
   
   **Hub (Warehouse):**
   - Cement stock: Increased by 450 kg ✓
   - Steel stock: Increased by 200 kg ✓
   - Materials ready for next project!

7. **Final Reporting**
   - Click "Export Resource Usage"
   - Date Range: Project start to end (Feb 1 - Aug 31)
   - Click Export
   - Review total project costs
   - Archive report
   - Update project accounting

8. **Project Closed!**
   - Materials returned
   - Records preserved
   - Ready to invoice client

### Workflow 5: Monthly Reporting and Reconciliation

**Scenario:** It's end of month, time for monthly reports.

**Steps:**

1. **Generate Hub Inventory Report**
   - Go to Transaction History
   - Filter:
     - Date Range: First to last day of month
     - Project: Leave empty (to include Hub)
   - Click Export
   - Save as: "Hub_Transactions_January_2026.xlsx"

2. **Generate Reports for Each Active Project**
   - For each project:
     - Open project detail page
     - Click "Export Resource Usage"
     - Date Range: First to last day of month
     - Click Export
     - Save as: "ProjectName_January_2026.xlsx"

3. **Generate Daily Consumption Reports**
   - If needed for specific high-activity days
   - Useful for detailed analysis

4. **Reconcile with Accounting**
   - Open each Excel file
   - Compare with purchase invoices
   - Verify all purchases recorded
   - Check for discrepancies
   - Update accounting system

5. **Cost Analysis**
   - Sum total purchases for the month
   - Sum consumption per project
   - Calculate Hub inventory value
   - Compare to budget
   - Identify cost overruns

6. **Archive Reports**
   - Create folder: "Reports/2026/January/"
   - Save all Excel files
   - Add summary notes
   - Backup to cloud storage

7. **Management Report**
   - Create summary document:
     - Total purchases: X AED
     - Total consumption: Y AED
     - Hub inventory value: Z AED
     - Active projects: N
     - Key insights and recommendations
   - Share with management

### Workflow 6: Handling Urgent Material Requests

**Scenario:** Project B supervisor calls: "We urgently need 200 kg cement NOW!"

**Steps:**

1. **Check Hub Stock**
   - Go to Resources → Portland Cement
   - Check Hub Stock
   - Scenario A: Hub has 500 kg ✓ (Proceed to step 2)
   - Scenario B: Hub has 50 kg ✗ (Need to purchase - see step 7)

2. **Quick Allocation (Scenario A: Hub has stock)**
   - From Resources page, click Portland Cement
   - Click "Allocate to Project"
   - Project: Select "Project B"
   - Quantity: 200 kg
   - Date: Today
   - Notes: "Urgent request from site supervisor"
   - Click Allocate

3. **Confirm with Supervisor**
   - Call back: "200 kg allocated, check your inventory"
   - They can verify in their project page
   - Materials ready to use!

4. **Update Your Notes**
   - Add note in project description or internal log
   - Track for later analysis of emergency requests

**Scenario B: Hub Low on Stock**

7. **Check Other Projects**
   - Can we transfer from another project?
   - Go to each active project
   - Check their cement available quantities
   - Project A has 1000 kg available ✓

8. **Transfer from Project A to Project B**
   - Go to Project A detail page
   - Click "Transfer Resource"
   - Resource: Portland Cement
   - To Project: Project B
   - Quantity: 200 kg
   - Notes: "Emergency transfer"
   - Click Transfer

9. **Call Supervisor**
   - "200 kg transferred from Project A, check inventory"
   - Crisis resolved!

10. **Plan Replenishment**
    - Add to shopping list: Need to purchase cement
    - Don't let Hub run this low again
    - Set reminder to purchase this week

### Workflow 7: Tracking Material Price Changes

**Scenario:** You want to see if cement prices are increasing.

**Steps:**

1. **Go to Portland Cement Resource**
   - Resources → Portland Cement
   - Click to open detail page

2. **Review Price Information**
   - Last Purchase Price: 22 AED/kg
   - Average Price: 20 AED/kg
   - 💡 Last purchase was higher than average!

3. **Export Transaction History**
   - Click "Export Transactions"
   - Date Range: Last 6 months
   - Click Export

4. **Analyze in Excel**
   - Open downloaded file
   - Look at Unit Price column
   - Create chart (Insert → Chart → Line Chart)
   - X-axis: Transaction Date
   - Y-axis: Unit Price
   - See trend over time

5. **Findings**
   - January: 20 AED/kg
   - February: 20 AED/kg
   - March: 21 AED/kg
   - April: 21 AED/kg
   - May: 22 AED/kg
   - 📈 Price increasing 10% over 5 months!

6. **Take Action**
   - Consider bulk purchase now before prices rise more
   - Negotiate with supplier for better rates
   - Update budget forecasts
   - Inform management of cost increases

---

## Troubleshooting & FAQs

### Common Issues and Solutions

#### Issue: "I can't log in"

**Possible Causes:**

1. **Wrong email or password**
   - ✅ Check for typos
   - ✅ Email is case-insensitive, password is case-sensitive
   - ✅ Try copying and pasting if typing doesn't work

2. **Caps Lock is on**
   - ✅ Check your keyboard Caps Lock indicator
   - Password "Password123" ≠ "PASSWORD123"

3. **Account doesn't exist or was deleted**
   - ✅ Contact your administrator
   - ✅ They can verify your account exists

4. **Browser cache issue**
   - ✅ Clear browser cache and cookies
   - ✅ Try incognito/private browsing mode
   - ✅ Try different browser

**Solution:**
- Contact administrator to reset your password
- Try "Forgot Password" link if available

---

#### Issue: "I can't see the Users menu"

**Cause:** You're not an administrator.

**Solution:**
- This is normal for regular users
- Only admins can manage users
- If you need admin access, ask your administrator
- You can still do all inventory and project work

---

#### Issue: "I can't allocate resources to a project"

**Possible Causes:**

1. **Project status is not "Active"**
   - ✅ Check project status badge
   - ✅ Should be green "Active"
   - ✅ Change status to Active first

2. **Not enough stock at Hub**
   - ✅ Check resource Hub Stock
   - ✅ Must have sufficient quantity available
   - ✅ Purchase more first if needed

3. **You're on wrong page**
   - ✅ Must click "Allocate to Project" button
   - ✅ Or use Allocate action on Resource detail page

**Solution:**
1. Open project → Click Edit → Change Status to "Active" → Save
2. Or purchase more materials first
3. Then allocate

---

#### Issue: "Action buttons (Consume, Transfer, etc.) are missing"

**Cause:** Project status is not "Active".

**Explanation:**
- 🟡 Pending projects: No action buttons (project not started)
- 🟢 Active projects: All action buttons visible
- 🔵 Completed projects: No action buttons (project finished)

**Solution:**
1. Open project detail page
2. Click Edit button
3. Change Status to "Active"
4. Save changes
5. Action buttons now appear!

---

#### Issue: "Error: Insufficient stock at hub"

**Cause:** Trying to allocate more than available.

**Example:**
- Hub has 500 kg cement
- You try to allocate 1000 kg
- System blocks this ✋

**Solution:**
1. Check Hub Stock first
2. Purchase more materials if needed
3. Or allocate less (within available stock)
4. Or transfer from another project

---

#### Issue: "Error: Insufficient stock at project"

**Cause:** Trying to consume or transfer more than project has.

**Example:**
- Project has 200 kg cement available
- You try to consume 300 kg
- System blocks this ✋

**Solution:**
1. Check Project Inventory available quantity
2. Allocate more from Hub if needed
3. Or consume only what's available
4. Update quantity in the form

---

#### Issue: "I made a mistake in a transaction. How do I fix it?"

**Answer:** You cannot edit or delete transactions.

**Why?**
- Maintains audit trail integrity
- Prevents fraud
- Ensures accounting accuracy

**Solution:**
1. **Record correcting transaction**
   - Example: If you consumed 300 kg but meant 250 kg
   - The 50 kg "over-consumption" cannot be undone
   - You'll need to allocate another 50 kg if needed

2. **Add explanation in notes**
   - When recording next transaction, explain the correction
   - Example Notes: "Correction for yesterday's entry"

3. **Document externally**
   - Keep a separate log of corrections
   - Update accounting records
   - Inform your manager if significant

4. **Contact administrator for major errors**
   - They may have database access for critical fixes
   - Only for serious errors (wrong project, wrong resource, etc.)

---

#### Issue: "The page is loading slowly"

**Possible Causes:**

1. **Slow internet connection**
   - ✅ Test your internet speed
   - ✅ Try accessing other websites
   - ✅ Contact IT if internet is slow

2. **Large amount of data**
   - ✅ If viewing transaction history with thousands of records
   - ✅ Use filters to reduce data shown

3. **Browser issues**
   - ✅ Close other tabs
   - ✅ Restart browser
   - ✅ Clear cache and cookies

4. **Server issues**
   - ✅ Contact administrator
   - ✅ May be temporary, try again later

**Solution:**
- Use date range filters to limit data
- Export large reports instead of viewing in browser
- Use modern browser (Chrome, Firefox, Edge latest versions)

---

#### Issue: "Export/Download button doesn't work"

**Possible Causes:**

1. **Pop-up blocker**
   - ✅ Browser is blocking downloads
   - ✅ Allow pop-ups for this site

2. **Browser download settings**
   - ✅ Check browser download folder
   - ✅ File might already be downloading

3. **Network interruption**
   - ✅ Click again
   - ✅ Wait a few seconds

**Solution:**
1. Check browser download settings
2. Allow pop-ups and downloads for the site
3. Check your Downloads folder
4. Try different browser if problem persists

---

#### Issue: "I can't find a specific resource or project"

**Solution:**

1. **Use the Search Box**
   - Type resource name, SKU, or project code
   - Search is not case-sensitive
   - Searches all text fields

2. **Check Filters**
   - You might have active filters
   - Click "Clear Filters" or "Reset"
   - Try again

3. **Check Pagination**
   - Item might be on another page
   - Use navigation arrows to browse pages
   - Or increase "items per page" setting

4. **Verify it exists**
   - Ask administrator
   - Check if it was deleted
   - Maybe it was never created

---

#### Issue: "Status badge shows wrong color"

**Answer:** Colors are intentional and have specific meanings.

**Project Status Colors:**
- 🟡 Gray = Pending (not started)
- 🟢 Green = Active (currently working)
- 🔵 Blue = Completed (finished)

**Transaction Type Colors:**
- 🟢 Green = PURCHASE
- 🟡 Yellow = ALLOCATION_OUT
- 🔵 Blue = ALLOCATION_IN
- 🔴 Red = CONSUMPTION
- ⚪ Gray = TRANSFER_OUT
- 🟣 Purple = TRANSFER_IN

This is normal and helps you quickly identify status/type!

---

#### Issue: "Numbers don't add up correctly"

**Common Confusion:**

**Example:**
- Resource shows: Hub Stock = 1000 kg, Total Stock = 1500 kg
- "Why is Total higher than Hub?"

**Answer:**
- **Hub Stock:** Only what's at central warehouse
- **Total Stock:** Hub + all project sites
- Total will be higher if materials are allocated to projects

**To understand:**
1. Check Hub Stock: 1000 kg (at warehouse)
2. Check each project inventory
   - Project A has 300 kg
   - Project B has 200 kg
   - Total at projects: 500 kg
3. Total Stock: 1000 + 500 = 1500 kg ✓

---

### Frequently Asked Questions (FAQ)

#### Q: What currency does the system use?

**A:** AED (UAE Dirham). All prices and values are in AED.

---

#### Q: Can I change the unit of a resource after creating it?

**A:** No. The base unit cannot be changed once the resource is created. This is to maintain consistency in transaction history.

**Workaround:** Create a new resource with the correct unit and mark the old one as discontinued in the description.

---

#### Q: What happens to transactions when I delete a user?

**A:** Transaction history is preserved. Transactions created by deleted users remain visible with the user's name, but the user cannot log in anymore.

---

#### Q: Can I have the same SKU for different resources?

**A:** No. SKU must be unique across all resources. The system will prevent you from creating duplicates.

---

#### Q: What if I complete a project by mistake?

**A:** Contact your administrator immediately. Project completion cannot be undone through the interface. The administrator may be able to help reverse it in the database, but it's complex.

**Prevention:** Always double-check before clicking "Complete Project"!

---

#### Q: Can I set low stock alerts?

**A:** This feature may be available in your system version. Check the Dashboard or Resources page for low stock indicators. Contact your administrator to enable notifications.

---

#### Q: How long is transaction history kept?

**A:** Indefinitely. All transactions are permanent and never deleted. This ensures complete audit trail.

---

#### Q: Can I bulk import resources or projects?

**A:** This depends on your system version. Contact your administrator. They may be able to import via database or API.

---

#### Q: What browsers are supported?

**A:** Modern browsers:
- ✅ Chrome (recommended)
- ✅ Firefox
- ✅ Safari
- ✅ Microsoft Edge

Not supported:
- ❌ Internet Explorer

---

#### Q: Can I access the system from my phone?

**A:** Yes! The interface is responsive and works on mobile devices. However, for best experience (especially for reports), use a desktop or tablet.

---

#### Q: What if two people record the same consumption?

**A:** Both transactions will be recorded. This creates duplicate consumption records.

**Prevention:**
- Coordinate who records transactions
- Review Daily Consumption Report
- If duplicate found, note it and adjust next day's recording

**Solution:**
- Cannot delete, but can record correcting transaction
- Contact administrator for guidance

---

#### Q: Can I see who created a transaction?

**A:** Transaction history shows when transactions were created. User information may be visible depending on your system version. Check with administrator.

---

#### Q: What's the difference between Allocated and Available quantity?

**For Projects:**
- **Allocated:** Total materials sent to the project (cumulative)
- **Consumed:** Total materials used at the project (cumulative)
- **Available:** Allocated - Consumed (what's left to use)

**Example:**
- Allocated 1000 kg cement to project
- Consumed 600 kg over time
- Available: 1000 - 600 = 400 kg (still at project, not used yet)

---

#### Q: Can I create subcategories for resources?

**A:** No. The system has fixed categories. Use clear naming in the Resource Name field to subcategorize.

**Example:**
- Category: Raw Materials
- Name: "Portland Cement - Grade 42.5"
- Name: "Portland Cement - Grade 52.5"

---

#### Q: How do I know which purchases belong to which project?

**A:** Purchases always go to Hub first. Then you allocate from Hub to projects. This ensures central inventory control.

**Tracking:**
1. Purchase goes to Hub
2. View Transaction History for the purchase
3. Then view Allocation transactions to see where it went
4. Or use Project Resource Usage Report

---

#### Q: What if a supplier gives me a discount?

**A:** Enter the discounted price as the Unit Price when recording purchase.

**Example:**
- Normal price: 25 AED/kg
- Discount: 10%
- Discounted price: 22.50 AED/kg
- Enter 22.50 in the Unit Price field
- Add note: "10% discount applied"

---

#### Q: Can I record returns to supplier?

**A:** This feature may not be built-in. 

**Workaround:**
1. Note it in the resource description or notes
2. Contact administrator for guidance
3. May need custom transaction or adjustment

---

#### Q: How do I backup my data?

**A:** Contact your administrator. They handle database backups on the server side.

**What you can do:**
- Regularly export reports
- Save Excel files to your computer
- Keep copies in cloud storage (Dropbox, Google Drive, etc.)

---

## Glossary

### Terms and Definitions

**Allocate / Allocation**
- Moving materials from Hub (central warehouse) to a project site
- Increases project inventory, decreases Hub inventory
- Example: "Allocate 500 kg cement to Building A"

**Available Quantity**
- Amount of material at a project that hasn't been consumed yet
- Formula: Allocated - Consumed
- Example: Allocated 1000 kg, Consumed 600 kg, Available 400 kg

**Base Unit**
- The standard unit of measurement for a resource
- Examples: kg, liters, pieces, meters
- Cannot be changed after resource creation

**Category**
- Classification group for resources
- Options: Raw Materials, Tools, Equipment, Consumables, Others
- Helps organize and find materials

**Completed (Status)**
- Project status indicating work is finished
- No more transactions allowed
- Unused materials returned to Hub

**Consume / Consumption**
- Recording actual usage of materials at a project
- Permanently reduces available quantity
- Cannot be undone
- Example: "Consume 250 kg cement for foundation work"

**Hub**
- Central warehouse or main storage location
- Where all purchases arrive
- Materials are allocated from Hub to projects

**Immutable**
- Cannot be changed or deleted
- Applies to all transactions
- Ensures audit trail integrity

**Inventory Transaction**
- Record of any material movement
- Includes: what, when, where, how much, cost
- Six types: Purchase, Allocation In/Out, Consumption, Transfer In/Out

**Ledger**
- Complete history of all inventory movements
- Shows running balance
- Permanent and immutable

**Pending (Status)**
- Project status indicating not yet started
- No material transactions allowed
- Change to Active to begin work

**Project**
- Construction site or work location
- Has its own inventory of allocated materials
- Three statuses: Pending, Active, Completed

**Purchase**
- Buying materials from supplier
- Always adds to Hub inventory
- Records price, supplier, quantity

**Resource**
- A type of material, tool, or equipment
- Examples: Cement, Steel, Hammer, Drill
- Identified by unique SKU

**SKU (Stock Keeping Unit)**
- Unique identifier for a resource
- Like a product code
- Example: "CEM-001", "STEEL-16MM"
- Must be unique across all resources

**Transaction**
- See "Inventory Transaction"

**Transfer**
- Moving materials from one project to another
- Two atomic transactions: Transfer Out (source) and Transfer In (destination)
- Requires both projects to be Active

**Unit Conversion**
- Automatic conversion between compatible units
- Example: Purchasing in tons, tracking in kg
- System converts: 2 tons = 2000 kg

---

## Support and Contact

### Need Help?

1. **Check This Manual First**
   - Use Table of Contents to find your topic
   - Use Ctrl+F (or Cmd+F on Mac) to search for keywords

2. **Check the Help Center** (if available)
   - Left sidebar → Help Center
   - Quick FAQs and tips

3. **Contact Your Administrator**
   - Your admin is: [Your Administrator Name]
   - Email: [Administrator Email]
   - Phone: [Administrator Phone]

4. **Report a Bug**
   - Describe what you were doing
   - What you expected to happen
   - What actually happened
   - Take screenshots if possible
   - Send to administrator

### Training Resources

**For New Users:**
1. Read "Getting Started" section first
2. Practice with test data
3. Follow "Common Workflows" examples
4. Ask questions!

**For Administrators:**
- Contact system developer for advanced training
- Technical documentation may be available separately

---

## Quick Reference Cards

### Quick Action Reference

| I want to... | Go here... | Click this... |
|-------------|-----------|---------------|
| Add new material type | Resources | New Resource |
| Buy materials | Resources → [Resource] | Purchase |
| Send materials to project | Resources → [Resource] | Allocate to Project |
| Start a new project | Projects | New Project |
| Record material usage | Projects → [Project] | Consume Resource |
| Move materials between projects | Projects → [Project] | Transfer Resource |
| Finish a project | Projects → [Project] | Complete Project |
| See all movements | Transaction History | (view table) |
| Download report | Projects → [Project] | Export Daily/Usage |
| Add a user (Admin) | Users | New User |

### Keyboard Shortcuts

| Shortcut | Action |
|---------|--------|
| **Ctrl + K** (or **Cmd + K** on Mac) | Quick search |
| **Esc** | Close modal/dialog |
| **Tab** | Navigate between form fields |
| **Enter** | Submit form (when in form field) |

### Status Quick Reference

**Project Status:**
- 🟡 **Pending** → Planning phase, no transactions
- 🟢 **Active** → Work in progress, all actions available
- 🔵 **Completed** → Finished, locked

**Transaction Types:**
- 🟢 **PURCHASE** → Buying from supplier
- 🟡 **ALLOCATION_OUT** → Sending from Hub/Project
- 🔵 **ALLOCATION_IN** → Receiving at Project/Hub
- 🔴 **CONSUMPTION** → Using at project
- ⚪ **TRANSFER_OUT** → Sending to other project
- 🟣 **TRANSFER_IN** → Receiving from other project

### Common Filters

**Transaction History:**
- Last 7 days
- Last 30 days
- Last 90 days
- This month
- This year
- Custom range

**Resources:**
- In stock (Hub Stock > 0)
- Low stock (Hub Stock < threshold)
- Out of stock (Hub Stock = 0)
- By category

**Projects:**
- Active only
- Completed only
- By date range

---

## Document Information

**Document Title:** Factory Resource & Inventory Management System - Complete User Manual

**Version:** 1.0

**Last Updated:** January 31, 2026

**Prepared For:** All system users (Technical and Non-Technical)

**Document Purpose:** Complete onboarding and reference guide

**Copyright:** © 2026 All Rights Reserved

**Usage Rights:** This document is for internal use only. Do not distribute outside your organization without permission.

### Revision History

| Version | Date | Changes | Author |
|---------|------|---------|--------|
| 1.0 | January 31, 2026 | Initial release - Complete user manual | System Administrator |

### Document Feedback

Have suggestions for improving this manual?
- Contact your system administrator
- Suggest new sections or examples
- Report errors or unclear instructions
- Request additional workflow examples

---

## End of User Manual

Thank you for using the Factory Resource & Inventory Management System!

**Remember:**
- 📖 Keep this manual handy
- 🎯 Follow workflows carefully
- ✅ Record transactions daily
- 📊 Generate reports regularly
- 💬 Ask questions when unsure
- 🔒 Keep your login secure

**For technical support:** Contact your system administrator

**For business questions:** Contact your project manager

---

**Happy Inventory Managing! 🏗️📦**
