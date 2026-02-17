/**
 * Column configuration for building component sub-tables.
 * Each component type has its own column definitions that drive
 * the generic ComponentTable component.
 */

export interface ComponentColumnDef {
  key: string;
  label: string;
  type: "text" | "numeric" | "dropdown";
  width: number;
  dropdownOptions?: string[];
  /** Tooltip hint shown on column header hover */
  hint?: string;
}

// ── Openings (5 fields, max 9 rows) ──────────────────────────────

export const OPENINGS_COLUMNS: ComponentColumnDef[] = [
  {
    key: "location",
    label: "Location",
    type: "dropdown",
    width: 160,
    dropdownOptions: [
      "Front Sidewall",
      "Back Sidewall",
      "Left Endwall",
      "Right Endwall",
    ],
  },
  { key: "size", label: "Size (WxH)", type: "text", width: 120 },
  { key: "qty", label: "Qty", type: "numeric", width: 60 },
  {
    key: "purlin_support",
    label: "Purlin Support",
    type: "numeric",
    width: 100,
    hint: "Number of purlins supporting the opening header",
  },
  { key: "bracing", label: "Bracing", type: "numeric", width: 80, hint: "Number of bracing bays affected by this opening" },
];

export const OPENINGS_MAX_ROWS = 9;

// ── Accessories (3 fields, max 5 rows) ───────────────────────────

export const ACCESSORIES_COLUMNS: ComponentColumnDef[] = [
  { key: "description", label: "Description", type: "text", width: 220 },
  { key: "code", label: "Code", type: "text", width: 120 },
  { key: "qty", label: "Qty", type: "numeric", width: 80 },
];

export const ACCESSORIES_MAX_ROWS = 5;

// ── Crane (6 fields, max 3 rows) ─────────────────────────────────

export const CRANE_COLUMNS: ComponentColumnDef[] = [
  { key: "description", label: "Description", type: "text", width: 180 },
  { key: "sales_code", label: "Sales Code", type: "numeric", width: 90 },
  { key: "capacity", label: "Capacity (MT)", type: "numeric", width: 100 },
  {
    key: "duty",
    label: "Duty",
    type: "dropdown",
    width: 70,
    dropdownOptions: ["L", "M", "H"],
    hint: "L = Light, M = Medium, H = Heavy duty cycle",
  },
  {
    key: "rail_centers",
    label: "Rail Centers (m)",
    type: "numeric",
    width: 110,
    hint: "Distance between crane rail centerlines",
  },
  { key: "crane_run", label: "Crane Run", type: "text", width: 140, hint: "Crane travel path. Format: count@spacing, e.g. 3@9.144" },
];

export const CRANE_MAX_ROWS = 3;

// ── Mezzanine (15 fields, max 3 rows) ────────────────────────────

export const MEZZANINE_COLUMNS: ComponentColumnDef[] = [
  { key: "description", label: "Description", type: "text", width: 160 },
  { key: "sales_code", label: "Sales Code", type: "numeric", width: 80 },
  { key: "col_spacing", label: "Col Spacing", type: "text", width: 110, hint: "Spacing pattern: count@distance, e.g. 2@6" },
  { key: "beam_spacing", label: "Beam Spacing", type: "text", width: 110, hint: "Spacing pattern: count@distance, e.g. 1@6" },
  { key: "joist_spacing", label: "Joist Spacing", type: "text", width: 110, hint: "Spacing pattern: count@distance, e.g. 1@3" },
  {
    key: "clear_height",
    label: "Clear Height (m)",
    type: "numeric",
    width: 110,
  },
  {
    key: "double_welded",
    label: "Dbl Weld",
    type: "dropdown",
    width: 80,
    dropdownOptions: ["Yes", "No"],
  },
  { key: "deck_type", label: "Deck Type", type: "text", width: 100 },
  { key: "n_stairs", label: "# Stairs", type: "numeric", width: 70 },
  { key: "dead_load", label: "DL (kN/m\u00B2)", type: "numeric", width: 90 },
  { key: "live_load", label: "LL (kN/m\u00B2)", type: "numeric", width: 90 },
  {
    key: "additional_load",
    label: "Addl Load",
    type: "numeric",
    width: 80,
  },
  { key: "bu_finish", label: "BU Finish", type: "text", width: 90 },
  { key: "cf_finish", label: "CF Finish", type: "text", width: 90 },
  {
    key: "min_thickness",
    label: "Min Thick (mm)",
    type: "numeric",
    width: 100,
  },
];

export const MEZZANINE_MAX_ROWS = 3;

// ── Partition (12 fields, max 5 rows) ─────────────────────────────

export const PARTITION_COLUMNS: ComponentColumnDef[] = [
  { key: "description", label: "Description", type: "text", width: 160 },
  { key: "sales_code", label: "Sales Code", type: "numeric", width: 80 },
  {
    key: "direction",
    label: "Direction",
    type: "dropdown",
    width: 120,
    dropdownOptions: ["Longitudinal", "Transverse"],
    hint: "Longitudinal = along building length, Transverse = across width",
  },
  { key: "bu_finish", label: "BU Finish", type: "text", width: 90 },
  { key: "cf_finish", label: "CF Finish", type: "text", width: 90 },
  {
    key: "wind_speed",
    label: "Wind (km/h)",
    type: "numeric",
    width: 90,
  },
  { key: "col_spacing", label: "Col Spacing", type: "text", width: 110 },
  { key: "height", label: "Height (m)", type: "numeric", width: 90 },
  {
    key: "opening_height",
    label: "Opening Ht (m)",
    type: "numeric",
    width: 100,
  },
  {
    key: "front_sheeting",
    label: "Front Sheet",
    type: "text",
    width: 100,
  },
  { key: "back_sheeting", label: "Back Sheet", type: "text", width: 100 },
  { key: "insulation", label: "Insulation", type: "text", width: 100 },
];

export const PARTITION_MAX_ROWS = 5;

// ── Canopy (16 fields, max 5 rows) ───────────────────────────────

export const CANOPY_COLUMNS: ComponentColumnDef[] = [
  { key: "description", label: "Description", type: "text", width: 160 },
  { key: "sales_code", label: "Sales Code", type: "numeric", width: 80 },
  {
    key: "frame_type",
    label: "Frame Type",
    type: "dropdown",
    width: 130,
    dropdownOptions: ["Roof Extension", "Lean-To", "Fascia"],
    hint: "Roof Extension = extends main roof. Lean-To = separate structure. Fascia = vertical wall cladding",
  },
  {
    key: "location",
    label: "Location",
    type: "dropdown",
    width: 110,
    dropdownOptions: ["Front", "Back", "Left", "Right", "All Around"],
  },
  { key: "height", label: "Height (m)", type: "numeric", width: 90 },
  { key: "width", label: "Width (m)", type: "numeric", width: 80 },
  { key: "col_spacing", label: "Col Spacing", type: "text", width: 110 },
  {
    key: "roof_sheeting",
    label: "Roof Sheet",
    type: "text",
    width: 100,
  },
  { key: "drainage", label: "Drainage", type: "text", width: 100 },
  { key: "soffit", label: "Soffit", type: "text", width: 90 },
  {
    key: "wall_sheeting",
    label: "Wall Sheet",
    type: "text",
    width: 100,
  },
  {
    key: "internal_sheeting",
    label: "Internal Sheet",
    type: "text",
    width: 100,
  },
  { key: "bu_finish", label: "BU Finish", type: "text", width: 90 },
  { key: "cf_finish", label: "CF Finish", type: "text", width: 90 },
  {
    key: "live_load",
    label: "LL (kN/m\u00B2)",
    type: "numeric",
    width: 90,
  },
  {
    key: "wind_speed",
    label: "Wind (km/h)",
    type: "numeric",
    width: 90,
  },
];

export const CANOPY_MAX_ROWS = 5;

// ── Liner / Ceiling (9 fields, max 5 rows) ──────────────────────

export const LINER_COLUMNS: ComponentColumnDef[] = [
  { key: "description", label: "Description", type: "text", width: 160 },
  { key: "sales_code", label: "Sales Code", type: "numeric", width: 80 },
  {
    key: "type",
    label: "Type",
    type: "dropdown",
    width: 120,
    dropdownOptions: ["Roof Liner", "Wall Liner", "Both"],
    hint: "Roof Liner = roof only, Wall Liner = walls only, Both = roof + walls",
  },
  { key: "roof_liner_code", label: "Roof Liner Code", type: "text", width: 120, hint: "Product code e.g. S5OW, A5OW, PUA50" },
  { key: "wall_liner_code", label: "Wall Liner Code", type: "text", width: 120, hint: "Product code e.g. S5OW, A5OW, PUS50" },
  { key: "roof_area", label: "Roof Area (m\u00B2)", type: "numeric", width: 110, hint: "Manual override. Leave 0 for auto-calculation" },
  { key: "wall_area", label: "Wall Area (m\u00B2)", type: "numeric", width: 110, hint: "Manual override. Leave 0 for auto-calculation" },
  { key: "roof_openings_area", label: "Roof Openings (m\u00B2)", type: "numeric", width: 130, hint: "Area to deduct from roof (skylights etc.)" },
  { key: "wall_openings_area", label: "Wall Openings (m\u00B2)", type: "numeric", width: 130, hint: "Area to deduct from walls (doors, windows)" },
];

export const LINER_MAX_ROWS = 5;

// ── Component type registry ──────────────────────────────────────

export type ComponentType =
  | "openings"
  | "accessories"
  | "cranes"
  | "mezzanines"
  | "partitions"
  | "canopies"
  | "liners";

export interface ComponentConfig {
  key: ComponentType;
  label: string;
  columns: ComponentColumnDef[];
  maxRows: number;
}

export const COMPONENT_CONFIGS: ComponentConfig[] = [
  {
    key: "openings",
    label: "Openings",
    columns: OPENINGS_COLUMNS,
    maxRows: OPENINGS_MAX_ROWS,
  },
  {
    key: "accessories",
    label: "Accessories",
    columns: ACCESSORIES_COLUMNS,
    maxRows: ACCESSORIES_MAX_ROWS,
  },
  {
    key: "cranes",
    label: "Crane",
    columns: CRANE_COLUMNS,
    maxRows: CRANE_MAX_ROWS,
  },
  {
    key: "mezzanines",
    label: "Mezzanine",
    columns: MEZZANINE_COLUMNS,
    maxRows: MEZZANINE_MAX_ROWS,
  },
  {
    key: "partitions",
    label: "Partition",
    columns: PARTITION_COLUMNS,
    maxRows: PARTITION_MAX_ROWS,
  },
  {
    key: "canopies",
    label: "Canopy",
    columns: CANOPY_COLUMNS,
    maxRows: CANOPY_MAX_ROWS,
  },
  {
    key: "liners",
    label: "Liner / Ceiling",
    columns: LINER_COLUMNS,
    maxRows: LINER_MAX_ROWS,
  },
];
