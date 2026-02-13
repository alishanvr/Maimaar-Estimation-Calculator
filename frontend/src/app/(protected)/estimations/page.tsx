"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useEstimations } from "@/hooks/useEstimations";
import {
  createEstimation,
  deleteEstimation,
  cloneEstimation,
  bulkExportEstimations,
} from "@/lib/estimations";
import { downloadBlob } from "@/lib/download";
import { INPUT_ROWS } from "@/components/estimations/InputSheetConfig";
import type { EstimationStatus } from "@/types";

/** Build default input_data from InputSheetConfig so all fields are persisted on creation. */
function buildDefaultInputData(): Record<string, unknown> {
  const defaults: Record<string, unknown> = {};
  for (const row of INPUT_ROWS) {
    if (row.type === "header" || !row.field || row.isTopLevel) continue;
    if (row.defaultValue !== undefined) {
      {
        defaults[row.field] = row.defaultValue;
      }
    }
  }
  return defaults;
}

const STATUS_FILTERS: { label: string; value: string }[] = [
  { label: "All", value: "" },
  { label: "Draft", value: "draft" },
  { label: "Calculated", value: "calculated" },
  { label: "Finalized", value: "finalized" },
];

const STATUS_BADGE: Record<EstimationStatus, string> = {
  draft: "bg-gray-100 text-gray-700",
  calculated: "bg-green-100 text-green-700",
  finalized: "bg-primary/15 text-primary",
};

export default function EstimationsPage() {
  const router = useRouter();
  const [statusFilter, setStatusFilter] = useState("");
  const [isCreating, setIsCreating] = useState(false);
  const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
  const [isExporting, setIsExporting] = useState(false);
  const { estimations, meta, isLoading, error, page, setPage, refetch } =
    useEstimations(statusFilter);

  const handleCreate = async () => {
    setIsCreating(true);
    try {
      const estimation = await createEstimation({
        building_name: "New Building",
        status: "draft" as EstimationStatus,
        input_data: buildDefaultInputData(),
      });
      router.push(`/estimations/${estimation.id}`);
    } catch {
      alert("Failed to create estimation.");
    } finally {
      setIsCreating(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Are you sure you want to delete this estimation?")) return;
    try {
      await deleteEstimation(id);
      setSelectedIds((prev) => {
        const next = new Set(prev);
        next.delete(id);
        return next;
      });
      refetch();
    } catch {
      alert("Failed to delete estimation.");
    }
  };

  const handleClone = async (id: number) => {
    try {
      const cloned = await cloneEstimation(id);
      router.push(`/estimations/${cloned.id}`);
    } catch {
      alert("Failed to clone estimation.");
    }
  };

  const handleCompare = () => {
    const ids = Array.from(selectedIds);
    router.push(`/estimations/compare?ids=${ids.join(",")}`);
  };

  const handleBulkExport = async () => {
    setIsExporting(true);
    try {
      const ids = Array.from(selectedIds);
      const blob = await bulkExportEstimations(ids, [
        "recap",
        "detail",
        "fcpbs",
        "sal",
        "boq",
        "jaf",
      ]);
      downloadBlob(blob, "estimations-export.zip");
    } catch {
      alert("Failed to export estimations.");
    } finally {
      setIsExporting(false);
    }
  };

  const toggleSelection = (id: number) => {
    setSelectedIds((prev) => {
      const next = new Set(prev);
      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }
      return next;
    });
  };

  const toggleAll = () => {
    if (selectedIds.size === estimations.length) {
      setSelectedIds(new Set());
    } else {
      setSelectedIds(new Set(estimations.map((e) => e.id)));
    }
  };

  const formatNumber = (value: number | null, decimals = 2): string => {
    if (value === null || value === undefined) return "\u2014";
    return value.toLocaleString("en-US", {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    });
  };

  return (
    <div>
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">Estimations</h2>
          <p className="text-gray-500 mt-1">
            Manage your construction estimations
          </p>
        </div>
        <button
          onClick={handleCreate}
          disabled={isCreating}
          className="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary/80 transition disabled:opacity-50"
        >
          {isCreating ? "Creating..." : "+ New Estimation"}
        </button>
      </div>

      {/* Status Filter Tabs */}
      <div className="flex gap-1 mb-6 bg-gray-100 rounded-lg p-1 w-fit">
        {STATUS_FILTERS.map((filter) => (
          <button
            key={filter.value}
            onClick={() => {
              setStatusFilter(filter.value);
              setPage(1);
            }}
            className={`px-4 py-1.5 text-sm rounded-md font-medium transition ${
              statusFilter === filter.value
                ? "bg-white text-gray-900 shadow-sm"
                : "text-gray-500 hover:text-gray-700"
            }`}
          >
            {filter.label}
          </button>
        ))}
      </div>

      {/* Selection Toolbar */}
      {selectedIds.size > 0 && (
        <div className="flex items-center gap-3 mb-4 bg-primary/10 border border-primary/25 rounded-lg px-4 py-2">
          <span className="text-sm font-medium text-primary">
            {selectedIds.size} selected
          </span>
          <div className="h-4 w-px bg-primary/25" />
          <button
            onClick={handleCompare}
            disabled={selectedIds.size !== 2}
            className="text-sm font-medium text-primary hover:text-primary/80 disabled:text-gray-400 disabled:cursor-not-allowed transition"
          >
            Compare
          </button>
          <button
            onClick={handleBulkExport}
            disabled={isExporting}
            className="text-sm font-medium text-primary hover:text-primary/80 disabled:text-gray-400 transition"
          >
            {isExporting ? "Exporting..." : "Export ZIP"}
          </button>
          <div className="flex-1" />
          <button
            onClick={() => setSelectedIds(new Set())}
            className="text-sm text-gray-500 hover:text-gray-700 transition"
          >
            Clear
          </button>
        </div>
      )}

      {/* Error */}
      {error && (
        <div className="bg-red-50 text-red-700 text-sm rounded-lg p-3 border border-red-200 mb-4">
          {error}
        </div>
      )}

      {/* Loading */}
      {isLoading && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
          <p className="text-gray-400">Loading estimations...</p>
        </div>
      )}

      {/* Empty State */}
      {!isLoading && estimations.length === 0 && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
          <div className="text-gray-300 text-5xl mb-4">&#9634;</div>
          <h3 className="text-lg font-medium text-gray-900">
            No estimations yet
          </h3>
          <p className="text-gray-500 mt-2 mb-4">
            Create your first estimation to get started.
          </p>
          <button
            onClick={handleCreate}
            disabled={isCreating}
            className="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary/80 transition disabled:opacity-50"
          >
            + New Estimation
          </button>
        </div>
      )}

      {/* Table */}
      {!isLoading && estimations.length > 0 && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-gray-50 border-b border-gray-200">
                <th className="w-10 px-3 py-3">
                  <input
                    type="checkbox"
                    checked={
                      estimations.length > 0 &&
                      selectedIds.size === estimations.length
                    }
                    onChange={toggleAll}
                    className="rounded border-gray-300 text-primary focus:ring-primary"
                  />
                </th>
                <th className="text-left px-4 py-3 font-medium text-gray-500">
                  Quote #
                </th>
                <th className="text-left px-4 py-3 font-medium text-gray-500">
                  Building
                </th>
                <th className="text-left px-4 py-3 font-medium text-gray-500">
                  Customer
                </th>
                <th className="text-left px-4 py-3 font-medium text-gray-500">
                  Status
                </th>
                <th className="text-right px-4 py-3 font-medium text-gray-500">
                  Weight (MT)
                </th>
                <th className="text-right px-4 py-3 font-medium text-gray-500">
                  Price (AED)
                </th>
                <th className="text-left px-4 py-3 font-medium text-gray-500">
                  Date
                </th>
                <th className="text-right px-4 py-3 font-medium text-gray-500">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {estimations.map((est) => (
                <tr
                  key={est.id}
                  className={`hover:bg-gray-50 cursor-pointer transition ${
                    selectedIds.has(est.id) ? "bg-primary/10" : ""
                  }`}
                  onClick={() => router.push(`/estimations/${est.id}`)}
                >
                  <td className="w-10 px-3 py-3">
                    <input
                      type="checkbox"
                      checked={selectedIds.has(est.id)}
                      onChange={(e) => {
                        e.stopPropagation();
                        toggleSelection(est.id);
                      }}
                      onClick={(e) => e.stopPropagation()}
                      className="rounded border-gray-300 text-primary focus:ring-primary"
                    />
                  </td>
                  <td className="px-4 py-3 font-mono text-xs">
                    {est.quote_number || "\u2014"}
                  </td>
                  <td className="px-4 py-3 font-medium text-gray-900">
                    {est.building_name || "Untitled"}
                  </td>
                  <td className="px-4 py-3 text-gray-600">
                    {est.customer_name || "\u2014"}
                  </td>
                  <td className="px-4 py-3">
                    <span
                      className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${STATUS_BADGE[est.status]}`}
                    >
                      {est.status}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-right font-mono text-xs">
                    {formatNumber(est.total_weight_mt, 2)}
                  </td>
                  <td className="px-4 py-3 text-right font-mono text-xs">
                    {formatNumber(est.total_price_aed, 0)}
                  </td>
                  <td className="px-4 py-3 text-gray-500 text-xs">
                    {est.estimation_date || est.created_at?.slice(0, 10)}
                  </td>
                  <td className="px-4 py-3 text-right">
                    <div className="flex items-center justify-end gap-2">
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          handleClone(est.id);
                        }}
                        className="text-primary hover:text-primary/80 text-xs font-medium transition"
                      >
                        Clone
                      </button>
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          handleDelete(est.id);
                        }}
                        className="text-red-500 hover:text-red-700 text-xs font-medium transition"
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {/* Pagination */}
          {meta && meta.last_page > 1 && (
            <div className="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
              <p className="text-sm text-gray-500">
                Showing {meta.from}&ndash;{meta.to} of {meta.total}
              </p>
              <div className="flex gap-2">
                <button
                  onClick={() => setPage(page - 1)}
                  disabled={page <= 1}
                  className="px-3 py-1 text-sm rounded border border-gray-300 disabled:opacity-50 hover:bg-white transition"
                >
                  Previous
                </button>
                <button
                  onClick={() => setPage(page + 1)}
                  disabled={page >= meta.last_page}
                  className="px-3 py-1 text-sm rounded border border-gray-300 disabled:opacity-50 hover:bg-white transition"
                >
                  Next
                </button>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
