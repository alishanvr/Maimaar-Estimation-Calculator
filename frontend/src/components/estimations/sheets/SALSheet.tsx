"use client";

import { useMemo, useCallback, useState } from "react";
import { HotTable } from "@handsontable/react-wrapper";
import { registerAllModules } from "handsontable/registry";
import "handsontable/styles/handsontable.min.css";
import "handsontable/styles/ht-theme-main.min.css";

import { useSheetData } from "@/hooks/useSheetData";
import { formatNumber } from "@/lib/formatters";
import { exportSalPdf } from "@/lib/estimations";
import { downloadBlob } from "@/lib/download";
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

  const [downloading, setDownloading] = useState(false);
  const [downloadError, setDownloadError] = useState<string | null>(null);

  const handleDownloadPdf = useCallback(async () => {
    setDownloading(true);
    setDownloadError(null);
    try {
      const blob = await exportSalPdf(estimationId);
      downloadBlob(blob, `SAL-${estimationId}.pdf`);
    } catch (err: unknown) {
      const message =
        err instanceof Error ? err.message : "Failed to download PDF";
      setDownloadError(message);
    } finally {
      setDownloading(false);
    }
  }, [estimationId]);

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
          className: "htRight htMiddle text-xs font-bold bg-primary-subtle",
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
        <div className="flex flex-col" style={{ height }}>
          {/* Download button bar */}
          <div className="flex items-center gap-2 px-3 py-2 border-b border-gray-200 bg-gray-50 no-print shrink-0">
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

          {/* Grid */}
          <div className="flex-1 overflow-hidden">
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
