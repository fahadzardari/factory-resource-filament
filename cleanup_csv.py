#!/usr/bin/env python3
import csv
from collections import defaultdict

UNIT_MAPPING = {
    'nos.': 'piece', 'nos': 'piece', 'no.': 'piece', 'no': 'piece',
    'pcs': 'piece', 'pcs.': 'piece', 'pkt': 'piece', 'pkt.': 'piece',
    'sheet': 'sheet', 'sheets': 'sheet',
    'gallon': 'gallon', 'gal': 'gallon',
    'pair': 'pair', 'roll': 'roll',
    'ctn': 'carton', 'carton': 'carton', 'box': 'box',
    'kg': 'kg', 'g': 'g', 'mg': 'mg', 'ton': 'ton',
    'ltr': 'liter', 'liter': 'liter', 'liters': 'liters', 'ml': 'ml',
    'piece': 'piece', 'pieces': 'pieces', 'unit': 'unit', 'dozen': 'dozen',
    'pallet': 'pallet', 'bag': 'bag', 'sack': 'sack', 'bundle': 'bundle',
    'set': 'set', 'lot': 'piece', 'lm': 'm', 'mtrs': 'm', 'mtr': 'm',
    'm': 'm', 'cm': 'cm', 'mm': 'mm', 'ft': 'ft', 'inch': 'inch', 'in': 'inch',
    'sqm': 'sqm', 'sqft': 'sqft', 'drum': 'piece', 'panel': 'panel', 'tile': 'tile',
}

stats = defaultdict(int)
unmapped = defaultdict(int)

with open('List of Items.csv', 'r', encoding='utf-8') as infile:
    reader = csv.DictReader(infile)
    rows = list(reader)

print(f"Total rows: {len(rows)}")

clean_rows = []
category_rows = []

for idx, row in enumerate(rows):
    description = (row.get('Description') or '').strip()
    code = (row.get('Code') or '').strip()
    uom = (row.get('UOM') or '').strip()
    resource_type = (row.get('Resource Type') or '').strip()
    
    stats['total_rows'] += 1
    
    if not description:
        stats['skipped_empty'] += 1
        continue
    
    if not code or (not resource_type):
        category_rows.append(description)
        stats['category_headers'] += 1
        continue
    
    if resource_type.lower() == 'service':
        stats['skipped_service'] += 1
        continue
    
    uom_lower = uom.lower().strip()
    if not uom_lower:
        mapped_uom = 'piece'
    else:
        mapped_uom = UNIT_MAPPING.get(uom_lower)
        if not mapped_uom:
            matched = False
            for key, value in UNIT_MAPPING.items():
                if key in uom_lower:
                    mapped_uom = value
                    matched = True
                    break
            if not matched:
                unmapped[uom_lower] += 1
                mapped_uom = 'piece'
    
    clean_rows.append({
        'name': description,
        'sku': code,
        'category': 'Others',
        'base_unit': mapped_uom,
        'description': '',
    })
    stats['valid_rows'] += 1

output_file = 'List of Items - CLEANED.csv'
with open(output_file, 'w', encoding='utf-8', newline='') as outfile:
    fieldnames = ['name', 'sku', 'category', 'base_unit', 'description']
    writer = csv.DictWriter(outfile, fieldnames=fieldnames)
    writer.writeheader()
    writer.writerows(clean_rows)

print("\n" + "="*60)
print("✅ CSV CLEANUP COMPLETE")
print("="*60)
print(f"Valid items:            {stats['valid_rows']}")
print(f"Category headers:       {stats['category_headers']}")
print(f"Service items skipped:  {stats['skipped_service']}")
print(f"Empty rows skipped:     {stats['skipped_empty']}")
print()

if unmapped:
    print(f"Unmapped UOMs (defaulted to 'piece'):")
    for uom, count in sorted(unmapped.items(), key=lambda x: -x[1])[:15]:
        print(f"  '{uom}': {count}")
    print()

print(f"📁 Output: {output_file}")
print("="*60)
