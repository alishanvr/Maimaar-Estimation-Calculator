"use client";

import { useMemo, useCallback } from "react";
import { HotTable } from "@handsontable/react-wrapper";
import { registerAllModules } from "handsontable/registry";
import "handsontable/styles/handsontable.min.css";
import "handsontable/styles/ht-theme-main.min.css";

import { useSheetData } from "@/hooks/useSheetData";
import type { DetailItem } from "@/types";
import ReadOnlySheet from "./ReadOnlySheet";

registerAllModules();

interface DetailSheetProps {
  estimationId: number;
  version?: string;
}

export default function DetailSheet({
  estimationId,
  version,
}: DetailSheetProps) {
  const { data, isLoading, error } = useSheetData<DetailItem[]>(
    estimationId,
    "detail",
    version
  );

  const tableData = useMemo(() => {
    if (!data) return [];
    return data.map((item) => {
      const size = typeof item.size === "number" ? item.size : 0;
      const totalWeight = item.is_header
        ? ""
        : (item.weight_per_unit * size * item.qty).toFixed(3);
      const totalCost = item.is_header
        ? ""
        : (item.rate * size * item.qty).toFixed(2);
      return [
        item.description,
        item.code,
        item.sales_code,
        item.cost_code,
        item.is_header ? "" : item.size,
        item.is_header ? "" : item.qty,
        item.unit,
        item.is_header ? "" : item.weight_per_unit,
        item.is_header ? "" : item.rate,
        totalWeight,
        totalCost,
      ];
    });
  }, [data]);

  const cellsCallback = useCallback(
    (row: number, col: number) => {
      if (!data) return { readOnly: true };
      const item = data[row];
      if (!item) return { readOnly: true };

      if (item.is_header) {
        return {
          readOnly: true,
          className: "htMiddle font-bold bg-gray-200 text-gray-800 text-xs",
        };
      }
      return {
        readOnly: true,
        className:
          col >= 4 && col <= 10
            ? "htRight htMiddle text-xs"
            : "htMiddle text-xs",
      };
    },
    [data]
  );

  return (
    <ReadOnlySheet isLoading={isLoading} error={error} sheetLabel="Detail">
      {(height) => (
        <HotTable
          data={tableData}
          colHeaders={[
            "Description",
            "Code",
            "Sales",
            "Cost Code",
            "Size",
            "Qty",
            "Unit",
            "Wt/Unit",
            "Rate",
            "Total Wt",
            "Total Cost",
          ]}
          colWidths={[220, 60, 60, 70, 70, 50, 50, 70, 80, 90, 100]}
          columns={Array.from({ length: 11 }, (_, i) => ({
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
