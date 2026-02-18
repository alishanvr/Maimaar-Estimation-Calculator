"use client";

import { useState, useCallback, useRef } from "react";
import { importEstimationCsv, type ImportResult } from "@/lib/estimations";
import { getErrorMessage } from "@/lib/api";

const TEMPLATE_CSV =
  "description,code,sales_code,cost_code,size,qty,unit,weight_per_unit,rate\nSample Beam,BU,1,MC01,200,4,EA,125.5,3500\nSample Plate,PL,2,MC02,10,10,EA,78.2,2200\n";

interface CsvImportModalProps {
  estimationId: number;
  isOpen: boolean;
  onClose: () => void;
  onImported: () => void;
}

export default function CsvImportModal({
  estimationId,
  isOpen,
  onClose,
  onImported,
}: CsvImportModalProps) {
  const fileRef = useRef<HTMLInputElement>(null);
  const [file, setFile] = useState<File | null>(null);
  const [mergeStrategy, setMergeStrategy] = useState<"append" | "replace">(
    "append"
  );
  const [preview, setPreview] = useState<ImportResult | null>(null);
  const [loading, setLoading] = useState(false);
  const [committing, setCommitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const reset = useCallback(() => {
    setFile(null);
    setPreview(null);
    setError(null);
    setLoading(false);
    setCommitting(false);
    setMergeStrategy("append");
    if (fileRef.current) fileRef.current.value = "";
  }, []);

  const handleClose = useCallback(() => {
    reset();
    onClose();
  }, [reset, onClose]);

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const selected = e.target.files?.[0] || null;
    setFile(selected);
    setPreview(null);
    setError(null);
  };

  const handleDownloadTemplate = useCallback(() => {
    const blob = new Blob([TEMPLATE_CSV], { type: "text/csv" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = "import-template.csv";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  }, []);

  const handlePreview = useCallback(async () => {
    if (!file) return;
    setLoading(true);
    setError(null);
    try {
      const result = await importEstimationCsv(estimationId, file, {
        commit: false,
      });
      setPreview(result);
      if (result.data.errors.length > 0) {
        setError(
          `${result.data.errors.length} warning(s) found. Review below.`
        );
      }
    } catch (err: unknown) {
      setError(getErrorMessage(err, "Failed to preview CSV"));
    } finally {
      setLoading(false);
    }
  }, [estimationId, file]);

  const handleCommit = useCallback(async () => {
    if (!file) return;
    setCommitting(true);
    setError(null);
    try {
      await importEstimationCsv(estimationId, file, {
        commit: true,
        merge_strategy: mergeStrategy,
      });
      onImported();
      handleClose();
    } catch (err: unknown) {
      setError(getErrorMessage(err, "Failed to import CSV"));
    } finally {
      setCommitting(false);
    }
  }, [estimationId, file, mergeStrategy, onImported, handleClose]);

  if (!isOpen) return null;

  const strategyLabel = mergeStrategy === "replace" ? "Replace" : "Append";

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
      <div className="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 max-h-[80vh] flex flex-col">
        <h3 className="text-lg font-semibold text-gray-900 mb-2">
          Import Detail Items from CSV
        </h3>
        <p className="text-sm text-gray-500 mb-1">
          Upload a CSV file with columns: description, code, sales_code,
          cost_code, size, qty, unit, weight_per_unit, rate.
        </p>
        <button
          onClick={handleDownloadTemplate}
          className="text-xs text-primary hover:underline mb-4 self-start"
        >
          Download sample template CSV
        </button>

        {/* File input */}
        <div className="mb-4">
          <label className="block text-xs font-medium text-gray-700 mb-1">
            CSV File <span className="text-red-500">*</span>
          </label>
          <input
            ref={fileRef}
            type="file"
            accept=".csv,.txt"
            onChange={handleFileChange}
            className="w-full text-sm text-gray-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border file:border-gray-300 file:text-xs file:font-medium file:bg-white file:text-gray-700 hover:file:bg-gray-50"
          />
        </div>

        {/* Merge strategy */}
        <div className="mb-4">
          <label className="block text-xs font-medium text-gray-700 mb-1">
            Merge Strategy
          </label>
          <div className="flex gap-4">
            <label className="flex items-center gap-1.5 text-sm text-gray-700">
              <input
                type="radio"
                name="merge_strategy"
                value="append"
                checked={mergeStrategy === "append"}
                onChange={() => setMergeStrategy("append")}
                className="text-primary focus:ring-primary"
              />
              Append to existing
            </label>
            <label className="flex items-center gap-1.5 text-sm text-gray-700">
              <input
                type="radio"
                name="merge_strategy"
                value="replace"
                checked={mergeStrategy === "replace"}
                onChange={() => setMergeStrategy("replace")}
                className="text-primary focus:ring-primary"
              />
              Replace existing
            </label>
          </div>
        </div>

        {/* Preview results */}
        {preview && (
          <div className="mb-4 flex-1 overflow-auto">
            <div className="text-xs text-gray-600 mb-2">
              <strong>{preview.data.row_count}</strong> row(s) parsed,{" "}
              <strong>{preview.data.items.length}</strong> valid item(s)
            </div>
            {preview.data.errors.length > 0 && (
              <div className="mb-2 p-2 bg-amber-50 rounded-md border border-amber-200">
                <p className="text-xs font-medium text-amber-800 mb-1">
                  Warnings:
                </p>
                <ul className="text-xs text-amber-700 list-disc list-inside max-h-20 overflow-auto">
                  {preview.data.errors.map((err, i) => (
                    <li key={i}>{err}</li>
                  ))}
                </ul>
              </div>
            )}
            {preview.data.items.length > 0 && (
              <div className="border border-gray-200 rounded-md overflow-auto max-h-40">
                <table className="min-w-full text-xs">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-2 py-1 text-left text-gray-600">
                        Description
                      </th>
                      <th className="px-2 py-1 text-left text-gray-600">
                        Code
                      </th>
                      <th className="px-2 py-1 text-right text-gray-600">
                        Qty
                      </th>
                      <th className="px-2 py-1 text-right text-gray-600">
                        Weight
                      </th>
                      <th className="px-2 py-1 text-right text-gray-600">
                        Rate
                      </th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100">
                    {preview.data.items.slice(0, 20).map((item, i) => (
                      <tr key={i}>
                        <td className="px-2 py-1 text-gray-900">
                          {String(item.description || "")}
                        </td>
                        <td className="px-2 py-1 text-gray-600">
                          {String(item.code || "")}
                        </td>
                        <td className="px-2 py-1 text-right text-gray-900">
                          {String(item.qty || "")}
                        </td>
                        <td className="px-2 py-1 text-right text-gray-900">
                          {String(item.weight_per_unit || "")}
                        </td>
                        <td className="px-2 py-1 text-right text-gray-900">
                          {String(item.rate || "")}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                {preview.data.items.length > 20 && (
                  <p className="text-xs text-gray-400 px-2 py-1">
                    ...and {preview.data.items.length - 20} more
                  </p>
                )}
              </div>
            )}
          </div>
        )}

        {/* Error */}
        {error && <p className="mb-3 text-xs text-red-500">{error}</p>}

        {/* Actions */}
        <div className="flex justify-end gap-2 mt-2">
          <button
            onClick={handleClose}
            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition"
          >
            Cancel
          </button>
          {!preview ? (
            <button
              onClick={handlePreview}
              disabled={!file || loading}
              className="px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary/80 transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {loading ? "Parsing..." : "Preview"}
            </button>
          ) : (
            <button
              onClick={handleCommit}
              disabled={
                committing ||
                preview.data.items.length === 0
              }
              className="px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary/80 transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {committing
                ? "Importing..."
                : `${strategyLabel} ${preview.data.items.length} Item(s)`}
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
