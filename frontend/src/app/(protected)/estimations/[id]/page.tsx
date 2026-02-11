"use client";

import { useState, Suspense } from "react";
import { useParams, useRouter, useSearchParams } from "next/navigation";
import { useEstimation } from "@/hooks/useEstimation";
import { cloneEstimation, createRevision } from "@/lib/estimations";
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

function EstimationEditor() {
  const params = useParams();
  const router = useRouter();
  const searchParams = useSearchParams();
  const id = Number(params.id);
  const [activeTab, setActiveTab] = useState<SheetTab>("input");
  const showFillTestData = searchParams.get("fill_test_data") === "true";
  const {
    estimation,
    isLoading,
    isSaving,
    isCalculating,
    error,
    save,
    updateInputData,
    updateFields,
    saveAndCalculate,
    finalize,
    unlock,
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
  const isFinalized = estimation.status === "finalized";

  const handleSave = () => {
    save({
      quote_number: estimation.quote_number,
      building_name: estimation.building_name,
      input_data: estimation.input_data,
    });
  };

  const handleCalculate = () => {
    const markups = {
      steel: Number(estimation.input_data?.markup_steel) || 0,
      panels: Number(estimation.input_data?.markup_panels) || 0,
      ssl: Number(estimation.input_data?.markup_ssl) || 0,
      finance: Number(estimation.input_data?.markup_finance) || 0,
    };
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

    const testInputData: InputData = {
      bay_spacing: "1@6.865+1@9.104+2@9.144",
      span_widths: "1@28.5",
      back_eave_height: 7.5,
      front_eave_height: 7.5,
      left_roof_slope: 1.0,
      right_roof_slope: 1.0,
      frame_type: "Clear Span",
      base_type: "Pinned Base",
      cf_finish: "Painted",
      panel_profile: "M45-250",
      outer_skin_material: "AZ Steel",
      min_thickness: 6,
      double_weld: "No",
      left_endwall_columns: "1@4.5+1@5",
      left_endwall_type: "Bearing Frame",
      left_endwall_portal: "None",
      right_endwall_columns: "1@4.5+1@5",
      right_endwall_type: "Bearing Frame",
      right_endwall_portal: "None",
      purlin_depth: "200",
      roof_sag_rods: "0",
      wall_sag_rods: "0",
      roof_sag_rod_dia: "12",
      wall_sag_rod_dia: "12",
      bracing_type: "Cables",
      dead_load: 0.1,
      live_load: 0.57,
      wind_speed: 0.7,
      collateral_load: 0,
      live_load_permanent: 0,
      live_load_floor: 0,
      additional_load: 0,
      roof_panel_code: "",
      wall_panel_code: "",
      core_thickness: 50,
      paint_system: "",
      roof_top_skin: "None",
      roof_core: "-",
      roof_bottom_skin: "-",
      roof_insulation: "None",
      wall_top_skin: "None",
      wall_core: "-",
      wall_bottom_skin: "-",
      wall_insulation: "None",
      trim_size: "0.5 AZ",
      back_eave_condition: "Gutter+Dwnspts",
      front_eave_condition: "Gutter+Dwnspts",
      wwm_option: "None",
      bu_finish: "",
      monitor_type: "None",
      monitor_width: 0,
      monitor_height: 0,
      monitor_length: 0,
      freight_type: "By Mammut",
      freight_rate: 0,
      container_count: 6,
      container_rate: 2000,
      area_sales_code: 1,
      area_description: "Building Area",
      acc_sales_code: 1,
      acc_description: "Accessories",
      sales_office: "Dubai",
      num_buildings: 1,
      erection_price: 0,
      markup_steel: 0,
      markup_panels: 0,
      markup_ssl: 0,
      markup_finance: 0,
      openings: [
        {
          location: "Front Sidewall",
          size: "4x4",
          qty: 2,
          purlin_support: 0,
          bracing: 0,
        },
      ],
      accessories: [
        { description: "Skylight Panel", code: "SL-01", qty: 4 },
      ],
      cranes: [
        {
          description: "EOT Crane",
          sales_code: 4,
          capacity: 10,
          duty: "M",
          rail_centers: 25,
          crane_run: "3@9.144",
        },
      ],
      mezzanines: [
        {
          description: "Office Mezzanine",
          sales_code: 2,
          col_spacing: "2@6",
          beam_spacing: "1@6",
          joist_spacing: "1@3",
          clear_height: 4.5,
          double_welded: "No",
          deck_type: "Concrete",
          n_stairs: 1,
          dead_load: 0.15,
          live_load: 2.5,
          additional_load: 0,
          bu_finish: "",
          cf_finish: "",
          min_thickness: 6,
        },
      ],
      partitions: [
        {
          description: "Internal Partition",
          sales_code: 11,
          direction: "Transverse",
          bu_finish: "",
          cf_finish: "",
          wind_speed: 0,
          col_spacing: "1@6",
          height: 6,
          opening_height: 4,
          front_sheeting: "None",
          back_sheeting: "None",
          insulation: "None",
        },
      ],
      canopies: [
        {
          description: "Front Canopy",
          sales_code: 3,
          frame_type: "Lean-To",
          location: "Front",
          height: 4,
          width: 3,
          col_spacing: "2@6",
          roof_sheeting: "None",
          drainage: "None",
          soffit: "None",
          wall_sheeting: "None",
          internal_sheeting: "None",
          bu_finish: "",
          cf_finish: "",
          live_load: 0.57,
          wind_speed: 0.7,
        },
      ],
    };

    saveAndCalculate(topLevelFields, testInputData, {
      steel: 0,
      panels: 0,
      ssl: 0,
      finance: 0,
    });
  };

  const handleClone = async () => {
    try {
      const cloned = await cloneEstimation(id);
      router.push(`/estimations/${cloned.id}`);
    } catch {
      alert("Failed to clone estimation.");
    }
  };

  const handleCreateRevision = async () => {
    try {
      const revision = await createRevision(id);
      router.push(`/estimations/${revision.id}`);
    } catch {
      alert("Failed to create revision.");
    }
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
        onFillTestData={showFillTestData ? handleFillTestData : undefined}
        onClone={handleClone}
        onCreateRevision={handleCreateRevision}
        onFinalize={finalize}
        onUnlock={unlock}
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
            readOnly={isFinalized}
          />
        )}
        {activeTab === "recap" && (
          <RecapSheet estimationId={id} version={estimation.updated_at} />
        )}
        {activeTab === "detail" && (
          <DetailSheet estimationId={id} version={estimation.updated_at} />
        )}
        {activeTab === "fcpbs" && (
          <FCPBSSheet estimationId={id} version={estimation.updated_at} />
        )}
        {activeTab === "sal" && (
          <SALSheet estimationId={id} version={estimation.updated_at} />
        )}
        {activeTab === "boq" && (
          <BOQSheet estimationId={id} version={estimation.updated_at} />
        )}
        {activeTab === "jaf" && (
          <JAFSheet estimationId={id} version={estimation.updated_at} />
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

export default function EstimationEditorPage() {
  return (
    <Suspense
      fallback={
        <div className="min-h-screen flex items-center justify-center">
          <p className="text-gray-400">Loading...</p>
        </div>
      }
    >
      <EstimationEditor />
    </Suspense>
  );
}
