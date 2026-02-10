/**
 * Input Sheet row configuration.
 *
 * Each row in the Handsontable grid maps to one of these definitions.
 * Header rows get a gray background and span across columns.
 * Field rows are editable in column B (Value).
 */

export type RowType = "header" | "text" | "numeric" | "dropdown" | "date";

export interface InputRowDef {
  /** Label displayed in column A */
  label: string;
  /** Which input_data field this maps to (undefined for headers) */
  field?: string;
  /** Whether this is a top-level estimation field vs input_data field */
  isTopLevel?: boolean;
  /** Cell type for column B */
  type: RowType;
  /** Unit or description shown in column C */
  unit?: string;
  /** Design configuration category to fetch dropdown options from */
  dropdownCategory?: string;
  /** Static dropdown options (when not from API) */
  dropdownOptions?: string[];
  /** Default value for new estimations */
  defaultValue?: string | number;
}

export const INPUT_ROWS: InputRowDef[] = [
  // ── PROJECT INFORMATION ──────────────────────────────────────────
  { label: "PROJECT INFORMATION", type: "header" },
  {
    label: "Quote Number",
    field: "quote_number",
    isTopLevel: true,
    type: "text",
    unit: "",
  },
  {
    label: "Revision No",
    field: "revision_no",
    isTopLevel: true,
    type: "text",
    unit: "",
  },
  {
    label: "Building Name",
    field: "building_name",
    isTopLevel: true,
    type: "text",
    unit: "",
  },
  {
    label: "Building No",
    field: "building_no",
    isTopLevel: true,
    type: "text",
    unit: "",
  },
  {
    label: "Project Name",
    field: "project_name",
    isTopLevel: true,
    type: "text",
    unit: "",
  },
  {
    label: "Customer Name",
    field: "customer_name",
    isTopLevel: true,
    type: "text",
    unit: "",
  },
  {
    label: "Salesperson Code",
    field: "salesperson_code",
    isTopLevel: true,
    type: "text",
    unit: "",
  },
  {
    label: "Estimation Date",
    field: "estimation_date",
    isTopLevel: true,
    type: "date",
    unit: "YYYY-MM-DD",
  },

  // ── BUILDING DIMENSIONS ──────────────────────────────────────────
  { label: "BUILDING DIMENSIONS", type: "header" },
  {
    label: "Bay Spacing",
    field: "bay_spacing",
    type: "text",
    unit: 'e.g. 1@6.865+1@9.104',
    defaultValue: "1@6",
  },
  {
    label: "Span Widths",
    field: "span_widths",
    type: "text",
    unit: 'e.g. 1@28.5',
    defaultValue: "1@28.5",
  },
  {
    label: "Back Eave Height",
    field: "back_eave_height",
    type: "numeric",
    unit: "m",
    defaultValue: 6.0,
  },
  {
    label: "Front Eave Height",
    field: "front_eave_height",
    type: "numeric",
    unit: "m",
    defaultValue: 6.0,
  },
  {
    label: "Left Roof Slope",
    field: "left_roof_slope",
    type: "numeric",
    unit: "\u00d710 (e.g. 10 = 1:10)",
    defaultValue: 1.0,
  },
  {
    label: "Right Roof Slope",
    field: "right_roof_slope",
    type: "numeric",
    unit: "\u00d710 (e.g. 10 = 1:10)",
    defaultValue: 1.0,
  },

  // ── STRUCTURAL DESIGN ────────────────────────────────────────────
  { label: "STRUCTURAL DESIGN", type: "header" },
  {
    label: "Frame Type",
    field: "frame_type",
    type: "dropdown",
    dropdownCategory: "frame_type",
    unit: "",
    defaultValue: "Clear Span",
  },
  {
    label: "Base Type",
    field: "base_type",
    type: "dropdown",
    dropdownOptions: ["Pinned Base", "Fixed Base"],
    unit: "",
    defaultValue: "Pinned Base",
  },
  {
    label: "CF Finish",
    field: "cf_finish",
    type: "dropdown",
    dropdownOptions: ["Painted", "Galvanized"],
    unit: "",
    defaultValue: "Painted",
  },
  {
    label: "Panel Profile",
    field: "panel_profile",
    type: "text",
    unit: 'e.g. M45-250',
    defaultValue: "M45-250",
  },
  {
    label: "Outer Skin Material",
    field: "outer_skin_material",
    type: "dropdown",
    dropdownOptions: ["AZ Steel", "Aluminum"],
    unit: "",
    defaultValue: "AZ Steel",
  },

  // ── LOADS ────────────────────────────────────────────────────────
  { label: "LOADS", type: "header" },
  {
    label: "Dead Load",
    field: "dead_load",
    type: "numeric",
    unit: "kN/m\u00b2",
    defaultValue: 0.1,
  },
  {
    label: "Live Load",
    field: "live_load",
    type: "numeric",
    unit: "kN/m\u00b2",
    defaultValue: 0.57,
  },
  {
    label: "Wind Speed",
    field: "wind_speed",
    type: "numeric",
    unit: "km/h",
    defaultValue: 0.7,
  },
  {
    label: "Collateral Load",
    field: "collateral_load",
    type: "numeric",
    unit: "kN/m\u00b2",
    defaultValue: 0,
  },

  // ── PANEL & MATERIALS ────────────────────────────────────────────
  { label: "PANEL & MATERIALS", type: "header" },
  {
    label: "Roof Panel Code",
    field: "roof_panel_code",
    type: "text",
    unit: "SSDB code",
  },
  {
    label: "Wall Panel Code",
    field: "wall_panel_code",
    type: "text",
    unit: "SSDB code",
  },
  {
    label: "Core Thickness",
    field: "core_thickness",
    type: "numeric",
    unit: "mm",
    defaultValue: 50,
  },
  {
    label: "Paint System",
    field: "paint_system",
    type: "dropdown",
    dropdownCategory: "paint_system",
    unit: "",
  },

  // ── ROOF MONITOR ─────────────────────────────────────────────────
  { label: "ROOF MONITOR", type: "header" },
  {
    label: "Monitor Type",
    field: "monitor_type",
    type: "dropdown",
    dropdownOptions: [
      "None",
      "Curve-CF",
      "Straight-CF",
      "Curve-HR",
      "Straight-HR",
    ],
    unit: "",
    defaultValue: "None",
  },
  {
    label: "Monitor Width",
    field: "monitor_width",
    type: "numeric",
    unit: "m",
  },
  {
    label: "Monitor Height",
    field: "monitor_height",
    type: "numeric",
    unit: "m",
  },
  {
    label: "Monitor Length",
    field: "monitor_length",
    type: "numeric",
    unit: "m",
  },

  // ── MARKUPS (for calculation) ────────────────────────────────────
  { label: "MARKUPS", type: "header" },
  {
    label: "Steel Markup",
    field: "markup_steel",
    type: "numeric",
    unit: "0-5",
    defaultValue: 0,
  },
  {
    label: "Panels Markup",
    field: "markup_panels",
    type: "numeric",
    unit: "0-5",
    defaultValue: 0,
  },
  {
    label: "SSL Markup",
    field: "markup_ssl",
    type: "numeric",
    unit: "0-5",
    defaultValue: 0,
  },
  {
    label: "Finance Markup",
    field: "markup_finance",
    type: "numeric",
    unit: "0-5",
    defaultValue: 0,
  },
];
