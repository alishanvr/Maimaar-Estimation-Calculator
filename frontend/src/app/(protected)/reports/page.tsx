"use client";

import { useState } from "react";
import { useReportDashboard } from "@/hooks/useReportDashboard";
import { exportReportCsv, exportReportPdf } from "@/lib/reports";
import { downloadBlob } from "@/lib/download";
import type { ReportFilters } from "@/types/reports";
import KPICards from "@/components/reports/KPICards";
import FiltersToolbar from "@/components/reports/FiltersToolbar";
import ReportCharts from "@/components/reports/ReportCharts";

export default function ReportsPage() {
  const [filters, setFilters] = useState<ReportFilters>({});
  const [isExporting, setIsExporting] = useState(false);
  const { data, isLoading, error } = useReportDashboard(filters);

  const handleExportCsv = async () => {
    setIsExporting(true);
    try {
      const blob = await exportReportCsv(filters);
      downloadBlob(blob, `estimations-report-${new Date().toISOString().slice(0, 10)}.csv`);
    } catch {
      alert("Failed to export CSV.");
    } finally {
      setIsExporting(false);
    }
  };

  const handleExportPdf = async () => {
    setIsExporting(true);
    try {
      const blob = await exportReportPdf(filters);
      downloadBlob(blob, `report-dashboard-${new Date().toISOString().slice(0, 10)}.pdf`);
    } catch {
      alert("Failed to export PDF.");
    } finally {
      setIsExporting(false);
    }
  };

  return (
    <div>
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">Reports</h2>
          <p className="text-gray-500 mt-1">
            Analytics and insights across estimations
          </p>
        </div>
        <div className="flex items-center gap-2">
          <button
            onClick={handleExportCsv}
            disabled={isExporting || isLoading}
            className="px-3 py-1.5 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition disabled:opacity-50"
          >
            {isExporting ? "Exporting..." : "Export CSV"}
          </button>
          <button
            onClick={handleExportPdf}
            disabled={isExporting || isLoading}
            className="px-3 py-1.5 text-sm bg-primary text-white rounded-lg font-medium hover:bg-primary/80 transition disabled:opacity-50"
          >
            {isExporting ? "Exporting..." : "Export PDF"}
          </button>
        </div>
      </div>

      {/* Filters */}
      <FiltersToolbar
        filters={filters}
        onChange={setFilters}
        customers={data?.filters_meta.customers ?? []}
        salespersons={data?.filters_meta.salespersons ?? []}
      />

      {/* Error */}
      {error && (
        <div className="bg-red-50 text-red-700 text-sm rounded-lg p-3 border border-red-200 mb-4">
          {error}
        </div>
      )}

      {/* Loading */}
      {isLoading && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
          <p className="text-gray-400">Loading report data...</p>
        </div>
      )}

      {/* Content */}
      {!isLoading && data && (
        <>
          <KPICards kpis={data.kpis} />
          <ReportCharts data={data} />
        </>
      )}
    </div>
  );
}
