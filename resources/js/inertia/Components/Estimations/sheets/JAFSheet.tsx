import { useCallback, useState } from "react";
import { useSheetData } from "../../../hooks/useSheetData";
import { useCurrency } from "../../../hooks/useCurrency";
import { formatNumber } from "../../../lib/formatters";
import { exportJafPdf } from "../../../lib/estimations";
import { downloadBlob } from "../../../lib/download";
import type { JAFData } from "../../../types";
import ReadOnlySheet from "./ReadOnlySheet";
import ExportButtons from "./ExportButtons";

interface JAFSheetProps {
  estimationId: number;
  version?: string;
}

function InfoRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between py-2 border-b border-gray-100 last:border-0">
      <span className="text-sm text-gray-500">{label}</span>
      <span className="text-sm font-mono font-medium text-gray-900">
        {value || "\u2014"}
      </span>
    </div>
  );
}

export default function JAFSheet({ estimationId, version }: JAFSheetProps) {
  const { format, formatPerMT } = useCurrency();
  const { data, isLoading, error } = useSheetData<JAFData>(
    estimationId,
    "jaf",
    version
  );

  const [downloading, setDownloading] = useState(false);

  const handleDownloadPdf = useCallback(async () => {
    setDownloading(true);
    try {
      const blob = await exportJafPdf(estimationId);
      downloadBlob(blob, `JAF-${estimationId}.pdf`);
    } finally {
      setDownloading(false);
    }
  }, [estimationId]);

  return (
    <ReadOnlySheet
      isLoading={isLoading}
      error={error}
      sheetLabel="Job Acceptance Form"
    >
      {data && (
        <div className="max-w-4xl mx-auto p-6 space-y-6">
          {/* Download button bar */}
          <div className="flex items-center gap-2 no-print">
            <ExportButtons
              estimationId={estimationId}
              sheetType="jaf"
              onDownloadPdf={handleDownloadPdf}
              downloadingPdf={downloading}
            />
          </div>

          {/* Header */}
          <div className="bg-primary text-white rounded-lg px-6 py-4">
            <h3 className="text-lg font-bold">Job Acceptance Form</h3>
            <p className="text-white/70 text-sm mt-1">
              Quote: {data.project_info.quote_number || "\u2014"} &middot;
              Rev {data.project_info.revision_number}
            </p>
          </div>

          {/* Project Info */}
          <div className="bg-white rounded-xl border border-gray-200 p-5">
            <h4 className="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">
              Project Information
            </h4>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-x-8">
              <InfoRow
                label="Quote Number"
                value={data.project_info.quote_number}
              />
              <InfoRow
                label="Building Name"
                value={data.project_info.building_name}
              />
              <InfoRow
                label="Building Number"
                value={String(data.project_info.building_number)}
              />
              <InfoRow
                label="Project Name"
                value={data.project_info.project_name}
              />
              <InfoRow
                label="Customer"
                value={data.project_info.customer_name}
              />
              <InfoRow
                label="Salesperson"
                value={data.project_info.salesperson_code}
              />
              <InfoRow
                label="Revision"
                value={String(data.project_info.revision_number)}
              />
              <InfoRow label="Date" value={data.project_info.date} />
              <InfoRow
                label="Sales Office"
                value={data.project_info.sales_office}
              />
            </div>
          </div>

          {/* Pricing */}
          <div className="bg-white rounded-xl border border-gray-200 p-5">
            <h4 className="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">
              Pricing
            </h4>
            <InfoRow
              label="Bottom Line Markup"
              value={formatNumber(data.pricing.bottom_line_markup, 4)}
            />
            <InfoRow
              label="Value Added (L)"
              value={formatPerMT(data.pricing.value_added_l, 2)}
            />
            <InfoRow
              label="Value Added (R)"
              value={formatPerMT(data.pricing.value_added_r, 2)}
            />
            <InfoRow
              label="Total Weight"
              value={formatNumber(data.pricing.total_weight_mt, 4) + " MT"}
            />
            <InfoRow
              label="Primary Weight"
              value={
                formatNumber(data.pricing.primary_weight_mt, 4) + " MT"
              }
            />
            <InfoRow
              label="Supply Price"
              value={format(data.pricing.supply_price_aed, 2)}
            />
            <InfoRow
              label="Erection Price"
              value={format(data.pricing.erection_price_aed, 2)}
            />
            <InfoRow
              label="Total Contract"
              value={format(data.pricing.total_contract_aed, 2)}
            />
            <InfoRow
              label="Contract Value (USD)"
              value={formatNumber(data.pricing.contract_value_usd, 0) + " USD"}
            />
            <InfoRow
              label="Price per MT"
              value={formatPerMT(data.pricing.price_per_mt, 2)}
            />
            <InfoRow
              label="Min Delivery"
              value={data.pricing.min_delivery_weeks + " weeks"}
            />
          </div>

          {/* Building Info */}
          <div className="bg-white rounded-xl border border-gray-200 p-5">
            <h4 className="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">
              Building Information
            </h4>
            <InfoRow
              label="Non-Identical Buildings"
              value={String(
                data.building_info.num_non_identical_buildings
              )}
            />
            <InfoRow
              label="All Buildings"
              value={String(data.building_info.num_all_buildings)}
            />
            <InfoRow label="Scope" value={data.building_info.scope} />
          </div>

          {/* Special Requirements */}
          {data.special_requirements &&
            Object.keys(data.special_requirements).length > 0 && (
              <div className="bg-white rounded-xl border border-gray-200 p-5">
                <h4 className="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">
                  Special Requirements
                </h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1">
                  {Object.entries(data.special_requirements).map(
                    ([num, desc]) => (
                      <div
                        key={num}
                        className="flex items-start gap-2 py-1 text-sm"
                      >
                        <span className="text-gray-400 font-mono w-6 shrink-0 text-right">
                          {num}.
                        </span>
                        <span className="text-gray-700">{desc}</span>
                      </div>
                    )
                  )}
                </div>
              </div>
            )}

          {/* Revision History */}
          {data.revision_history && data.revision_history.length > 0 && (
            <div className="bg-white rounded-xl border border-gray-200 p-5">
              <h4 className="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">
                Revision History
              </h4>
              <p className="text-sm text-gray-400">
                {data.revision_history.length} revision(s) recorded.
              </p>
            </div>
          )}
        </div>
      )}
    </ReadOnlySheet>
  );
}
