# Equipment Units System - Implementation Summary

## System Architecture

### Logical vs Physical Equipment

**Before**: Each equipment record represented a single physical unit
**After**: Equipment records represent equipment TYPES, individual units are tracked separately

### Database Structure

1. **equipment** table - Logical equipment types
   - Stores: equipment_name, category, total_quantity, quantity_available
   - Each row = one equipment type (e.g., "Treadmill")

2. **equipment_units** table - Physical units  
   - Stores: unit_id, equipment_id, unit_number, status, serial_number
   - Each row = one physical unit (e.g., "Treadmill #1", "Treadmill #2")

3. **equipment_maintenance** table - Maintenance records
   - Now links to specific units via unit_id
   - Tracks which exact unit received maintenance

## Features Implemented

### 1. Card Display (Grouped Equipment)
✅ Shows equipment TYPE (not individual units)
✅ Displays breakdown: Total | Available | Under Maintenance | Out of Order
✅ Auto-computed status:
   - **Available**: All units available
   - **Mixed**: Some units available, some in maintenance
   - **Under Maintenance**: All units in maintenance
   - **Out of Order**: No units available

### 2. Unit-Based Maintenance
✅ Click "Maintenance" → Modal shows checkbox list of all units
✅ Each unit displayed with:
   - Unit number (e.g., "Treadmill #1")
   - Current status (Available/Under Maintenance/Out of Order)
   - Status badge (color-coded)
✅ Can only select Available units for maintenance
✅ Select All / Deselect All buttons
✅ Units under maintenance are grayed out and disabled

### 3. Backend Logic
✅ Add Equipment: Auto-creates individual unit records
✅ Maintenance: Updates each selected unit's status
✅ Automatic recalculation of equipment totals
✅ Maintains data integrity with foreign keys

## How It Works

### Adding Equipment
```
User adds "Treadmill" with quantity 5
↓
System creates:
1. Equipment record: equipment_id=1, name="Treadmill", total=5
2. Unit records: 
   - unit_id=1, equipment_id=1, unit_number=1, status='Available'
   - unit_id=2, equipment_id=1, unit_number=2, status='Available'
   - unit_id=3, equipment_id=1, unit_number=3, status='Available'
   - unit_id=4, equipment_id=1, unit_number=4, status='Available'
   - unit_id=5, equipment_id=1, unit_number=5, status='Available'
```

### Performing Maintenance
```
User clicks "Maintenance" on Treadmill card
↓
Modal shows checkbox list:
☐ Treadmill #1 [Available]
☐ Treadmill #2 [Available]
☑ Treadmill #3 [Available] ← User selects
☐ Treadmill #4 [Available]
☑ Treadmill #5 [Available] ← User selects
↓
User saves
↓
System updates:
1. Creates maintenance records for units 3 and 5
2. Changes unit 3 status → 'Under Maintenance'
3. Changes unit 5 status → 'Under Maintenance'
4. Recalculates equipment: 5 total | 3 available | 2 under maintenance
5. Card status → 'Mixed'
```

### Completing Maintenance
```
User marks maintenance as "Completed"
↓
System updates:
1. Unit status → 'Available'
2. Recalculates: 5 total | 5 available | 0 under maintenance
3. Card status → 'Available'
```

## Setup Instructions

### 1. Run Database Migration
Open phpMyAdmin and run: [setup_equipment_units.sql](c:/xampp/htdocs/gym/setup_equipment_units.sql)

This will:
- Create `equipment_units` table
- Add unit_id column to `equipment_maintenance`
- Migrate existing equipment data to create units
- Set up triggers for auto-unit creation

### 2. Files Modified
- ✅ [equipment.php](c:/xampp/htdocs/gym/staff_view/equipment.php) - Main equipment page
- ✅ [get_equipment_units.php](c:/xampp/htdocs/gym/staff_view/get_equipment_units.php) - AJAX endpoint

### 3. Key Changes
- Equipment cards show unit breakdown instead of simple quantity
- Maintenance modal uses checkboxes instead of number input
- Status is auto-computed based on unit availability
- Brand, Model, Location removed from add form (simplified)

## Benefits

1. **Granular Tracking**: Know exactly which units are under maintenance
2. **Better Reporting**: See real-time breakdown of unit status
3. **Scalability**: Easy to manage gyms with many units of same equipment
4. **Data Integrity**: Foreign keys ensure maintenance records always link to valid units
5. **User-Friendly**: Visual checkbox interface instead of manual counting

## Visual Improvements

- Card design with color-coded status boxes
- Gradient header for equipment cards
- Hover effects on cards
- Responsive grid layout (3-4 cards desktop, 1 mobile)
- Icons for better UX
- Status badges with appropriate colors:
  - Available → Green
  - Mixed → Blue
  - Under Maintenance → Yellow
  - Out of Order → Red
