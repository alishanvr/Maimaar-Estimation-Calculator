"use client";

import { useRef, useCallback } from "react";
import { HotTable } from "@handsontable/react-wrapper";
import type { CellChange, ChangeSource } from "handsontable/common";
import type { Opening } from "@/types";

const LOCATIONS = [
  "Front Sidewall",
  "Back Sidewall",
  "Left Endwall",
  "Right Endwall",
];

const NUM_ROWS = 9;

interface OpeningsTableProps {
  openings: Opening[];
  onChange: (openings: Opening[]) => void;
}

function toTableData(openings: Opening[]): (string | number | null)[][] {
  const rows: (string | number | null)[][] = [];
  for (let i = 0; i < NUM_ROWS; i++) {
    const o = openings[i];
    rows.push([
      o?.location ?? null,
      o?.size ?? null,
      o?.qty ?? null,
      o?.purlin_support ?? null,
      o?.bracing ?? null,
    ]);
  }
  return rows;
}

export default function OpeningsTable({
  openings,
  onChange,
}: OpeningsTableProps) {
  const isUpdatingRef = useRef(false);

  const handleAfterChange = useCallback(
    (changes: CellChange[] | null, source: ChangeSource) => {
      if (!changes || source === "loadData" || isUpdatingRef.current) return;

      // Rebuild openings array from the current HotTable data
      const hotRef = document.querySelector(
        "[data-openings-table] .handsontable"
      );
      if (!hotRef) return;

      // Use changes to patch the current openings
      const updated = [...Array(NUM_ROWS)].map((_, i) => ({
        location: openings[i]?.location ?? "",
        size: openings[i]?.size ?? "",
        qty: openings[i]?.qty ?? 0,
        purlin_support: openings[i]?.purlin_support ?? 0,
        bracing: openings[i]?.bracing ?? 0,
      }));

      for (const [rowIdx, colIdx, , newVal] of changes) {
        const row = rowIdx as number;
        const col = colIdx as number;
        if (row < 0 || row >= NUM_ROWS) continue;

        if (col === 0) updated[row].location = String(newVal ?? "");
        else if (col === 1) updated[row].size = String(newVal ?? "");
        else if (col === 2)
          updated[row].qty = newVal ? Number(newVal) : 0;
        else if (col === 3)
          updated[row].purlin_support = newVal ? Number(newVal) : 0;
        else if (col === 4)
          updated[row].bracing = newVal ? Number(newVal) : 0;
      }

      // Filter out completely empty rows
      const filtered = updated.filter(
        (o) => o.location || o.size || o.qty > 0
      );
      onChange(filtered);
    },
    [openings, onChange]
  );

  const cellsCallback = useCallback((_row: number, col: number) => {
    if (col === 0) {
      return {
        type: "dropdown" as const,
        source: LOCATIONS,
        strict: false,
        allowInvalid: true,
        className: "text-xs",
      };
    }
    if (col >= 2) {
      return { type: "numeric" as const, className: "htRight text-xs" };
    }
    return { className: "text-xs" };
  }, []);

  return (
    <div data-openings-table>
      <HotTable
        data={toTableData(openings)}
        colHeaders={[
          "Location",
          "Size (WxH)",
          "Qty",
          "Purlin Support",
          "Bracing",
        ]}
        colWidths={[160, 120, 60, 100, 80]}
        columns={[
          { data: 0 },
          { data: 1 },
          { data: 2 },
          { data: 3 },
          { data: 4 },
        ]}
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
