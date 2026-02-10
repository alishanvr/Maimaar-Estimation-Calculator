"use client";

import { useRef, useEffect, useMemo, useCallback, useState } from "react";
import { HotTable } from "@handsontable/react-wrapper";
import { registerAllModules } from "handsontable/registry";
import type { CellChange, ChangeSource } from "handsontable/common";
import "handsontable/styles/handsontable.min.css";
import "handsontable/styles/ht-theme-main.min.css";

import { INPUT_ROWS, type InputRowDef } from "./InputSheetConfig";
import { useDesignConfigurations } from "@/hooks/useDesignConfigurations";
import type { Estimation, InputData } from "@/types";

registerAllModules();

interface InputSheetProps {
  estimation: Estimation;
  onInputDataChange: (inputData: InputData) => void;
  onFieldsChange: (fields: Partial<Estimation>) => void;
}

/**
 * Collects all unique design config categories that have `dropdownCategory` set,
 * so we can pre-fetch them.
 */
function getRequiredCategories(): string[] {
  const cats = new Set<string>();
  INPUT_ROWS.forEach((row) => {
    if (row.dropdownCategory) cats.add(row.dropdownCategory);
  });
  return Array.from(cats);
}

/**
 * Hook to fetch all needed design configuration dropdowns.
 * Returns a map of category → labels array.
 */
function useAllDropdowns() {
  const categories = useMemo(() => getRequiredCategories(), []);
  const results: Record<string, string[]> = {};

  // We call the hook for each category. Hooks must be called unconditionally
  // so we statically define the max we support (2 categories currently).
  const cat0 = useDesignConfigurations(categories[0] ?? "__none__");
  const cat1 = useDesignConfigurations(categories[1] ?? "__none__");

  if (categories[0]) results[categories[0]] = cat0.dropdownLabels;
  if (categories[1]) results[categories[1]] = cat1.dropdownLabels;

  return results;
}

export default function InputSheet({
  estimation,
  onInputDataChange,
  onFieldsChange,
}: InputSheetProps) {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const hotRef = useRef<any>(null);
  const containerRef = useRef<HTMLDivElement>(null);
  const [containerHeight, setContainerHeight] = useState(0);
  const dropdowns = useAllDropdowns();
  const isUpdatingRef = useRef(false);

  /** Measure the container height so we can give HotTable an explicit pixel value. */
  useEffect(() => {
    const el = containerRef.current;
    if (!el) return;

    const observer = new ResizeObserver((entries) => {
      for (const entry of entries) {
        setContainerHeight(entry.contentRect.height);
      }
    });
    observer.observe(el);
    setContainerHeight(el.clientHeight);
    return () => observer.disconnect();
  }, []);

  /** Get the current value for a row from the estimation data. */
  const getValueForRow = useCallback(
    (row: InputRowDef): string | number | null => {
      if (row.type === "header" || !row.field) return "";

      if (row.isTopLevel) {
        const val = estimation[row.field as keyof Estimation];
        return val !== null && val !== undefined ? String(val) : "";
      }

      // Handle CF Finish mapping: stored as number, shown as label
      if (row.field === "cf_finish") {
        const val = estimation.input_data?.[row.field];
        if (Number(val) === 3) return "Painted";
        if (Number(val) === 1) return "Galvanized";
        return row.defaultValue !== undefined ? String(row.defaultValue) : "";
      }

      const val = estimation.input_data?.[row.field];
      if (val !== null && val !== undefined) return val as string | number;
      return row.defaultValue !== undefined ? row.defaultValue : "";
    },
    [estimation]
  );

  /** Build the Handsontable data array from estimation state. */
  const tableData = useMemo(() => {
    return INPUT_ROWS.map((row) => [
      row.label,
      row.type === "header" ? "" : getValueForRow(row),
      row.type === "header" ? "" : (row.unit ?? ""),
    ]);
  }, [getValueForRow]);

  /** Sync Handsontable data whenever estimation changes externally. */
  useEffect(() => {
    const hot = hotRef.current?.hotInstance;
    if (!hot || isUpdatingRef.current) return;

    isUpdatingRef.current = true;
    hot.loadData(tableData);
    isUpdatingRef.current = false;
  }, [tableData]);

  /** Handle cell changes from Handsontable → update estimation data. */
  const handleAfterChange = useCallback(
    (changes: CellChange[] | null, source: ChangeSource) => {
      if (!changes || source === "loadData" || isUpdatingRef.current) return;

      const topLevelUpdates: Record<string, unknown> = {};
      const inputDataUpdates: Record<string, unknown> = {};

      for (const [rowIdx, , , newVal] of changes) {
        const rowDef = INPUT_ROWS[rowIdx];
        if (!rowDef || rowDef.type === "header" || !rowDef.field) continue;

        let processedValue: unknown = newVal;

        // Convert numeric fields
        if (rowDef.type === "numeric" && newVal !== "" && newVal !== null) {
          processedValue = Number(newVal);
        }

        // Convert CF Finish from label to stored value
        if (rowDef.field === "cf_finish") {
          processedValue =
            newVal === "Painted" ? 3 : newVal === "Galvanized" ? 1 : newVal;
        }

        if (rowDef.isTopLevel) {
          topLevelUpdates[rowDef.field] = processedValue;
        } else {
          inputDataUpdates[rowDef.field] = processedValue;
        }
      }

      // Propagate changes
      if (Object.keys(topLevelUpdates).length > 0) {
        onFieldsChange(topLevelUpdates as Partial<Estimation>);
      }

      if (Object.keys(inputDataUpdates).length > 0) {
        const mergedInputData = {
          ...estimation.input_data,
          ...inputDataUpdates,
        };
        onInputDataChange(mergedInputData as InputData);
      }
    },
    [estimation.input_data, onFieldsChange, onInputDataChange]
  );

  /** Per-cell configuration: readOnly, type, source for dropdowns. */
  const cellsCallback = useCallback(
    (row: number, col: number) => {
      const rowDef = INPUT_ROWS[row];
      if (!rowDef) return {};

      // Column A (label) and Column C (unit) are always read-only
      if (col === 0 || col === 2) {
        const isHeaderRow = rowDef.type === "header";
        return {
          readOnly: true,
          className: isHeaderRow
            ? "htMiddle font-bold bg-gray-200 text-gray-800 text-xs"
            : "htMiddle text-gray-600 text-xs",
        };
      }

      // Column B (value)
      if (col === 1) {
        if (rowDef.type === "header") {
          return {
            readOnly: true,
            className: "bg-gray-200",
          };
        }

        const cellProps: Record<string, unknown> = {
          className: "text-xs",
        };

        if (rowDef.type === "dropdown") {
          cellProps.type = "dropdown";
          cellProps.source =
            rowDef.dropdownOptions ??
            (rowDef.dropdownCategory
              ? dropdowns[rowDef.dropdownCategory] ?? []
              : []);
          cellProps.strict = false;
          cellProps.allowInvalid = true;
        } else if (rowDef.type === "numeric") {
          cellProps.type = "numeric";
        } else if (rowDef.type === "date") {
          cellProps.type = "text"; // Keep as text, user enters YYYY-MM-DD
        }

        return cellProps;
      }

      return {};
    },
    [dropdowns]
  );

  return (
    <div ref={containerRef} className="flex-1 overflow-hidden">
      {containerHeight > 0 && (
        <HotTable
          ref={hotRef}
          data={tableData}
          colHeaders={["Field", "Value", "Unit / Notes"]}
          colWidths={[220, 280, 180]}
          rowHeaders={false}
          columns={[
            { data: 0, readOnly: true },
            { data: 1 },
            { data: 2, readOnly: true },
          ]}
          cells={cellsCallback}
          afterChange={handleAfterChange}
          stretchH="all"
          autoWrapRow={false}
          autoWrapCol={false}
          height={containerHeight}
          licenseKey="non-commercial-and-evaluation"
          className="htLeft htMiddle text-sm"
          manualRowResize={false}
          manualColumnResize={true}
          contextMenu={false}
          fillHandle={false}
          undo={true}
        />
      )}
    </div>
  );
}
