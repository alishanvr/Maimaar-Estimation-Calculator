// ── API Response Types ─────────────────────────────────────────────

export interface PaginationLinks {
  first: string | null;
  last: string | null;
  prev: string | null;
  next: string | null;
}

export interface PaginationMeta {
  current_page: number;
  from: number | null;
  last_page: number;
  path: string;
  per_page: number;
  to: number | null;
  total: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  links: PaginationLinks;
  meta: PaginationMeta;
}

// ── User ───────────────────────────────────────────────────────────

export interface User {
  id: number;
  name: string;
  email: string;
  role: "admin" | "user";
  status: "active" | "inactive";
  company_name: string | null;
  phone: string | null;
}

// ── Design Configuration ───────────────────────────────────────────

export interface DesignConfiguration {
  id: number;
  category: string;
  key: string;
  value: string;
  label: string;
  sort_order: number;
  metadata: Record<string, string | number | null> | null;
}

// ── Input Data (fields sent inside estimation.input_data) ──────────

export interface InputData {
  // Building Dimensions
  bay_spacing?: string;
  span_widths?: string;
  back_eave_height?: number;
  front_eave_height?: number;
  left_roof_slope?: number;
  right_roof_slope?: number;

  // Structural Design
  frame_type?: string;
  base_type?: string;
  cf_finish?: string;
  panel_profile?: string;
  outer_skin_material?: string;

  // Frame Configuration
  min_thickness?: number;
  double_weld?: string;

  // Endwall Configuration
  left_endwall_columns?: string;
  left_endwall_type?: string;
  left_endwall_portal?: string;
  right_endwall_columns?: string;
  right_endwall_type?: string;
  right_endwall_portal?: string;

  // Secondary Members
  purlin_depth?: string;
  roof_sag_rods?: string;
  wall_sag_rods?: string;
  roof_sag_rod_dia?: string;
  wall_sag_rod_dia?: string;
  bracing_type?: string;

  // Loads
  dead_load?: number;
  live_load?: number;
  wind_speed?: number;
  collateral_load?: number;
  live_load_permanent?: number;
  live_load_floor?: number;
  additional_load?: number;

  // Panel & Materials
  roof_panel_code?: string;
  wall_panel_code?: string;
  core_thickness?: number;
  paint_system?: string;

  // Roof Sheeting
  roof_top_skin?: string;
  roof_core?: string;
  roof_bottom_skin?: string;
  roof_insulation?: string;

  // Wall Sheeting
  wall_top_skin?: string;
  wall_core?: string;
  wall_bottom_skin?: string;
  wall_insulation?: string;

  // Trims & Flashings
  trim_size?: string;
  back_eave_condition?: string;
  front_eave_condition?: string;

  // Insulation
  wwm_option?: string;

  // Finishes
  bu_finish?: string;

  // Roof Monitor
  monitor_type?: string;
  monitor_width?: number;
  monitor_height?: number;
  monitor_length?: number;

  // Freight
  freight_type?: string;
  freight_rate?: number;
  container_count?: number;
  container_rate?: number;

  // Sales Codes
  area_sales_code?: number;
  area_description?: string;
  acc_sales_code?: number;
  acc_description?: string;

  // Project / Pricing
  sales_office?: string;
  num_buildings?: number;
  erection_price?: number;

  // Markups
  markup_steel?: number;
  markup_panels?: number;
  markup_ssl?: number;
  markup_finance?: number;

  // Complex fields
  openings?: Opening[];
  accessories?: Accessory[];

  // Optional Components
  cranes?: Crane[];
  mezzanines?: Mezzanine[];
  partitions?: Partition[];
  canopies?: Canopy[];
  liners?: Liner[];

  [key: string]: unknown;
}

export interface Opening {
  location: string;
  size: string;
  qty: number;
  purlin_support?: number;
  bracing?: number;
}

export interface Accessory {
  description: string;
  code: string;
  qty: number;
}

// ── Optional Building Components ──────────────────────────────────

export interface Crane {
  description?: string;
  sales_code?: number;
  capacity?: number;
  duty?: string;
  rail_centers?: number;
  crane_run?: string;
}

export interface Mezzanine {
  description?: string;
  sales_code?: number;
  col_spacing?: string;
  beam_spacing?: string;
  joist_spacing?: string;
  clear_height?: number;
  double_welded?: string;
  deck_type?: string;
  n_stairs?: number;
  dead_load?: number;
  live_load?: number;
  additional_load?: number;
  bu_finish?: string;
  cf_finish?: string;
  min_thickness?: number;
}

export interface Partition {
  description?: string;
  sales_code?: number;
  direction?: string;
  bu_finish?: string;
  cf_finish?: string;
  wind_speed?: number;
  col_spacing?: string;
  height?: number;
  opening_height?: number;
  front_sheeting?: string;
  back_sheeting?: string;
  insulation?: string;
}

export interface Canopy {
  description?: string;
  sales_code?: number;
  frame_type?: string;
  location?: string;
  height?: number;
  width?: number;
  col_spacing?: string;
  roof_sheeting?: string;
  drainage?: string;
  soffit?: string;
  wall_sheeting?: string;
  internal_sheeting?: string;
  bu_finish?: string;
  cf_finish?: string;
  live_load?: number;
  wind_speed?: number;
}

export interface Liner {
  description?: string;
  sales_code?: number;
  type?: string;
  roof_liner_code?: string;
  wall_liner_code?: string;
  roof_area?: number;
  wall_area?: number;
  roof_openings_area?: number;
  wall_openings_area?: number;
}

// ── Estimation Summary (exposed when calculated) ───────────────────

export interface EstimationSummary {
  total_weight_kg: number;
  total_weight_mt: number;
  total_price_aed: number;
  price_per_mt: number;
  fob_price_aed: number;
  steel_weight_kg: number;
  panels_weight_kg: number;
}

// ── Estimation Item ────────────────────────────────────────────────

export interface EstimationItem {
  id: number;
  item_code: string;
  description: string;
  unit: string;
  quantity: number;
  weight_kg: number;
  rate: number;
  amount: number;
  category: string;
  sort_order: number;
}

// ── Estimation ─────────────────────────────────────────────────────

export type EstimationStatus = "draft" | "calculated" | "finalized";

export interface Estimation {
  id: number;
  parent_id: number | null;
  project_id: number | null;
  quote_number: string | null;
  revision_no: string | null;
  building_name: string | null;
  building_no: string | null;
  project_name: string | null;
  customer_name: string | null;
  salesperson_code: string | null;
  estimation_date: string | null;
  status: EstimationStatus;
  input_data: InputData;
  total_weight_mt: number | null;
  total_price_aed: number | null;
  summary: EstimationSummary | null;
  items: EstimationItem[];
  created_at: string;
  updated_at: string;
}

// ── Revision History ──────────────────────────────────────────────

export interface RevisionEntry {
  id: number;
  revision_no: string | null;
  status: EstimationStatus;
  total_weight_mt: number | null;
  total_price_aed: number | null;
  created_at: string;
  is_current: boolean;
}

// ── Comparison ────────────────────────────────────────────────────

export interface ComparisonEstimation {
  id: number;
  quote_number: string | null;
  revision_no: string | null;
  building_name: string | null;
  status: EstimationStatus;
  total_weight_mt: number | null;
  total_price_aed: number | null;
  summary: EstimationSummary | null;
  input_data: InputData;
}

// ── Markups (for calculate request) ────────────────────────────────

export interface Markups {
  steel?: number;
  panels?: number;
  ssl?: number;
  finance?: number;
}

// ── Sheet Data Types (Iteration 5 — Output Sheets) ────────────────

/** Recap sheet data (from results_data['summary']) */
export interface RecapData {
  total_weight_kg: number;
  total_weight_mt: number;
  total_price_aed: number;
  price_per_mt: number;
  fob_price_aed: number;
  steel_weight_kg: number;
  panels_weight_kg: number;
}

/** A single row in the Detail sheet */
export interface DetailItem {
  description: string;
  code: string;
  sales_code: number | string;
  cost_code: string;
  size: number | string;
  qty: number;
  is_header: boolean;
  sort_order: number;
  weight_per_unit: number;
  rate: number;
  unit: string;
}

/** A single FCPBS category */
export interface FCPBSCategory {
  key: string;
  name: string;
  quantity: number;
  weight_kg: number;
  weight_pct: number;
  material_cost: number;
  manufacturing_cost: number;
  overhead_cost: number;
  total_cost: number;
  markup: number;
  selling_price: number;
  selling_price_pct: number;
  price_per_mt: number;
  value_added: number;
  va_per_mt: number;
}

/** FCPBS subtotal structure */
export interface FCPBSSubtotal {
  weight_kg: number;
  material_cost: number;
  manufacturing_cost: number;
  overhead_cost: number;
  total_cost: number;
  selling_price: number;
  value_added: number;
}

/** FCPBS sheet data */
export interface FCPBSData {
  categories: Record<string, FCPBSCategory>;
  steel_subtotal: FCPBSSubtotal;
  panels_subtotal: FCPBSSubtotal;
  fob_price: number;
  total_price: number;
  total_weight_kg: number;
  total_weight_mt: number;
}

/** A single SAL line item */
export interface SALLine {
  code: number | string;
  description: string;
  weight_kg: number;
  cost: number;
  markup: number;
  price: number;
  price_per_mt: number;
}

/** SAL sheet data */
export interface SALData {
  lines: SALLine[];
  total_weight_kg: number;
  total_cost: number;
  total_price: number;
  markup_ratio: number;
  price_per_mt: number;
}

/** A single BOQ item */
export interface BOQItem {
  sl_no: number;
  description: string;
  unit: string;
  quantity: number;
  unit_rate: number;
  total_price: number;
}

/** BOQ sheet data */
export interface BOQData {
  items: BOQItem[];
  total_weight_mt: number;
  total_price: number;
}

/** JAF sheet data */
export interface JAFData {
  project_info: {
    quote_number: string;
    building_name: string;
    building_number: number;
    project_name: string;
    customer_name: string;
    salesperson_code: string;
    revision_number: number;
    date: string;
    sales_office: string;
  };
  pricing: {
    bottom_line_markup: number;
    value_added_l: number;
    value_added_r: number;
    total_weight_mt: number;
    primary_weight_mt: number;
    supply_price_aed: number;
    erection_price_aed: number;
    total_contract_aed: number;
    contract_value_usd: number;
    price_per_mt: number;
    min_delivery_weeks: number;
  };
  building_info: {
    num_non_identical_buildings: number;
    num_all_buildings: number;
    scope: string;
  };
  special_requirements: Record<string, string>;
  revision_history: unknown[];
}

// ── RAWMAT (Raw Material) ────────────────────────────────────────

export interface RawmatItem {
  no: number;
  code: string;
  cost_code: string;
  description: string;
  unit: string;
  quantity: number;
  unit_weight: number;
  total_weight: number;
  category: string;
  sources: string;
}

export interface RawmatCategoryStats {
  count: number;
  weight_kg: number;
}

export interface RawmatData {
  items: RawmatItem[];
  summary: {
    total_items_before: number;
    unique_materials: number;
    total_weight_kg: number;
    category_count: number;
  };
  categories: Record<string, RawmatCategoryStats>;
}

// ── Tab Definitions ────────────────────────────────────────────────

export type SheetTab =
  | "input"
  | "recap"
  | "detail"
  | "fcpbs"
  | "sal"
  | "boq"
  | "jaf"
  | "rawmat";

export const SHEET_TABS: { key: SheetTab; label: string }[] = [
  { key: "input", label: "Input" },
  { key: "recap", label: "Recap" },
  { key: "detail", label: "Detail" },
  { key: "fcpbs", label: "FCPBS" },
  { key: "sal", label: "SAL" },
  { key: "boq", label: "BOQ" },
  { key: "jaf", label: "JAF" },
  { key: "rawmat", label: "RAWMAT" },
];

// ── Project Types ──────────────────────────────────────────────────

export type ProjectStatus = "draft" | "in_progress" | "completed" | "archived";

export interface ProjectSummary {
  building_count: number;
  total_weight: number | null;
  total_price: number | null;
}

export interface Project {
  id: number;
  project_number: string;
  project_name: string;
  customer_name: string | null;
  location: string | null;
  description: string | null;
  status: ProjectStatus;
  summary: ProjectSummary;
  estimations?: Estimation[];
  created_at: string;
  updated_at: string;
}

export interface ActivityLogEntry {
  id: number;
  description: string;
  causer_name: string | null;
  created_at: string;
  properties: Record<string, unknown>;
}
