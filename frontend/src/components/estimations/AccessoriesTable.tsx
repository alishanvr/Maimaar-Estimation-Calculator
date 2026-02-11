"use client";

import { useRef, useCallback } from "react";
import { HotTable } from "@handsontable/react-wrapper";
import type { CellChange, ChangeSource } from "handsontable/common";
import type { Accessory } from "@/types";

const NUM_ROWS = 5;

interface AccessoriesTableProps {
  accessories: Accessory[];
  onChange: (accessories: Accessory[]) => void;
}

function toTableData(
  accessories: Accessory[]
): (string | number | null)[][] {
  const rows: (string | number | null)[][] = [];
  for (let i = 0; i < NUM_ROWS; i++) {
    const a = accessories[i];
    rows.push([a?.description ?? null, a?.code ?? null, a?.qty ?? null]);
  }
  return rows;
}

export default function AccessoriesTable({
  accessories,
  onChange,
}: AccessoriesTableProps) {
  const isUpdatingRef = useRef(false);

  const handleAfterChange = useCallback(
    (changes: CellChange[] | null, source: ChangeSource) => {
      if (!changes || source === "loadData" || isUpdatingRef.current) return;

      const updated = [...Array(NUM_ROWS)].map((_, i) => ({
        description: accessories[i]?.description ?? "",
        code: accessories[i]?.code ?? "",
        qty: accessories[i]?.qty ?? 0,
      }));

      for (const [rowIdx, colIdx, , newVal] of changes) {
        const row = rowIdx as number;
        const col = colIdx as number;
        if (row < 0 || row >= NUM_ROWS) continue;

        if (col === 0) updated[row].description = String(newVal ?? "");
        else if (col === 1) updated[row].code = String(newVal ?? "");
        else if (col === 2)
          updated[row].qty = newVal ? Number(newVal) : 0;
      }

      // Filter out completely empty rows
      const filtered = updated.filter(
        (a) => a.description || a.code || a.qty > 0
      );
      onChange(filtered);
    },
    [accessories, onChange]
  );

  const cellsCallback = useCallback((_row: number, col: number) => {
    if (col === 2) {
      return { type: "numeric" as const, className: "htRight text-xs" };
    }
    return { className: "text-xs" };
  }, []);

  return (
    <div data-accessories-table>
      <HotTable
        data={toTableData(accessories)}
        colHeaders={["Description", "Code", "Qty"]}
        colWidths={[250, 150, 80]}
        columns={[{ data: 0 }, { data: 1 }, { data: 2 }]}
        cells={cellsCallback}
        afterChange={handleAfterChange}
        stretchH="all"
        height="auto"
        rowHeaders={false}
        contextMenu={false}
        fillHandle={false}
        manualColumnResize={true}
        autoWrapRow={false}
        autoWrapCol={false}
        licenseKey="non-commercial-and-evaluation"
        className="htLeft htMiddle text-sm"
      />
    </div>
  );
}
