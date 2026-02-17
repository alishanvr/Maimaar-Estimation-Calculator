"use client";

import { useMemo, useCallback, useState } from "react";
import { HotTable } from "@handsontable/react-wrapper";
import { registerAllModules } from "handsontable/registry";
import "handsontable/styles/handsontable.min.css";
import "handsontable/styles/ht-theme-main.min.css";

import { useSheetData } from "@/hooks/useSheetData";
import { formatNumber } from "@/lib/formatters";
import { exportRawmatPdf } from "@/lib/estimations";
import { downloadBlob } from "@/lib/download";
import type { RawmatData } from "@/types";
import ReadOnlySheet from "./ReadOnlySheet";

registerAllModules();

interface RawmatSheetProps {
  estimationId: number;
  version?: string;
}

export default function RawmatSheet({
  estimationId,
  version,
}: RawmatSheetProps) {
  const { data, isLoading, error } = useSheetData<RawmatData>(
    estimationId,
    "rawmat",
    version
  );

  const [downloading, setDownloading] = useState(false);
  const [downloadError, setDownloadError] = useState<string | null>(null);

  const handleDownloadPdf = useCallback(async () => {
    setDownloading(true);
    setDownloadError(null);
    try {
      const blob = await exportRawmatPdf(estimationId);
      downloadBlob(blob, `RAWMAT-${estimationId}.pdf`);
    } catch (err: unknown) {
      const message =
        err instanceof Error ? err.message : "Failed to download PDF";
      setDownloadError(message);
    } finally {
      setDownloading(false);
    }
  }, [estimationId]);

  const { tableData, categoryRowIndices, totalRowIndex } = useMemo(() => {
    if (!data) return { tableData: [], categoryRowIndices: new Set<number>(), totalRowIndex: -1 };

    const rows: (string | number)[][] = [];
    const catRows = new Set<number>();
    let currentCategory: string | null = null;

    for (const item of data.items) {
      // Insert category header row when category changes
      if (item.category !== currentCategory) {
        currentCategory = item.category;
        const catStats = data.categories[currentCategory];
        const label = `${currentCategory} (${catStats?.count ?? 0} items — ${formatNumber(catStats?.weight_kg ?? 0, 2)} kg)`;
        catRows.add(rows.length);
        rows.push(["", "", "", label, "", "", "", "", ""]);
      }

      rows.push([
        item.no,
        item.code,
        item.cost_code,
        item.description,
        item.unit,
        formatNumber(item.quantity, 2),
        formatNumber(item.unit_weight, 4),
        formatNumber(item.total_weight, 2),
        item.category,
      ]);
    }

    // Total row
    const idx = rows.length;
    rows.push([
      "",
      "",
      "",
      "TOTAL",
      "",
      `${data.summary.unique_materials} items`,
      "",
      formatNumber(data.summary.total_weight_kg, 2),
      "",
    ]);

    return { tableData: rows, categoryRowIndices: catRows, totalRowIndex: idx };
  }, [data]);

  const cellsCallback = useCallback(
    (row: number, col: number) => {
      if (row === totalRowIndex) {
        return {
          readOnly: true,
          className: "htRight htMiddle text-xs font-bold bg-primary-subtle",
        };
      }
      if (categoryRowIndices.has(row)) {
        return {
          readOnly: true,
          className: "htLeft htMiddle text-xs font-bold bg-gray-100",
        };
      }
      return {
        readOnly: true,
        className:
          col >= 5
            ? "htRight htMiddle text-xs"
            : "htMiddle text-xs",
      };
    },
    [totalRowIndex, categoryRowIndices]
  );

  return (
    <ReadOnlySheet isLoading={isLoading} error={error} sheetLabel="RAWMAT">
      {(height) => (
        <div className="flex flex-col" style={{ height }}>
          {/* Summary stats + download button */}
          <div className="flex items-center justify-between gap-2 px-3 py-2 border-b border-gray-200 bg-gray-50 no-print shrink-0">
            <div className="flex items-center gap-4 text-xs text-gray-600">
              {data && (
                <>
                  <span>
                    <strong>Detail Items:</strong>{" "}
                    {data.summary.total_items_before}
                  </span>
                  <span>
                    <strong>Unique Materials:</strong>{" "}
                    {data.summary.unique_materials}
                  </span>
                  <span>
                    <strong>Total Weight:</strong>{" "}
                    {formatNumber(data.summary.total_weight_kg / 1000, 4)} MT
                  </span>
                  <span>
                    <strong>Categories:</strong>{" "}
                    {data.summary.category_count}
                  </span>
                </>
              )}
            </div>
            <div className="flex items-center gap-2">
              <button
                onClick={handleDownloadPdf}
                disabled={downloading}
                className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-primary rounded-md hover:bg-primary/80 transition disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg
                  className="w-3.5 h-3.5"
                  fill="none"
                  viewBox="0 0 24 24"
                  strokeWidth={2}
                  stroke="currentColor"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"
                  />
                </svg>
                {downloading ? "Downloading..." : "Download PDF"}
              </button>
              {downloadError && (
                <span className="text-xs text-red-500">{downloadError}</span>
              )}
            </div>
          </div>

          {/* Grid */}
          <div className="flex-1 overflow-hidden">
            <HotTable
              data={tableData}
              colHeaders={[
                "No",
                "DB Code",
                "Cost Code",
                "Description",
                "Unit",
                "QTY",
                "Unit Wt (kg)",
                "Total Wt (kg)",
                "Category",
              ]}
              colWidths={[40, 80, 80, 240, 50, 80, 90, 100, 120]}
              columns={Array.from({ length: 9 }, (_, i) => ({
                data: i,
                readOnly: true,
              }))}
              cells={cellsCallback}
              mergeCells={[...categoryRowIndices].map((rowIdx) => ({
                row: rowIdx,
                col: 0,
                rowspan: 1,
                colspan: 9,
              }))}
              stretchH="all"
              height={height - 50}
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
          </div>
        </div>
      )}
    </ReadOnlySheet>
  );
}
