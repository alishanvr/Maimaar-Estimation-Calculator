import { useMemo, useCallback, useState } from "react";
import { HotTable } from "@handsontable/react-wrapper";
import { registerAllModules } from "handsontable/registry";

import { useSheetData } from "../../../hooks/useSheetData";
import { useCurrency } from "../../../hooks/useCurrency";
import { formatNumber } from "../../../lib/formatters";
import { exportSalPdf } from "../../../lib/estimations";
import { downloadBlob } from "../../../lib/download";
import type { SALData } from "../../../types";
import ReadOnlySheet from "./ReadOnlySheet";
import ExportButtons from "./ExportButtons";

registerAllModules();

interface SALSheetProps {
  estimationId: number;
  version?: string;
}

export default function SALSheet({ estimationId, version }: SALSheetProps) {
  const { symbol, format, formatPerMT } = useCurrency();
  const { data, isLoading, error } = useSheetData<SALData>(
    estimationId,
    "sal",
    version
  );

  const [downloading, setDownloading] = useState(false);

  const handleDownloadPdf = useCallback(async () => {
    setDownloading(true);
    try {
      const blob = await exportSalPdf(estimationId);
      downloadBlob(blob, `SAL-${estimationId}.pdf`);
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
        format(line.cost, 2),
        formatNumber(line.markup, 3),
        format(line.price, 2),
        formatPerMT(line.price_per_mt, 2),
      ]);
    }

    // Total row
    const idx = rows.length;
    rows.push([
      "",
      "TOTAL",
      formatNumber(data.total_weight_kg, 3),
      format(data.total_cost, 2),
      formatNumber(data.markup_ratio, 3),
      format(data.total_price, 2),
      formatPerMT(data.price_per_mt, 2),
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
            <ExportButtons
              estimationId={estimationId}
              sheetType="sal"
              onDownloadPdf={handleDownloadPdf}
              downloadingPdf={downloading}
            />
          </div>

          {/* Grid */}
          <div className="flex-1 overflow-hidden">
            <HotTable
              data={tableData}
              colHeaders={[
                "Code",
                "Description",
                "Weight (kg)",
                `Cost (${symbol})`,
                "Markup",
                `Price (${symbol})`,
                `${symbol}/MT`,
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
