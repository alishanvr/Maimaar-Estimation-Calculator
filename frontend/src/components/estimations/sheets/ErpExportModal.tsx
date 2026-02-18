"use client";

import { useState, useCallback } from "react";
import { exportErpCsv } from "@/lib/estimations";
import { downloadBlob } from "@/lib/download";
import { getErrorMessage } from "@/lib/api";

interface ErpExportModalProps {
  estimationId: number;
  isOpen: boolean;
  onClose: () => void;
}

export default function ErpExportModal({
  estimationId,
  isOpen,
  onClose,
}: ErpExportModalProps) {
  const [jobNumber, setJobNumber] = useState("");
  const [buildingNumber, setBuildingNumber] = useState("");
  const [contractDate, setContractDate] = useState("");
  const [fiscalYear, setFiscalYear] = useState(new Date().getFullYear());
  const [downloading, setDownloading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const reset = useCallback(() => {
    setJobNumber("");
    setBuildingNumber("");
    setContractDate("");
    setFiscalYear(new Date().getFullYear());
    setError(null);
    setDownloading(false);
  }, []);

  const handleClose = useCallback(() => {
    reset();
    onClose();
  }, [reset, onClose]);

  const handleExport = useCallback(async () => {
    if (!jobNumber || !buildingNumber || !contractDate) {
      setError("All fields are required.");
      return;
    }

    setDownloading(true);
    setError(null);
    try {
      const blob = await exportErpCsv(estimationId, {
        job_number: jobNumber,
        building_number: buildingNumber,
        contract_date: contractDate,
        fiscal_year: fiscalYear,
      });
      downloadBlob(blob, `ERP-${estimationId}.csv`);
      handleClose();
    } catch (err: unknown) {
      setError(getErrorMessage(err, "Failed to export ERP CSV"));
    } finally {
      setDownloading(false);
    }
  }, [estimationId, jobNumber, buildingNumber, contractDate, fiscalYear, handleClose]);

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
      <div className="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">
          Export ERP CSV
        </h3>
        <p className="text-sm text-gray-500 mb-4">
          Enter the project details required for ERP system integration.
        </p>

        <div className="space-y-3">
          <div>
            <label className="block text-xs font-medium text-gray-700 mb-1">
              Job Number <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              value={jobNumber}
              onChange={(e) => setJobNumber(e.target.value)}
              maxLength={9}
              placeholder="e.g. TEST01"
              className="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
            />
          </div>

          <div>
            <label className="block text-xs font-medium text-gray-700 mb-1">
              Building Number <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              value={buildingNumber}
              onChange={(e) => setBuildingNumber(e.target.value)}
              maxLength={10}
              placeholder="e.g. 01"
              className="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
            />
          </div>

          <div>
            <label className="block text-xs font-medium text-gray-700 mb-1">
              Contract Date <span className="text-red-500">*</span>
            </label>
            <input
              type="date"
              value={contractDate}
              onChange={(e) => setContractDate(e.target.value)}
              className="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
            />
          </div>

          <div>
            <label className="block text-xs font-medium text-gray-700 mb-1">
              Fiscal Year <span className="text-red-500">*</span>
            </label>
            <input
              type="number"
              value={fiscalYear}
              onChange={(e) => setFiscalYear(parseInt(e.target.value) || 2026)}
              min={2020}
              max={2099}
              className="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
            />
          </div>
        </div>

        {error && (
          <p className="mt-3 text-xs text-red-500">{error}</p>
        )}

        <div className="flex justify-end gap-2 mt-6">
          <button
            onClick={handleClose}
            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition"
          >
            Cancel
          </button>
          <button
            onClick={handleExport}
            disabled={downloading}
            className="px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary/80 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {downloading ? "Exporting..." : "Export ERP CSV"}
          </button>
        </div>
      </div>
    </div>
  );
}
