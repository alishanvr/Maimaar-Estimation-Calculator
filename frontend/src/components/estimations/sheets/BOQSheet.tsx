"use client";

import { useMemo, useCallback } from "react";
import { HotTable } from "@handsontable/react-wrapper";
import { registerAllModules } from "handsontable/registry";
import "handsontable/styles/handsontable.min.css";
import "handsontable/styles/ht-theme-main.min.css";

import { useSheetData } from "@/hooks/useSheetData";
import { formatNumber } from "@/lib/formatters";
import type { BOQData } from "@/types";
import ReadOnlySheet from "./ReadOnlySheet";

registerAllModules();

interface BOQSheetProps {
  estimationId: number;
  version?: string;
}

export default function BOQSheet({ estimationId, version }: BOQSheetProps) {
  const { data, isLoading, error } = useSheetData<BOQData>(
    estimationId,
    "boq",
    version
  );

  const { tableData, totalRowIndex } = useMemo(() => {
    if (!data) return { tableData: [], totalRowIndex: -1 };

    const rows: (string | number)[][] = data.items.map((item) => [
      item.sl_no,
      item.description,
      item.unit,
      formatNumber(item.quantity, 4),
      formatNumber(item.unit_rate, 2),
      formatNumber(item.total_price, 2),
    ]);

    // Total row
    const idx = rows.length;
    rows.push([
      "",
      "TOTAL",
      "MT",
      formatNumber(data.total_weight_mt, 4),
      "",
      formatNumber(data.total_price, 2),
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
          col >= 3
            ? "htRight htMiddle text-xs"
            : "htMiddle text-xs",
      };
    },
    [totalRowIndex]
  );

  return (
    <ReadOnlySheet isLoading={isLoading} error={error} sheetLabel="BOQ">
      {(height) => (
        <HotTable
          data={tableData}
          colHeaders={[
            "SL No",
            "Item Description",
            "Unit",
            "QTY",
            "Unit Rate (AED)",
            "Total Price (AED)",
          ]}
          colWidths={[60, 400, 60, 90, 120, 140]}
          columns={Array.from({ length: 6 }, (_, i) => ({
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
