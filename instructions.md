Conceptual Guide: Factory Resource & Inventory System Architecture

Version: 1.1
Objective: Explain the architectural flaws in the initial approach and detail the "Ledger-Based" solution required to generate accurate Opening/Closing reports and Financial Valuations.

1. The Core Problem: State vs. History

The "Wrong" Approach (Current State Tracking)

In a typical simple inventory app, you might have a database column called current_stock.

Action: You buy 10 items.

Logic: You update the number from 50 to 60.

The Issue: The system has "forgotten" that it was 50 five minutes ago. It only knows it is 60 now.

Why this fails your requirements:
Your requirement asks for a Daily Report with Opening Balance and Closing Balance.

If you run a report for Yesterday, the system looks at current_stock (which is 60). It has no way to know that Yesterday morning the stock was actually 45.

You cannot reconstruct the past because you overwrote the data.

The "Right" Approach (Transaction Ledger)

Instead of tracking State (what is it now?), we track Events (what happened?).

Action: You buy 10 items.

Logic: You create a new record in a transactions table: +10 items added on Jan 30th.

Result:

Current Stock: Sum of all history (+10, -2, +5...).

History: You can scroll back to Jan 29th and sum all transactions up to that moment to get the exact stock level at that specific time.

2. The Solution: The "Single Source of Truth"

To meet your requirements, we must implement a Double-Entry Ledger System (simplified for inventory).

Concept 1: The Ledger Table

Imagine a physical notebook where you are only allowed to write new lines, never erase or change old ones. This is your inventory_transactions table.

Every single time a resource moves, a line is written here.

Date

Resource

Project

Type

Qty

Unit Price

Total Value

9:00 AM

Cement

(Hub)

PURCHASE

+100

$10.00

$1,000.00

10:00 AM

Cement

(Hub)

ALLOCATION_OUT

-20

$10.00

-$200.00

10:00 AM

Cement

Factory A

ALLOCATION_IN

+20

$10.00

$200.00

2:00 PM

Cement

Factory A

CONSUMPTION

-5

$10.00

-$50.00

Concept 2: The Two "Warehouses"

Your system has two distinct types of storage locations.

The Central Hub (Master Catalog):

This is the main warehouse.

Stock enters here via Purchases.

Stock leaves here via Allocations to projects.

The Project Sites (Virtual Warehouses):

These are temporary locations (Factories).

Stock enters here via Allocations (from Hub) or Transfers (from other Projects).

Stock leaves here via Consumption (used in work) or Transfers (sent elsewhere).

3. Workflow Walkthroughs

Here is how we handle specific user actions to ensure data integrity.

Scenario A: Purchasing New Stock

User Action: Admin clicks "Purchase Stock" on the Resource page.

System Action:

Creates a transaction record: Type PURCHASE, Quantity +100.

Records the Purchase Price ($10.00) in the unit_price column.

(Optional Optimization) Updates a cached number on the resource so we don't have to sum 1,000,000 rows every time we view the list.

Scenario B: Allocating to a Project (The Move)

This is a critical moment. Material cannot just "appear" at the project; it must come from somewhere.

User Action: Admin clicks "Allocate to Project A".

System Action (Atomic Transaction):

Check: Does the Hub actually have enough stock?

Debit Hub: Creates a transaction: Type ALLOCATION_OUT, Quantity -20.

Credit Project: Creates a transaction: Type ALLOCATION_IN, Quantity +20.

Result: The total global inventory hasn't changed, but the location has shifted.

Scenario C: Consumption (Using it up)

User Action: Project Manager clicks "Consume".

System Action:

Check: Does the Project have enough stock?

Debit Project: Creates a transaction: Type CONSUMPTION, Quantity -5.

Result: The stock leaves the system entirely. It is "gone" (transformed into a finished building).

4. The "Magic" of Reporting

This is where the Ledger approach pays off. You want a report for January 15th.

How to calculate "Opening Balance" (Jan 15th, 00:00:00)

The system asks: "What was the sum of all transactions created BEFORE Jan 15th?"
It adds up every purchase, subtracts every consumption, and processes every transfer from the beginning of time until Jan 14th, 11:59 PM.
Result: 500 Units.

How to calculate "Total In" (Jan 15th)

The system asks: "What is the sum of all Positive (+) transactions created ON Jan 15th?"
(Purchases + Incoming Transfers).
Result: +100 Units.

How to calculate "Total Out" (Jan 15th)

The system asks: "What is the sum of all Negative (-) transactions created ON Jan 15th?"
(Consumption + Outgoing Transfers).
Result: -50 Units.

How to calculate "Closing Balance" (Jan 15th, 23:59:59)

Math: Opening (500) + In (100) - Out (50) = 550 Units.

5. Summary of Required Changes

To fix your current project, you need to stop thinking about "Editing a Project" to change its stock. You must build:

A Service Layer: A strict set of rules that handles the math. No one is allowed to touch the database directly; they must go through the Service (e.g., InventoryService.moveStock()).

The Transaction Table: The history log described above.

Action Buttons: Instead of "Edit Resource", you need specific buttons: "Purchase", "Allocate", "Consume". Each button triggers a specific event type in the Service.

6. Financial Logic (Price Tracking)

You explicitly asked about Purchase Prices and Units. The Ledger handles this via the unit_price column.

A. Tracking Varying Purchase Prices

If you buy Cement at different rates, the Ledger records the exact rate for that specific purchase.

Jan 1: Buy 100 units @ $10. (Value added: $1000)

Jan 5: Buy 50 units @ $12. (Value added: $600)

When you generate a report, you can sum the quantity * unit_price to get the Total Value of Purchases.

B. Valuation of Outgoing Stock (Consumption)

When you consume stock, what price do we attach to it? You have two options for your Service Layer:

Weighted Average Cost (Recommended for Simplicity):

Total Value in Hub / Total Units in Hub = Average Price.

When allocating, use this Average Price.

Last Purchase Price:

Simply use the price of the most recent Purchase transaction.

C. Units of Measure

If you purchase in Cartons but consume in Pieces:

Best Practice: Store everything in the Ledger in the Base Unit (Pieces).

User Interface: When purchasing, ask for "Cartons", but the Service Layer converts it: 10 Cartons * 12 Pieces = 120 Pieces before writing to the ledger.