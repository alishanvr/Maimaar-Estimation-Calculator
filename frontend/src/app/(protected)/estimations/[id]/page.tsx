"use client";

import { useState } from "react";
import { useParams } from "next/navigation";
import { useEstimation } from "@/hooks/useEstimation";
import EstimationHeader from "@/components/estimations/EstimationHeader";
import PrintHeader from "@/components/estimations/PrintHeader";
import TabBar from "@/components/estimations/TabBar";
import InputSheet from "@/components/estimations/InputSheet";
import RecapSheet from "@/components/estimations/sheets/RecapSheet";
import DetailSheet from "@/components/estimations/sheets/DetailSheet";
import FCPBSSheet from "@/components/estimations/sheets/FCPBSSheet";
import SALSheet from "@/components/estimations/sheets/SALSheet";
import BOQSheet from "@/components/estimations/sheets/BOQSheet";
import JAFSheet from "@/components/estimations/sheets/JAFSheet";
import type { SheetTab, InputData } from "@/types";

export default function EstimationEditorPage() {
  const params = useParams();
  const id = Number(params.id);
  const [activeTab, setActiveTab] = useState<SheetTab>("input");
  const {
    estimation,
    isLoading,
    isSaving,
    isCalculating,
    error,
    save,
    updateInputData,
    updateFields,
    calculate,
    saveAndCalculate,
  } = useEstimation(id);

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <p className="text-gray-400">Loading estimation...</p>
      </div>
    );
  }

  if (!estimation) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <h2 className="text-xl font-bold text-gray-900 mb-2">
            Estimation Not Found
          </h2>
          <p className="text-gray-500">
            The estimation you&apos;re looking for doesn&apos;t exist or you
            don&apos;t have access.
          </p>
        </div>
      </div>
    );
  }

  const isCalculated =
    estimation.status === "calculated" || estimation.status === "finalized";

  const handleSave = () => {
    save({
      quote_number: estimation.quote_number,
      building_name: estimation.building_name,
      input_data: estimation.input_data,
    });
  };

  const handleCalculate = () => {
    // Extract markups from input_data for the calculate request
    const markups = {
      steel: Number(estimation.input_data?.markup_steel) || 0,
      panels: Number(estimation.input_data?.markup_panels) || 0,
      ssl: Number(estimation.input_data?.markup_ssl) || 0,
      finance: Number(estimation.input_data?.markup_finance) || 0,
    };
    // Use saveAndCalculate to flush any pending debounced saves first
    saveAndCalculate(
      {
        quote_number: estimation.quote_number,
        building_name: estimation.building_name,
      },
      estimation.input_data as InputData,
      markups
    );
  };

  const handleFillTestData = () => {
    const topLevelFields = {
      quote_number: "HQ-TEST-001",
      building_name: "Test Warehouse",
      project_name: "Test Project",
      customer_name: "Test Customer",
      revision_no: "R00",
      building_no: "B1",
      salesperson_code: "SP01",
    };

    // Realistic PEB building values (Quote 53305 reference)
    const testInputData: InputData = {
      bay_spacing: "1@6.865+1@9.104+2@9.144",
      span_widths: "1@28.5",
      back_eave_height: 7.5,
      front_eave_height: 7.5,
      left_roof_slope: 1.0,
      right_roof_slope: 1.0,
      frame_type: "Clear Span",
      base_type: "Pinned Base",
      cf_finish: 3,
      panel_profile: "M45-250",
      outer_skin_material: "AZ Steel",
      dead_load: 0.1,
      live_load: 0.57,
      wind_speed: 0.7,
      collateral_load: 0,
      roof_panel_code: "",
      wall_panel_code: "",
      core_thickness: 50,
      paint_system: "",
      monitor_type: "None",
      monitor_width: 0,
      monitor_height: 0,
      monitor_length: 0,
      markup_steel: 0,
      markup_panels: 0,
      markup_ssl: 0,
      markup_finance: 0,
    };

    // Single atomic save + calculate (no debounce race condition)
    saveAndCalculate(topLevelFields, testInputData, {
      steel: 0,
      panels: 0,
      ssl: 0,
      finance: 0,
    });
  };

  const handlePrint = () => {
    const wideSheets: SheetTab[] = ["detail", "fcpbs"];
    const style = document.createElement("style");
    style.id = "print-orientation";
    if (wideSheets.includes(activeTab)) {
      style.textContent = "@page { size: landscape; }";
    } else {
      style.textContent = "@page { size: portrait; }";
    }
    document.getElementById("print-orientation")?.remove();
    document.head.appendChild(style);
    window.print();
  };

  return (
    <div className="fixed inset-0 flex flex-col bg-gray-50">
      {/* Header */}
      <EstimationHeader
        estimation={estimation}
        isSaving={isSaving}
        isCalculating={isCalculating}
        onSave={handleSave}
        onCalculate={handleCalculate}
        onFillTestData={handleFillTestData}
        onPrint={handlePrint}
      />

      {/* Error bar */}
      {error && (
        <div className="no-print bg-red-50 text-red-700 text-sm px-4 py-2 border-b border-red-200">
          {error}
        </div>
      )}

      {/* Print-only header (hidden on screen, visible in print) */}
      <PrintHeader
        quoteNumber={estimation.quote_number || ""}
        buildingName={estimation.building_name || ""}
        revision={String(estimation.revision_no || "0")}
        activeTab={activeTab}
      />

      {/* Tab Content Area */}
      <div className="flex-1 overflow-hidden flex flex-col p-4">
        {activeTab === "input" && (
          <InputSheet
            estimation={estimation}
            onInputDataChange={updateInputData}
            onFieldsChange={updateFields}
          />
        )}
        {activeTab === "recap" && (
          <RecapSheet
            estimationId={id}
            version={estimation.updated_at}
          />
        )}
        {activeTab === "detail" && (
          <DetailSheet
            estimationId={id}
            version={estimation.updated_at}
          />
        )}
        {activeTab === "fcpbs" && (
          <FCPBSSheet
            estimationId={id}
            version={estimation.updated_at}
          />
        )}
        {activeTab === "sal" && (
          <SALSheet
            estimationId={id}
            version={estimation.updated_at}
          />
        )}
        {activeTab === "boq" && (
          <BOQSheet
            estimationId={id}
            version={estimation.updated_at}
          />
        )}
        {activeTab === "jaf" && (
          <JAFSheet
            estimationId={id}
            version={estimation.updated_at}
          />
        )}
      </div>

      {/* Tab Bar */}
      <TabBar
        activeTab={activeTab}
        onTabChange={setActiveTab}
        isCalculated={isCalculated}
      />
    </div>
  );
}
