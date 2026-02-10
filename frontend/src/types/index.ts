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
  // Project info (stored at top level on estimation, but also in input_data for calc)
  bay_spacing?: string;
  span_widths?: string;
  back_eave_height?: number;
  front_eave_height?: number;
  left_roof_slope?: number;
  right_roof_slope?: number;
  dead_load?: number;
  live_load?: number;
  wind_speed?: number;
  collateral_load?: number;
  frame_type?: string;
  base_type?: string;
  cf_finish?: number;
  panel_profile?: string;
  outer_skin_material?: string;
  roof_panel_code?: string;
  wall_panel_code?: string;
  core_thickness?: number;
  paint_system?: string;
  monitor_type?: string;
  monitor_width?: number;
  monitor_height?: number;
  monitor_length?: number;
  openings?: Opening[];
  [key: string]: unknown;
}

export interface Opening {
  location: string;
  size: string;
  qty: number;
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

// ── Markups (for calculate request) ────────────────────────────────

export interface Markups {
  steel?: number;
  panels?: number;
  ssl?: number;
  finance?: number;
}

// ── Tab Definitions ────────────────────────────────────────────────

export type SheetTab =
  | "input"
  | "recap"
  | "detail"
  | "fcpbs"
  | "sal"
  | "boq"
  | "jaf";

export const SHEET_TABS: { key: SheetTab; label: string }[] = [
  { key: "input", label: "Input" },
  { key: "recap", label: "Recap" },
  { key: "detail", label: "Detail" },
  { key: "fcpbs", label: "FCPBS" },
  { key: "sal", label: "SAL" },
  { key: "boq", label: "BOQ" },
  { key: "jaf", label: "JAF" },
];
