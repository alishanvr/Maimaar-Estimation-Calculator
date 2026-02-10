"use client";

import { useState } from "react";
import { useParams } from "next/navigation";
import { useEstimation } from "@/hooks/useEstimation";
import EstimationHeader from "@/components/estimations/EstimationHeader";
import TabBar from "@/components/estimations/TabBar";
import InputSheet from "@/components/estimations/InputSheet";
import SheetTabPlaceholder from "@/components/estimations/SheetTab";
import type { SheetTab } from "@/types";

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
    calculate(markups);
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
      />

      {/* Error bar */}
      {error && (
        <div className="bg-red-50 text-red-700 text-sm px-4 py-2 border-b border-red-200">
          {error}
        </div>
      )}

      {/* Tab Content Area */}
      <div className="flex-1 overflow-hidden flex flex-col">
        {activeTab === "input" ? (
          <InputSheet
            estimation={estimation}
            onInputDataChange={updateInputData}
            onFieldsChange={updateFields}
          />
        ) : (
          <SheetTabPlaceholder sheet={activeTab} isCalculated={isCalculated} />
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
