"use client";

import { useMemo, useCallback } from "react";
import { HotTable } from "@handsontable/react-wrapper";
import { registerAllModules } from "handsontable/registry";
import "handsontable/styles/handsontable.min.css";
import "handsontable/styles/ht-theme-main.min.css";

import { useSheetData } from "@/hooks/useSheetData";
import { formatNumber, formatPct } from "@/lib/formatters";
import type { FCPBSData, FCPBSSubtotal } from "@/types";
import ReadOnlySheet from "./ReadOnlySheet";

registerAllModules();

interface FCPBSSheetProps {
  estimationId: number;
  version?: string;
}

/** Category display order with subtotal/total row markers. */
const CATEGORY_ORDER = [
  "A",
  "B",
  "C",
  "D",
  "__steelSub",
  "F",
  "G",
  "H",
  "I",
  "J",
  "__panelsSub",
  "__fob",
  "M",
  "O",
  "Q",
  "__totalSupply",
  "T",
  "__totalContract",
];

type RowType = "category" | "subtotal" | "total";

function subtotalRow(
  label: string,
  sub: FCPBSSubtotal
): (string | number)[] {
  return [
    "",
    label,
    "",
    formatNumber(sub.weight_kg, 1),
    "",
    formatNumber(sub.material_cost, 0),
    formatNumber(sub.manufacturing_cost, 0),
    formatNumber(sub.overhead_cost, 0),
    formatNumber(sub.total_cost, 0),
    "",
    formatNumber(sub.selling_price, 0),
    "",
    "",
    formatNumber(sub.value_added, 0),
    "",
  ];
}

export default function FCPBSSheet({
  estimationId,
  version,
}: FCPBSSheetProps) {
  const { data, isLoading, error } = useSheetData<FCPBSData>(
    estimationId,
    "fcpbs",
    version
  );

  const { tableData, rowTypes } = useMemo(() => {
    if (!data) return { tableData: [], rowTypes: [] as RowType[] };

    const rows: (string | number)[][] = [];
    const types: RowType[] = [];

    for (const key of CATEGORY_ORDER) {
      if (key === "__steelSub") {
        rows.push(subtotalRow("Sub Total (Steel)", data.steel_subtotal));
        types.push("subtotal");
      } else if (key === "__panelsSub") {
        rows.push(subtotalRow("Sub Total (Panels)", data.panels_subtotal));
        types.push("subtotal");
      } else if (key === "__fob") {
        rows.push([
          "",
          "FOB Price",
          "",
          "",
          "",
          "",
          "",
          "",
          "",
          "",
          formatNumber(data.fob_price, 0),
          "",
          "",
          "",
          "",
        ]);
        types.push("total");
      } else if (key === "__totalSupply") {
        rows.push([
          "",
          "Total Supply",
          "",
          formatNumber(data.total_weight_kg, 1),
          "",
          "",
          "",
          "",
          "",
          "",
          formatNumber(data.total_price, 0),
          "",
          "",
          "",
          "",
        ]);
        types.push("total");
      } else if (key === "__totalContract") {
        rows.push([
          "",
          "Total Contract",
          "",
          formatNumber(data.total_weight_kg, 1),
          "",
          "",
          "",
          "",
          "",
          "",
          formatNumber(data.total_price, 0),
          "",
          formatNumber(
            data.total_weight_kg > 0
              ? (data.total_price / data.total_weight_kg) * 1000
              : 0,
            0
          ),
          "",
          "",
        ]);
        types.push("total");
      } else {
        const cat = data.categories[key];
        if (!cat) continue;
        rows.push([
          cat.key,
          cat.name,
          formatNumber(cat.quantity, 1),
          formatNumber(cat.weight_kg, 1),
          formatPct(cat.weight_pct, 1),
          formatNumber(cat.material_cost, 0),
          formatNumber(cat.manufacturing_cost, 0),
          formatNumber(cat.overhead_cost, 0),
          formatNumber(cat.total_cost, 0),
          formatNumber(cat.markup, 3),
          formatNumber(cat.selling_price, 0),
          formatPct(cat.selling_price_pct, 1),
          formatNumber(cat.price_per_mt, 0),
          formatNumber(cat.value_added, 0),
          formatNumber(cat.va_per_mt, 0),
        ]);
        types.push("category");
      }
    }

    return { tableData: rows, rowTypes: types };
  }, [data]);

  const cellsCallback = useCallback(
    (row: number) => {
      const type = rowTypes[row];
      if (type === "subtotal") {
        return {
          readOnly: true,
          className: "htRight htMiddle text-xs font-bold bg-blue-50",
        };
      }
      if (type === "total") {
        return {
          readOnly: true,
          className: "htRight htMiddle text-xs font-bold bg-blue-100",
        };
      }
      return { readOnly: true, className: "htRight htMiddle text-xs" };
    },
    [rowTypes]
  );

  return (
    <ReadOnlySheet isLoading={isLoading} error={error} sheetLabel="FCPBS">
      {(height) => (
        <HotTable
          data={tableData}
          colHeaders={[
            "SN",
            "Category",
            "Qty",
            "Weight (kg)",
            "Wt %",
            "Material",
            "Manufg",
            "Overhead",
            "Total Cost",
            "Markup",
            "Selling Price",
            "Sell %",
            "AED/MT",
            "Value Added",
            "VA/MT",
          ]}
          colWidths={[
            40, 160, 60, 80, 60, 90, 90, 90, 100, 60, 100, 60, 80, 90, 70,
          ]}
          columns={Array.from({ length: 15 }, (_, i) => ({
            data: i,
            readOnly: true,
          }))}
          cells={cellsCallback}
          stretchH="all"
          height={height}
          readOnly={true}
          rowHeaders={false}
          contextMenu={false}
          fillHandle={false}
          manualColumnResize={true}
          autoWrapRow={false}
          autoWrapCol={false}
          licenseKey="non-commercial-and-evaluation"
          className="htLeft htMiddle text-sm"
        />
      )}
    </ReadOnlySheet>
  );
}
