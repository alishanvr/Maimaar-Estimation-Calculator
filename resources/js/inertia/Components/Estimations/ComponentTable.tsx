import { useRef, useCallback } from "react";
import { HotTable } from "@handsontable/react-wrapper";
import type { CellChange, ChangeSource } from "handsontable/common";
import type { ComponentColumnDef } from "./ComponentTableConfig";

interface ComponentTableProps {
  columns: ComponentColumnDef[];
  items: Record<string, unknown>[];
  maxRows: number;
  onChange: (items: Record<string, unknown>[]) => void;
}

/**
 * Converts the items array into a 2D table data array for Handsontable.
 */
function toTableData(
  items: Record<string, unknown>[],
  columns: ComponentColumnDef[],
  maxRows: number
): (string | number | null)[][] {
  const rows: (string | number | null)[][] = [];
  for (let i = 0; i < maxRows; i++) {
    const item = items[i];
    rows.push(
      columns.map((col) => {
        const val = item?.[col.key];
        if (val === undefined || val === null) return null;
        return val as string | number;
      })
    );
  }
  return rows;
}

/**
 * Generic reusable Handsontable component for optional building components.
 * Driven entirely by the column config — works for Crane, Mezzanine, Partition, and Canopy.
 */
export default function ComponentTable({
  columns,
  items,
  maxRows,
  onChange,
}: ComponentTableProps) {
  const isUpdatingRef = useRef(false);

  const handleAfterChange = useCallback(
    (changes: CellChange[] | null, source: ChangeSource) => {
      if (!changes || source === "loadData" || isUpdatingRef.current) return;

      // Build a working copy of the current items (expanded to maxRows)
      const updated: Record<string, unknown>[] = [...Array(maxRows)].map(
        (_, i) => {
          const existing = items[i] ?? {};
          const row: Record<string, unknown> = {};
          for (const col of columns) {
            row[col.key] = existing[col.key] ?? (col.type === "numeric" ? 0 : "");
          }
          return row;
        }
      );

      // Apply changes
      for (const [rowIdx, colIdx, , newVal] of changes) {
        const row = rowIdx as number;
        const col = colIdx as number;
        if (row < 0 || row >= maxRows || col < 0 || col >= columns.length)
          continue;

        const colDef = columns[col];
        if (colDef.type === "numeric") {
          updated[row][colDef.key] = newVal ? Number(newVal) : 0;
        } else {
          updated[row][colDef.key] = String(newVal ?? "");
        }
      }

      // Filter out completely empty rows (all text fields empty, all numeric fields 0)
      const filtered = updated.filter((row) => {
        return columns.some((col) => {
          const val = row[col.key];
          if (col.type === "numeric") return val !== 0 && val !== null;
          return val !== "" && val !== null;
        });
      });

      onChange(filtered);
    },
    [items, columns, maxRows, onChange]
  );

  const handleAfterGetColHeader = useCallback(
    (col: number, TH: HTMLTableCellElement) => {
      const colDef = columns[col];
      if (colDef?.hint) {
        TH.title = colDef.hint;
      }
    },
    [columns]
  );

  const cellsCallback = useCallback(
    (_row: number, col: number) => {
      const colDef = columns[col];
      if (!colDef) return {};

      if (colDef.type === "dropdown") {
        return {
          type: "dropdown" as const,
          source: colDef.dropdownOptions ?? [],
          strict: false,
          allowInvalid: true,
          className: "text-xs",
        };
      }
      if (colDef.type === "numeric") {
        return { type: "numeric" as const, className: "htRight text-xs" };
      }
      return { className: "text-xs" };
    },
    [columns]
  );

  return (
    <HotTable
      data={toTableData(items, columns, maxRows)}
      colHeaders={columns.map((c) => c.label)}
      colWidths={columns.map((c) => c.width)}
      columns={columns.map((_, i) => ({ data: i }))}
      cells={cellsCallback}
      afterChange={handleAfterChange}
      afterGetColHeader={handleAfterGetColHeader}
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
  );
}
