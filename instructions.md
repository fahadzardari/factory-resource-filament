Project Proposal: Resource & Inventory Management System
(WE
B)
Date: January 9, 2026
1.
Executive Summary
This document outlines the proposal for a centralized Resource & Inventory Management
System. The system serves as a "single source of truth" for factory resources, tracking
materials from warehouse entry through their lifecycle in various production projects. The
primary goal is to eliminate stock leakage and enable real-time resource mobility between
active jobs without returning to the central warehouse.
2. Scope of Work
The project will be delivered as a web-based application with the following core modules:
A
. Centralized Inventory Control
("The Hub")
Global Resource Catalog: Master database of materials including Name, SKU, Category,
Unit Type, Rate/Cost (Purchase Price)
, and Total Quantity.
Stock Management:
Inbound: Interfaces to add new resources and shipments to the inventory.
Outbound: Interfaces to record usage, waste, or removal of stock from the system.
Real-time Calculations: Automated tracking of Total vs.
Available (Free) stock.
B. Project-Specific Workspaces
Virtual Warehouses: Each project functions as an isolated inventory container.
Outbound (Consumption) Tracking: System to log Outbound resources (
materials
consumed/used) vs. materials currently holding on-site.
Project Lifecycle: Status tracking
(
Active, Pending, Completed)
.
C. Resource Mobility (Core Innovation)
Inter-Project Transfers:
Direct transfer of stock from Project A to Project B with strict
validation to ensure data integrity.
Audit Trails: automated logging of "Who moved what, where, and when."
3. Technical Approach
We will utilize a Monolithic Architecture to ensure high performance while keeping hosting
costs minimal.
Framework: Laravel 12
(PHP)
.
Database: MySQL
(Relational Database)
.
Frontend:
Blade Templates
