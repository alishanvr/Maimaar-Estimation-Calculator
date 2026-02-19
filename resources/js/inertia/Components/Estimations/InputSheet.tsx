import { useState, useRef, useEffect, useMemo, useCallback } from "react";
import { HotTable } from "@handsontable/react-wrapper";
import { registerAllModules } from "handsontable/registry";
import type { CellChange, ChangeSource } from "handsontable/common";

import { INPUT_ROWS, type InputRowDef } from "./InputSheetConfig";
import { useDesignConfigurations } from "../../hooks/useDesignConfigurations";
import { useCurrency } from "../../hooks/useCurrency";
import ComponentTable from "./ComponentTable";
import ComponentButtonBar from "./ComponentButtonBar";
import {
  COMPONENT_CONFIGS,
  type ComponentType,
} from "./ComponentTableConfig";
import type { Estimation, InputData } from "../../types";

registerAllModules();

interface InputSheetProps {
  estimation: Estimation;
  onInputDataChange: (inputData: InputData) => void;
  onFieldsChange: (fields: Partial<Estimation>) => void;
  readOnly?: boolean;
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
 * Returns a map of category -> labels array.
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
  readOnly = false,
}: InputSheetProps) {
  const { symbol: currencySymbol } = useCurrency();
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const hotRef = useRef<any>(null);
  const containerRef = useRef<HTMLDivElement>(null);
  const dropdowns = useAllDropdowns();
  const isUpdatingRef = useRef(false);

  /** On first load, merge defaults into input_data for any missing keys so
   *  the backend gets all required fields for calculation. */
  const defaultsMergedRef = useRef(false);
  useEffect(() => {
    if (defaultsMergedRef.current || !estimation.input_data) return;
    defaultsMergedRef.current = true;

    const currentData = estimation.input_data ?? {};
    const merged: Record<string, unknown> = { ...currentData };
    let hasMissing = false;

    for (const row of INPUT_ROWS) {
      if (row.type === "header" || !row.field || row.isTopLevel) continue;
      if (row.defaultValue !== undefined && !(row.field in currentData)) {
        hasMissing = true;
        merged[row.field] = row.defaultValue;
      }
    }

    if (hasMissing) {
      onInputDataChange(merged as InputData);
    }
  }, [estimation.input_data, onInputDataChange]);

  /** Get the current value for a row from the estimation data. */
  const getValueForRow = useCallback(
    (row: InputRowDef): string | number | null => {
      if (row.type === "header" || !row.field) return "";

      if (row.isTopLevel) {
        const val = estimation[row.field as keyof Estimation];
        return val !== null && val !== undefined ? String(val) : "";
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
      row.type === "header" ? "" : ((row.unit ?? "").replace(/AED/g, currencySymbol)),
    ]);
  }, [getValueForRow, currencySymbol]);

  /** Sync Handsontable data whenever estimation changes externally. */
  useEffect(() => {
    const hot = hotRef.current?.hotInstance;
    if (!hot || isUpdatingRef.current) return;

    isUpdatingRef.current = true;
    hot.loadData(tableData);
    isUpdatingRef.current = false;
  }, [tableData]);

  /** Handle cell changes from Handsontable -> update estimation data. */
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

  /**
   * After Tab/Enter lands on a header row, skip to the next editable row.
   * Mouse clicks on any column are allowed -- only keyboard navigation forces column B.
   */
  const isKeyNavRef = useRef(false);

  const handleBeforeKeyDown = useCallback((e: KeyboardEvent) => {
    if (e.key === "Tab" || e.key === "Enter") {
      isKeyNavRef.current = true;
    }
  }, []);

  const handleAfterSelection = useCallback(
    (row: number, col: number) => {
      const hot = hotRef.current?.hotInstance;
      if (!hot) return;

      const rowDef = INPUT_ROWS[row];
      if (!rowDef) return;

      // Only auto-redirect when navigating via Tab/Enter (not mouse clicks)
      if (isKeyNavRef.current) {
        isKeyNavRef.current = false;

        if (rowDef.type === "header") {
          // Skip header row -- move to next non-header row in column B
          let nextRow = row + 1;
          while (nextRow < INPUT_ROWS.length) {
            if (INPUT_ROWS[nextRow].type !== "header") {
              hot.selectCell(nextRow, 1);
              return;
            }
            nextRow++;
          }
          // If nothing below, try above
          let prevRow = row - 1;
          while (prevRow >= 0) {
            if (INPUT_ROWS[prevRow].type !== "header") {
              hot.selectCell(prevRow, 1);
              return;
            }
            prevRow--;
          }
        } else if (col !== 1) {
          // Tab/Enter should always land in Value column (B)
          hot.selectCell(row, 1);
        }
      }
    },
    []
  );

  /** Per-cell configuration: readOnly, type, source for dropdowns. */
  const cellsCallback = useCallback(
    (row: number, col: number) => {
      const rowDef = INPUT_ROWS[row];
      if (!rowDef) return {};

      // Column A (label) and Column C (unit) are always read-only
      if (col === 0 || col === 2) {
        const isHeaderRow = rowDef.type === "header";
        const cellProps: Record<string, unknown> = {
          readOnly: true,
          className: isHeaderRow
            ? "htMiddle font-bold bg-gray-200 text-gray-800 text-xs"
            : "htMiddle text-gray-600 text-xs",
        };
        // Add hint tooltip on label cells
        if (col === 0 && rowDef.hint) {
          cellProps.comment = { value: rowDef.hint, readOnly: true };
        }
        return cellProps;
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

  // Fixed height for the main grid based on row count
  const mainGridHeight = INPUT_ROWS.length * 23 + 30;

  // ── Optional Component State ──────────────────────────────────────

  /** Refs for scrolling to component sections */
  const componentRefs = useRef<Record<ComponentType, HTMLDivElement | null>>({
    openings: null,
    accessories: null,
    cranes: null,
    mezzanines: null,
    partitions: null,
    canopies: null,
    liners: null,
  });

  /** Determine which components are active based on existing data */
  const [activeComponents, setActiveComponents] = useState<
    Record<ComponentType, boolean>
  >(() => ({
    openings: (estimation.input_data?.openings?.length ?? 0) > 0,
    accessories: (estimation.input_data?.accessories?.length ?? 0) > 0,
    cranes: (estimation.input_data?.cranes?.length ?? 0) > 0,
    mezzanines: (estimation.input_data?.mezzanines?.length ?? 0) > 0,
    partitions: (estimation.input_data?.partitions?.length ?? 0) > 0,
    canopies: (estimation.input_data?.canopies?.length ?? 0) > 0,
    liners: (estimation.input_data?.liners?.length ?? 0) > 0,
  }));

  /** Sync active state when estimation data changes externally (e.g. Fill Test Data) */
  useEffect(() => {
    setActiveComponents({
      openings: (estimation.input_data?.openings?.length ?? 0) > 0,
      accessories: (estimation.input_data?.accessories?.length ?? 0) > 0,
      cranes: (estimation.input_data?.cranes?.length ?? 0) > 0,
      mezzanines: (estimation.input_data?.mezzanines?.length ?? 0) > 0,
      partitions: (estimation.input_data?.partitions?.length ?? 0) > 0,
      canopies: (estimation.input_data?.canopies?.length ?? 0) > 0,
      liners: (estimation.input_data?.liners?.length ?? 0) > 0,
    });
  }, [estimation.input_data?.openings, estimation.input_data?.accessories, estimation.input_data?.cranes, estimation.input_data?.mezzanines, estimation.input_data?.partitions, estimation.input_data?.canopies, estimation.input_data?.liners]);

  /** Toggle a component on/off. Off = clear its data. */
  const handleComponentToggle = useCallback(
    (type: ComponentType) => {
      const isCurrentlyActive = activeComponents[type];

      if (isCurrentlyActive) {
        // Remove: first update local state, then clear data (deferred to avoid
        // calling parent setState during this component's render cycle)
        setActiveComponents((prev) => ({ ...prev, [type]: false }));
        setTimeout(() => {
          const updated = { ...estimation.input_data };
          delete updated[type];
          onInputDataChange(updated as InputData);
        }, 0);
      } else {
        // Add: update local state, scroll to section after it renders
        setActiveComponents((prev) => ({ ...prev, [type]: true }));
        setTimeout(() => {
          componentRefs.current[type]?.scrollIntoView({
            behavior: "smooth",
            block: "start",
          });
        }, 100);
      }
    },
    [activeComponents, estimation.input_data, onInputDataChange]
  );

  /** Scroll to a component section */
  const handleComponentScrollTo = useCallback((type: ComponentType) => {
    componentRefs.current[type]?.scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
  }, []);

  /** Generic handler for component data changes */
  const handleComponentChange = useCallback(
    (type: ComponentType, items: Record<string, unknown>[]) => {
      onInputDataChange({
        ...estimation.input_data,
        [type]: items,
      } as InputData);
    },
    [estimation.input_data, onInputDataChange]
  );

  return (
    <div ref={containerRef} className="flex-1 overflow-auto">
      {/* Component toggle buttons */}
      <ComponentButtonBar
        activeComponents={activeComponents}
        onToggle={handleComponentToggle}
        onScrollTo={handleComponentScrollTo}
      />

      {/* Main input grid */}
      <div style={{ minHeight: mainGridHeight }}>
        <HotTable
          ref={hotRef}
          data={tableData}
          colHeaders={["Field", "Value", "Unit / Notes"]}
          colWidths={[220, 280, 180]}
          rowHeaders={false}
          columns={[
            { data: 0, readOnly: true },
            { data: 1, readOnly },
            { data: 2, readOnly: true },
          ]}
          cells={cellsCallback}
          afterChange={handleAfterChange}
          beforeKeyDown={handleBeforeKeyDown}
          afterSelection={handleAfterSelection}
          tabMoves={{ row: 1, col: 0 }}
          enterMoves={{ row: 1, col: 0 }}
          stretchH="all"
          autoWrapRow={false}
          autoWrapCol={false}
          height={mainGridHeight}
          licenseKey="non-commercial-and-evaluation"
          className="htLeft htMiddle text-sm"
          manualRowResize={false}
          manualColumnResize={true}
          contextMenu={false}
          fillHandle={false}
          undo={true}
          comments={{ displayDelay: 300 }}
        />
      </div>

      {/* Component sub-tables (Openings, Accessories, Crane, Mezzanine, Partition, Canopy) */}
      {COMPONENT_CONFIGS.map((config) =>
        activeComponents[config.key] ? (
          <div
            key={config.key}
            ref={(el) => {
              componentRefs.current[config.key] = el;
            }}
            className="mt-4 px-1"
          >
            <h3 className="text-xs font-bold text-gray-700 bg-indigo-100 px-2 py-1.5 uppercase tracking-wider border-l-4 border-indigo-500">
              {config.label}
            </h3>
            <ComponentTable
              columns={config.columns}
              items={
                (estimation.input_data?.[
                  config.key
                ] as Record<string, unknown>[]) ?? []
              }
              maxRows={config.maxRows}
              onChange={(items) =>
                handleComponentChange(config.key, items)
              }
            />
          </div>
        ) : null
      )}

      {/* Bottom padding */}
      <div className="h-4" />
    </div>
  );
}
