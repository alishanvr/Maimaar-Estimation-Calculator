// ── Report Filter Parameters ──────────────────────────────────────
export interface ReportFilters {
  date_from?: string;
  date_to?: string;
  statuses?: string[];
  customer_name?: string;
  salesperson_code?: string;
}

// ── KPI Data ───────────────────────────────────────────────────────
export interface ReportKPIs {
  total_estimations: number;
  total_weight_mt: number;
  total_revenue_aed: number;
  avg_price_per_mt: number;
  finalized_count: number;
  calculated_count: number;
  draft_count: number;
}

// ── Monthly Trend Point ────────────────────────────────────────────
export interface MonthlyTrendPoint {
  month: string;
  label: string;
  count: number;
  revenue: number;
  weight_mt: number;
}

// ── Customer Revenue ───────────────────────────────────────────────
export interface CustomerRevenue {
  customer_name: string;
  total_price_aed: number;
  estimation_count: number;
}

// ── Weight Distribution ────────────────────────────────────────────
export interface WeightDistribution {
  steel_weight_kg: number;
  panels_weight_kg: number;
}

// ── Status Breakdown ───────────────────────────────────────────────
export interface StatusBreakdownItem {
  status: string;
  count: number;
}

// ── Price Per MT Trend ─────────────────────────────────────────────
export interface PricePerMtPoint {
  month: string;
  label: string;
  avg_price_per_mt: number;
}

// ── Cost Category ──────────────────────────────────────────────────
export interface CostCategoryItem {
  key: string;
  name: string;
  total_cost: number;
  total_selling: number;
}

// ── Filters Meta (for populating dropdowns) ────────────────────────
export interface FiltersMeta {
  customers: string[];
  salespersons: string[];
}

// ── Complete Dashboard Response ────────────────────────────────────
export interface ReportDashboardData {
  kpis: ReportKPIs;
  monthly_trends: MonthlyTrendPoint[];
  customer_revenue: CustomerRevenue[];
  weight_distribution: WeightDistribution;
  status_breakdown: StatusBreakdownItem[];
  price_per_mt_trend: PricePerMtPoint[];
  cost_category_breakdown: CostCategoryItem[];
  filters_meta: FiltersMeta;
}
