"use client";

import { useMemo, useCallback } from "react";
import { HotTable } from "@handsontable/react-wrapper";
import { registerAllModules } from "handsontable/registry";
import "handsontable/styles/handsontable.min.css";
import "handsontable/styles/ht-theme-main.min.css";

import { useSheetData } from "@/hooks/useSheetData";
import { formatNumber } from "@/lib/formatters";
import type { SALData } from "@/types";
import ReadOnlySheet from "./ReadOnlySheet";

registerAllModules();

interface SALSheetProps {
  estimationId: number;
  version?: string;
}

export default function SALSheet({ estimationId, version }: SALSheetProps) {
  const { data, isLoading, error } = useSheetData<SALData>(
    estimationId,
    "sal",
    version
  );

  /** Build table data: filter out empty rows, add total. */
  const { tableData, totalRowIndex } = useMemo(() => {
    if (!data) return { tableData: [], totalRowIndex: -1 };

    const rows: (string | number)[][] = [];

    // Include lines that have any non-zero value
    for (const line of data.lines) {
      if (line.weight_kg === 0 && line.cost === 0 && line.price === 0) continue;
      rows.push([
        String(line.code),
        line.description,
        formatNumber(line.weight_kg, 3),
        formatNumber(line.cost, 2),
        formatNumber(line.markup, 3),
        formatNumber(line.price, 2),
        formatNumber(line.price_per_mt, 2),
      ]);
    }

    // Total row
    const idx = rows.length;
    rows.push([
      "",
      "TOTAL",
      formatNumber(data.total_weight_kg, 3),
      formatNumber(data.total_cost, 2),
      formatNumber(data.markup_ratio, 3),
      formatNumber(data.total_price, 2),
      formatNumber(data.price_per_mt, 2),
    ]);

    return { tableData: rows, totalRowIndex: idx };
  }, [data]);

  const cellsCallback = useCallback(
    (row: number, col: number) => {
      if (row === totalRowIndex) {
        return {
          readOnly: true,
          className: "htRight htMiddle text-xs font-bold bg-blue-50",
        };
      }
      return {
        readOnly: true,
        className:
          col >= 2
            ? "htRight htMiddle text-xs"
            : "htMiddle text-xs",
      };
    },
    [totalRowIndex]
  );

  return (
    <ReadOnlySheet isLoading={isLoading} error={error} sheetLabel="SAL">
      {(height) => (
        <HotTable
          data={tableData}
          colHeaders={[
            "Code",
            "Description",
            "Weight (kg)",
            "Cost (AED)",
            "Markup",
            "Price (AED)",
            "AED/MT",
          ]}
          colWidths={[60, 300, 100, 120, 80, 120, 100]}
          columns={Array.from({ length: 7 }, (_, i) => ({
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
