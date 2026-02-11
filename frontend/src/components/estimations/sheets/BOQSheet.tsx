"use client";

import { useMemo, useCallback, useState } from "react";
import { HotTable } from "@handsontable/react-wrapper";
import { registerAllModules } from "handsontable/registry";
import "handsontable/styles/handsontable.min.css";
import "handsontable/styles/ht-theme-main.min.css";

import { useSheetData } from "@/hooks/useSheetData";
import { formatNumber } from "@/lib/formatters";
import { exportBoqPdf } from "@/lib/estimations";
import { downloadBlob } from "@/lib/download";
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

  const [downloading, setDownloading] = useState(false);
  const [downloadError, setDownloadError] = useState<string | null>(null);

  const handleDownloadPdf = useCallback(async () => {
    setDownloading(true);
    setDownloadError(null);
    try {
      const blob = await exportBoqPdf(estimationId);
      downloadBlob(blob, `BOQ-${estimationId}.pdf`);
    } catch (err: unknown) {
      const message =
        err instanceof Error ? err.message : "Failed to download PDF";
      setDownloadError(message);
    } finally {
      setDownloading(false);
    }
  }, [estimationId]);

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
        <div className="flex flex-col" style={{ height }}>
          {/* Download button bar */}
          <div className="flex items-center gap-2 px-3 py-2 border-b border-gray-200 bg-gray-50 no-print shrink-0">
            <button
              onClick={handleDownloadPdf}
              disabled={downloading}
              className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
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
