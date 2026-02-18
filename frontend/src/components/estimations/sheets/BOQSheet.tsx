"use client";

import { useMemo, useCallback, useState } from "react";
import { HotTable } from "@handsontable/react-wrapper";
import { registerAllModules } from "handsontable/registry";
import "handsontable/styles/handsontable.min.css";
import "handsontable/styles/ht-theme-main.min.css";

import { useSheetData } from "@/hooks/useSheetData";
import { useCurrency } from "@/hooks/useCurrency";
import { formatNumber } from "@/lib/formatters";
import { exportBoqPdf } from "@/lib/estimations";
import { downloadBlob } from "@/lib/download";
import type { BOQData } from "@/types";
import ReadOnlySheet from "./ReadOnlySheet";
import ExportButtons from "./ExportButtons";

registerAllModules();

interface BOQSheetProps {
  estimationId: number;
  version?: string;
}

export default function BOQSheet({ estimationId, version }: BOQSheetProps) {
  const { symbol, rate, format } = useCurrency();
  const { data, isLoading, error } = useSheetData<BOQData>(
    estimationId,
    "boq",
    version
  );

  const [downloading, setDownloading] = useState(false);

  const handleDownloadPdf = useCallback(async () => {
    setDownloading(true);
    try {
      const blob = await exportBoqPdf(estimationId);
      downloadBlob(blob, `BOQ-${estimationId}.pdf`);
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
      format(item.unit_rate, 2),
      format(item.total_price, 2),
    ]);

    // Total row
    const idx = rows.length;
    rows.push([
      "",
      "TOTAL",
      "MT",
      formatNumber(data.total_weight_mt, 4),
      "",
      format(data.total_price, 2),
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
            <ExportButtons
              estimationId={estimationId}
              sheetType="boq"
              onDownloadPdf={handleDownloadPdf}
              downloadingPdf={downloading}
            />
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
                `Unit Rate (${symbol})`,
                `Total Price (${symbol})`,
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
