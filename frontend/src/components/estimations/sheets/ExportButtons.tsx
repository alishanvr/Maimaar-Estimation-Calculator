"use client";

import { useState, useCallback } from "react";
import { exportSheetCsv, type CsvSheetType } from "@/lib/estimations";
import { downloadBlob } from "@/lib/download";

const DownloadIcon = () => (
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
);

interface ExportButtonsProps {
  estimationId: number;
  sheetType: CsvSheetType;
  onDownloadPdf: () => Promise<void>;
  downloadingPdf: boolean;
  children?: React.ReactNode;
}

export default function ExportButtons({
  estimationId,
  sheetType,
  onDownloadPdf,
  downloadingPdf,
  children,
}: ExportButtonsProps) {
  const [downloadingCsv, setDownloadingCsv] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleDownloadCsv = useCallback(async () => {
    setDownloadingCsv(true);
    setError(null);
    try {
      const blob = await exportSheetCsv(estimationId, sheetType);
      downloadBlob(
        blob,
        `${sheetType.toUpperCase()}-${estimationId}.csv`
      );
    } catch (err: unknown) {
      const message =
        err instanceof Error ? err.message : "Failed to download CSV";
      setError(message);
    } finally {
      setDownloadingCsv(false);
    }
  }, [estimationId, sheetType]);

  return (
    <>
      <button
        onClick={onDownloadPdf}
        disabled={downloadingPdf}
        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-primary rounded-md hover:bg-primary/80 transition disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <DownloadIcon />
        {downloadingPdf ? "Downloading..." : "Download PDF"}
      </button>
      <button
        onClick={handleDownloadCsv}
        disabled={downloadingCsv}
        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <DownloadIcon />
        {downloadingCsv ? "Downloading..." : "Download CSV"}
      </button>
      {children}
      {error && <span className="text-xs text-red-500">{error}</span>}
    </>
  );
}
