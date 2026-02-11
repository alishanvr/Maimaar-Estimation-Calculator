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
    unit: "e.g. 1@6.865+1@9.104",
    defaultValue: "1@6",
  },
  {
    label: "Span Widths",
    field: "span_widths",
    type: "text",
    unit: "e.g. 1@28.5",
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
    unit: "e.g. M45-250",
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

  // ── FRAME CONFIGURATION ──────────────────────────────────────────
  { label: "FRAME CONFIGURATION", type: "header" },
  {
    label: "Minimum Thickness",
    field: "min_thickness",
    type: "numeric",
    unit: "mm",
    defaultValue: 6,
  },
  {
    label: "Double Side Welding",
    field: "double_weld",
    type: "dropdown",
    dropdownOptions: ["Yes", "No"],
    unit: "",
    defaultValue: "No",
  },

  // ── ENDWALL CONFIGURATION ────────────────────────────────────────
  { label: "ENDWALL CONFIGURATION", type: "header" },
  {
    label: "Left Endwall Columns",
    field: "left_endwall_columns",
    type: "text",
    unit: "e.g. 1@4.5+1@5",
  },
  {
    label: "Left Endwall Type",
    field: "left_endwall_type",
    type: "dropdown",
    dropdownOptions: [
      "Bearing Frame",
      "Main Frame",
      "MF 1/2 Loaded",
      "False Rafter",
    ],
    unit: "",
    defaultValue: "Bearing Frame",
  },
  {
    label: "Left Endwall Portal",
    field: "left_endwall_portal",
    type: "dropdown",
    dropdownOptions: ["None", "Portal"],
    unit: "",
    defaultValue: "None",
  },
  {
    label: "Right Endwall Columns",
    field: "right_endwall_columns",
    type: "text",
    unit: "e.g. 1@4.5+1@5",
  },
  {
    label: "Right Endwall Type",
    field: "right_endwall_type",
    type: "dropdown",
    dropdownOptions: [
      "Bearing Frame",
      "Main Frame",
      "MF 1/2 Loaded",
      "False Rafter",
    ],
    unit: "",
    defaultValue: "Bearing Frame",
  },
  {
    label: "Right Endwall Portal",
    field: "right_endwall_portal",
    type: "dropdown",
    dropdownOptions: ["None", "Portal"],
    unit: "",
    defaultValue: "None",
  },

  // ── SECONDARY MEMBERS ────────────────────────────────────────────
  { label: "SECONDARY MEMBERS", type: "header" },
  {
    label: "Purlin Depth",
    field: "purlin_depth",
    type: "dropdown",
    dropdownOptions: ["200", "250", "360"],
    unit: "mm",
    defaultValue: "200",
  },
  {
    label: "Roof Sag Rods",
    field: "roof_sag_rods",
    type: "text",
    unit: "0 or A (auto)",
    defaultValue: "0",
  },
  {
    label: "Wall Sag Rods",
    field: "wall_sag_rods",
    type: "text",
    unit: "0 or A (auto)",
    defaultValue: "0",
  },
  {
    label: "Roof Sag Rod Dia",
    field: "roof_sag_rod_dia",
    type: "dropdown",
    dropdownOptions: ["12", "16", "20", "22"],
    unit: "mm",
    defaultValue: "12",
  },
  {
    label: "Wall Sag Rod Dia",
    field: "wall_sag_rod_dia",
    type: "dropdown",
    dropdownOptions: ["12", "16", "20", "22"],
    unit: "mm",
    defaultValue: "12",
  },
  {
    label: "Bracing Type",
    field: "bracing_type",
    type: "dropdown",
    dropdownOptions: ["Cables", "Rods", "Angles"],
    unit: "",
    defaultValue: "Cables",
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
  {
    label: "Live Load Permanent",
    field: "live_load_permanent",
    type: "numeric",
    unit: "kN/m\u00b2",
    defaultValue: 0,
  },
  {
    label: "Live Load Floor",
    field: "live_load_floor",
    type: "numeric",
    unit: "kN/m\u00b2",
    defaultValue: 0,
  },
  {
    label: "Additional Load",
    field: "additional_load",
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

  // ── ROOF SHEETING ────────────────────────────────────────────────
  { label: "ROOF SHEETING", type: "header" },
  {
    label: "Roof Top Skin",
    field: "roof_top_skin",
    type: "text",
    unit: "product code",
    defaultValue: "None",
  },
  {
    label: "Roof Core",
    field: "roof_core",
    type: "text",
    unit: "product code",
    defaultValue: "-",
  },
  {
    label: "Roof Bottom Skin",
    field: "roof_bottom_skin",
    type: "text",
    unit: "product code",
    defaultValue: "-",
  },
  {
    label: "Roof Insulation",
    field: "roof_insulation",
    type: "text",
    unit: "product code",
    defaultValue: "None",
  },

  // ── WALL SHEETING ────────────────────────────────────────────────
  { label: "WALL SHEETING", type: "header" },
  {
    label: "Wall Top Skin",
    field: "wall_top_skin",
    type: "text",
    unit: "product code",
    defaultValue: "None",
  },
  {
    label: "Wall Core",
    field: "wall_core",
    type: "text",
    unit: "product code",
    defaultValue: "-",
  },
  {
    label: "Wall Bottom Skin",
    field: "wall_bottom_skin",
    type: "text",
    unit: "product code",
    defaultValue: "-",
  },
  {
    label: "Wall Insulation",
    field: "wall_insulation",
    type: "text",
    unit: "product code",
    defaultValue: "None",
  },

  // ── TRIMS & FLASHINGS ───────────────────────────────────────────
  { label: "TRIMS & FLASHINGS", type: "header" },
  {
    label: "Trim Size",
    field: "trim_size",
    type: "dropdown",
    dropdownOptions: ["0.5 AZ", "0.7 AZ"],
    unit: "",
    defaultValue: "0.5 AZ",
  },
  {
    label: "Back Eave Condition",
    field: "back_eave_condition",
    type: "dropdown",
    dropdownOptions: [
      "Gutter+Dwnspts",
      "Curved",
      "Curved+VGutter",
      "Eave Trim",
    ],
    unit: "",
  },
  {
    label: "Front Eave Condition",
    field: "front_eave_condition",
    type: "dropdown",
    dropdownOptions: [
      "Gutter+Dwnspts",
      "Curved",
      "Curved+VGutter",
      "Eave Trim",
    ],
    unit: "",
  },

  // ── INSULATION ───────────────────────────────────────────────────
  { label: "INSULATION", type: "header" },
  {
    label: "WWM Option",
    field: "wwm_option",
    type: "dropdown",
    dropdownOptions: ["None", "Roof Only", "Wall Only", "Roof+Wall"],
    unit: "",
    defaultValue: "None",
  },

  // ── FINISHES ─────────────────────────────────────────────────────
  { label: "FINISHES", type: "header" },
  {
    label: "Built-Up Finish",
    field: "bu_finish",
    type: "text",
    unit: "finish code",
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

  // ── FREIGHT ──────────────────────────────────────────────────────
  { label: "FREIGHT", type: "header" },
  {
    label: "Freight Type",
    field: "freight_type",
    type: "dropdown",
    dropdownOptions: ["By Mammut", "By Customer", "FOB"],
    unit: "",
    defaultValue: "By Mammut",
  },
  {
    label: "Freight Rate",
    field: "freight_rate",
    type: "numeric",
    unit: "AED/MT",
    defaultValue: 0,
  },
  {
    label: "Container Count",
    field: "container_count",
    type: "numeric",
    unit: "",
    defaultValue: 6,
  },
  {
    label: "Container Rate",
    field: "container_rate",
    type: "numeric",
    unit: "AED/container",
    defaultValue: 2000,
  },

  // ── SALES CODES ──────────────────────────────────────────────────
  { label: "SALES CODES", type: "header" },
  {
    label: "Area Sales Code",
    field: "area_sales_code",
    type: "numeric",
    unit: "",
    defaultValue: 1,
  },
  {
    label: "Area Description",
    field: "area_description",
    type: "text",
    unit: "",
    defaultValue: "Building Area",
  },
  {
    label: "Accessories Sales Code",
    field: "acc_sales_code",
    type: "numeric",
    unit: "",
    defaultValue: 1,
  },
  {
    label: "Accessories Description",
    field: "acc_description",
    type: "text",
    unit: "",
    defaultValue: "Accessories",
  },

  // ── PROJECT / PRICING ────────────────────────────────────────────
  { label: "PROJECT / PRICING", type: "header" },
  {
    label: "Sales Office",
    field: "sales_office",
    type: "text",
    unit: "",
  },
  {
    label: "Number of Buildings",
    field: "num_buildings",
    type: "numeric",
    unit: "",
    defaultValue: 1,
  },
  {
    label: "Erection Price",
    field: "erection_price",
    type: "numeric",
    unit: "AED",
    defaultValue: 0,
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
