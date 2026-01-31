# Equipment Management System - Grouped Equipment Guide

## Overview
The equipment management system now uses **grouped equipment types** instead of tracking individual units. This means each row in the equipment table represents one equipment type (e.g., "Treadmill - Brand X - Model Y") rather than individual units.

## How It Works

### Adding Equipment

When you add equipment:
1. Fill in the equipment details (name, category, brand, model)
2. Enter the **quantity to add**
3. The system checks if this exact equipment type already exists
   - If it exists: adds the quantity to the existing record
   - If it's new: creates a new equipment type record

**Example:**
- You add "Treadmill - LifeFitness - T3" with quantity 5
- Later, you receive 3 more of the same model
- When you add them, the system updates the existing record to show 8 total

### Editing Equipment

When editing equipment:
- You can only update details (name, category, brand, model, purchase info, location, notes)
- **Quantities and status cannot be changed directly**
- To change quantities or status, use maintenance records

### Equipment Status (Auto-Calculated)

Equipment status is automatically determined based on availability:
- **Working**: All units available (quantity_available == total_quantity)
- **Under Maintenance**: Some units unavailable (0 < quantity_available < total_quantity)
- **Out of Order**: No units available (quantity_available == 0)

### Maintenance Records

Maintenance records now affect equipment quantities:

1. **Scheduled** or **In Progress** maintenance:
   - Specify how many units are affected
   - System reduces `quantity_available` by that amount
   - Equipment status automatically changes to "Under Maintenance" or "Out of Order"

2. **Completed** maintenance:
   - Specify how many units were fixed
   - System restores `quantity_available` by that amount
   - Equipment status automatically updates based on new availability

**Example:**
- Equipment: "Bench Press" - 10 total, 10 available, Status: Working
- Add maintenance: "In Progress", 3 units affected
- Result: 10 total, 7 available, Status: Under Maintenance
- Complete maintenance: "Completed", 3 units fixed
- Result: 10 total, 10 available, Status: Working

## Database Structure

### Equipment Table
- `equipment_id`: Auto-increment ID (primary key)
- `equipment_name`: Equipment type name
- `category`: Category (Cardio, Strength, etc.)
- `brand`: Manufacturer brand
- `model`: Model number/name
- `total_quantity`: Total units owned
- `quantity_available`: Units currently available
- `status`: Auto-calculated based on availability
- `purchase_date`, `purchase_price`, `location`, `notes`: Details

### Equipment Maintenance Table
- `maintenance_id`: Auto-increment ID
- `equipment_id`: Foreign key to equipment
- `maintenance_type`: Type of maintenance
- `units_affected`: Number of units in maintenance (NEW)
- `maintenance_date`: Date of maintenance
- `status`: Scheduled, In Progress, Completed
- `description`, `cost`, `performed_by`, `next_maintenance_date`: Details

## Key Changes from Previous System

### Before (Individual Units)
- Each equipment unit had its own row
- Equipment IDs like "EQ001", "EQ002", etc.
- Status per individual unit
- Hard to track total inventory

### After (Grouped Equipment)
- One row per equipment type
- Auto-increment IDs
- Status based on overall availability
- Easy inventory tracking with quantity fields

## Benefits

1. **Simplified Inventory**: See total and available quantities at a glance
2. **Automatic Status**: No manual status updates needed
3. **Smart Additions**: Adding same equipment automatically updates quantities
4. **Maintenance Tracking**: Maintenance directly affects availability
5. **Scalability**: Easy to manage large equipment inventories

## Usage Tips

- When purchasing new equipment of the same type, just "add equipment" again with the same details and new quantity
- Use maintenance records to track units under repair
- The system prevents double-counting by checking for existing equipment types
- Status updates happen automatically - no need to manually change status
