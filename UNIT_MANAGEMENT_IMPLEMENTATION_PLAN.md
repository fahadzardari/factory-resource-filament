# Dynamic Unit Management System - Implementation Plan

## Problem Statement

**Current State:**
- Unit conversions are hardcoded in `GoodsReceiptNoteResource.php`
- Fixed arrays with ~15 unit categories (weight, volume, length, etc.)
- Can't add new units without code changes
- Not flexible for future expansions

**Future Need:**
- System admins should add units dynamically
- Should be able to create custom base units
- Should add sub-units and conversion factors
- Resources automatically can use these new units

---

## Proposed Solution Architecture

### **Phase 1: Database Structure** 🗄️

Create TWO new tables:

#### **Table 1: `units`**
```sql
CREATE TABLE units (
    id BIGINT PRIMARY KEY AUTO_INCREMENT
    name VARCHAR(255)           // e.g., "Kilogram", "Meter", "Liter"
    code VARCHAR(50) UNIQUE     // e.g., "kg", "m", "l"
    unit_type VARCHAR(100)      // e.g., "weight", "length", "volume", "count"
    is_base_unit BOOLEAN        // TRUE if this is the base unit for its type
    description TEXT            // Optional description
    created_at TIMESTAMP
    updated_at TIMESTAMP
)
```

**Example Data:**
```
| id | name           | code   | unit_type | is_base_unit | description
|----|----------------|--------|-----------|--------------|-------------
| 1  | Kilogram       | kg     | weight    | true         | Base unit for weight
| 2  | Gram           | g      | weight    | false        | 1000g = 1kg
| 3  | Pound          | lb     | weight    | false        | 2.204lb = 1kg
| 4  | Meter          | m      | length    | true         | Base unit for length
| 5  | Centimeter     | cm     | length    | false        | 100cm = 1m
| 6  | Inch           | inch   | length    | false        | 39.37inch = 1m
```

#### **Table 2: `unit_conversions`**
```sql
CREATE TABLE unit_conversions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT
    from_unit_id BIGINT         // FK to units.id (e.g., kg)
    to_unit_id BIGINT           // FK to units.id (e.g., g)
    conversion_factor DECIMAL(20,10)  // How many of from_unit = 1 of to_unit
                                      // e.g., kg→g: 1kg = 1000g, so factor = 1000
    created_at TIMESTAMP
    updated_at TIMESTAMP
    
    UNIQUE KEY unique_conversion (from_unit_id, to_unit_id)
)
```

**Example Data:**
```
| id | from_unit_id | to_unit_id | conversion_factor
|----|--------------|------------|-------------------
| 1  | 1 (kg)       | 2 (g)      | 1000
| 2  | 1 (kg)       | 3 (lb)     | 2.20462
| 3  | 2 (g)        | 1 (kg)     | 0.001
| 4  | 4 (m)        | 5 (cm)     | 100
| 5  | 4 (m)        | 6 (inch)   | 39.3701
```

---

### **Phase 2: Models** 🧬

#### **Create Unit Model**
```php
// app/Models/Unit.php
class Unit extends Model
{
    protected $fillable = ['name', 'code', 'unit_type', 'is_base_unit', 'description'];
    
    public function conversions() // Conversions FROM this unit
    {
        return $this->hasMany(UnitConversion::class, 'from_unit_id');
    }
    
    public function reverseConversions() // Conversions TO this unit
    {
        return $this->hasMany(UnitConversion::class, 'to_unit_id');
    }
    
    public function allConversions() // All possible conversions (both directions)
    {
        // Returns all units this can convert to
    }
}
```

#### **Create UnitConversion Model**
```php
// app/Models/UnitConversion.php
class UnitConversion extends Model
{
    protected $fillable = ['from_unit_id', 'to_unit_id', 'conversion_factor'];
    protected $casts = ['conversion_factor' => 'decimal:10'];
    
    public function fromUnit()
    {
        return $this->belongsTo(Unit::class, 'from_unit_id');
    }
    
    public function toUnit()
    {
        return $this->belongsTo(Unit::class, 'to_unit_id');
    }
}
```

---

### **Phase 3: Service Layer** ⚙️

Create new service to handle conversions dynamically:

```php
// app/Services/UnitConversionService.php
class UnitConversionService
{
    /**
     * Get all available units for a specific unit type
     * @param string $unitType (e.g., 'weight', 'length', 'volume')
     */
    public function getUnitsByType(string $unitType): Collection
    {
        return Unit::where('unit_type', $unitType)->get();
    }
    
    /**
     * Get all conversion options for a specific unit
     * @param int $unitId or string $unitCode
     */
    public function getConversionOptions($unit): array
    {
        // Returns: ['kg' => 'Kilogram (kg)', 'g' => 'Gram (g)', ...]
    }
    
    /**
     * Convert quantity from one unit to another
     */
    public function convertQuantity(
        float $quantity,
        string $fromUnitCode,
        string $toUnitCode
    ): float
    {
        // Find conversion factor and return: quantity * factor
    }
    
    /**
     * Get conversion factor between two units
     */
    public function getConversionFactor(string $from, string $to): float
    {
        // Returns the factor (e.g., 1000 for kg→g)
    }
}
```

---

### **Phase 4: Migration** 📝

Create migration file to add the two new tables.

---

### **Phase 5: Seeder** 🌱

Create `UnitSeeder` with initial data:
- Weight units (kg, g, ton, lb, oz)
- Length units (m, cm, mm, km, ft, inch)
- Volume units (liter, ml, gallon, m3)
- Count units (piece, dozen, box, carton, pallet)

This seeder will:
1. Create base units
2. Create all sub-units
3. Create all conversions between them

---

### **Phase 6: Filament Admin Resource** 🎛️

Create `UnitResource` so admins can:
- List all units
- Create new unit types
- View/Edit unit details
- Delete units (with warnings if in use)

Create `UnitConversionResource` so admins can:
- Add conversions between units
- Edit conversion factors
- Delete conversions

---

### **Phase 7: Update GRN Resource** 🔄

Modify `GoodsReceiptNoteResource.php`:
- Replace hardcoded arrays with database queries
- Use `UnitConversionService` to get available units
- Fetch conversions dynamically instead of static arrays

---

### **Phase 8: Update Resource Seeder** 🌾

Modify existing `ResourceSeeder.php`:
- When creating resources, fetch `base_unit` from Unit table
- Validate that base_unit exists in database
- Handle base_unit as FK instead of string

---

## Implementation Order

```
1️⃣  Create migrations (Unit & UnitConversion tables)
2️⃣  Create models (Unit, UnitConversion)
3️⃣  Create seeder (UnitSeeder with all initial data)
4️⃣  Create service (UnitConversionService)
5️⃣  Create Filament resources (UnitResource, UnitConversionResource)
6️⃣  Update GoodsReceiptNoteResource (use DB instead of hardcoded arrays)
7️⃣  Update ResourceSeeder (work with new Unit system)
8️⃣  Test everything (run seed and verify)
```

---

## Benefits of This Approach

| Benefit | Why It Matters |
|---------|----------------|
| **Dynamic** | Admins add units without code changes |
| **Scalable** | Unlimited unit types and sub-units |
| **Flexible** | Any custom conversion factors |
| **Auditable** | Full history of unit definitions |
| **Reusable** | Service layer works everywhere |
| **Maintainable** | Centralized unit logic |
| **User-Friendly** | Filament UI for admins |

---

## What Users See

### **For Regular Users (No Change)**
GRN form still shows:
- Resource selection
- Quantity input with unit dropdown
- Automatic conversion to base unit

**But now:** Units come from database, not hardcoded! ✨

### **For Admins (New Capability)**
New admin menu:
```
Inventory Management
├── Goods Receipts (GRN)
├── Inventory Transactions
├── Resources
├── Unit Management (NEW)
│   ├── Units
│   └── Unit Conversions
└── Suppliers
```

Admins can:
- Add new unit type: "Software Licenses"
- Add units: "License", "Bundle (10 licenses)"
- Set conversion: 10 licenses = 1 bundle
- Resources using these units immediately work!

---

## Example: Adding a New Unit Type

**Before:** Require developer, code change, migration, deploy  
**After:** Admin does this in UI:

```
1. Go to Admin → Unit Management → Units
2. Click "Create Unit"
   - Name: Software License
   - Code: lic
   - Type: software
   - Base Unit: ✓ Yes
3. Click "Create Unit" again
   - Name: License Bundle
   - Code: lic_bundle
   - Type: software
   - Base Unit: No
4. Go to Unit Conversions
5. Click "Create Conversion"
   - From: License
   - To: License Bundle
   - Factor: 10
6. Done! Resources can now use these units
```

Takes 2 minutes. No code involved. ✅

---

## Database Diagram

```
Resources
├── base_unit_id (FK → units.id)  [CHANGED from string to FK]
│
Units
├── id (PK)
├── name
├── code
├── unit_type
├── is_base_unit
└── created_at

UnitConversions
├── id (PK)
├── from_unit_id (FK)
├── to_unit_id (FK)
├── conversion_factor
└── created_at
```

---

## Detailed Implementation Steps (For Each Phase)

### **🔹 Phase 1: Migrations**

File: `database/migrations/YYYY_MM_DD_create_units_table.php`
```php
Schema::create('units', function (Blueprint $table) {
    $table->id();
    $table->string('name');            // "Kilogram"
    $table->string('code')->unique();  // "kg"
    $table->string('unit_type');       // "weight"
    $table->boolean('is_base_unit')->default(false);
    $table->text('description')->nullable();
    $table->timestamps();
});
```

File: `database/migrations/YYYY_MM_DD_create_unit_conversions_table.php`
```php
Schema::create('unit_conversions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('from_unit_id')->constrained('units');
    $table->foreignId('to_unit_id')->constrained('units');
    $table->decimal('conversion_factor', 20, 10);
    $table->timestamps();
    $table->unique(['from_unit_id', 'to_unit_id']);
});
```

---

### **🔹 Phase 2: Models**

Both models are simple, just relationships and casts.

---

### **🔹 Phase 3: Service**

~200 lines of clean, reusable code for unit operations.

---

### **🔹 Phase 4-8: Resources, Seeders, etc.**

Standard Filament patterns and Laravel seeders.

---

## Timeline Estimate

| Phase | Time | Complexity |
|-------|------|-----------|
| 1-2 (DB + Models) | 15 min | Easy |
| 3 (Service) | 20 min | Medium |
| 4 (Migration) | 10 min | Easy |
| 5 (Seeder) | 30 min | Medium (lots of data) |
| 6 (Filament UI) | 30 min | Medium |
| 7 (Update GRN) | 20 min | Medium |
| 8 (Update Resources) | 15 min | Easy |
| **Total** | **2.5 hours** | **Straightforward** |

---

## Ready to Proceed?

Once you approve this plan, I'll:

1. ✅ Create both migrations
2. ✅ Create both models  
3. ✅ Create UnitConversionService
4. ✅ Create UnitSeeder with all initial data
5. ✅ Create Filament resources for unit management
6. ✅ Update GoodsReceiptNoteResource to use database
7. ✅ Update ResourceSeeder to work with new system
8. ✅ Test and verify everything works

**Should I proceed with this plan?**

