import api from "./api";
import type {
  Estimation,
  ComparisonEstimation,
  DesignConfiguration,
  Markups,
  PaginatedResponse,
  RevisionEntry,
  SheetTab,
} from "@/types";

// ── Estimation CRUD ────────────────────────────────────────────────

export async function listEstimations(params?: {
  page?: number;
  status?: string;
}): Promise<PaginatedResponse<Estimation>> {
  const { data } = await api.get("/estimations", { params });
  return data;
}

export async function createEstimation(
  payload: Partial<Estimation>
): Promise<Estimation> {
  const { data } = await api.post("/estimations", payload);
  return data.data;
}

export async function getEstimation(id: number): Promise<Estimation> {
  const { data } = await api.get(`/estimations/${id}`);
  return data.data;
}

export async function updateEstimation(
  id: number,
  payload: Partial<Estimation>
): Promise<Estimation> {
  const { data } = await api.put(`/estimations/${id}`, payload);
  return data.data;
}

export async function deleteEstimation(id: number): Promise<void> {
  await api.delete(`/estimations/${id}`);
}

// ── Clone & Revision ────────────────────────────────────────────────

export async function cloneEstimation(id: number): Promise<Estimation> {
  const { data } = await api.post(`/estimations/${id}/clone`);
  return data.data;
}

export async function createRevision(id: number): Promise<Estimation> {
  const { data } = await api.post(`/estimations/${id}/revision`);
  return data.data;
}

export async function getRevisions(id: number): Promise<RevisionEntry[]> {
  const { data } = await api.get(`/estimations/${id}/revisions`);
  return data.data;
}

export async function finalizeEstimation(id: number): Promise<Estimation> {
  const { data } = await api.post(`/estimations/${id}/finalize`);
  return data.data;
}

export async function unlockEstimation(id: number): Promise<Estimation> {
  const { data } = await api.post(`/estimations/${id}/unlock`);
  return data.data;
}

// ── Compare & Bulk Export ───────────────────────────────────────────

export async function compareEstimations(
  ids: number[]
): Promise<ComparisonEstimation[]> {
  const { data } = await api.post("/estimations/compare", { ids });
  return data.data;
}

export async function bulkExportEstimations(
  ids: number[],
  sheets: string[]
): Promise<Blob> {
  const { data } = await api.post(
    "/estimations/bulk-export",
    { ids, sheets },
    { responseType: "blob" }
  );
  return data;
}

// ── Calculation ────────────────────────────────────────────────────

export async function calculateEstimation(
  id: number,
  markups?: Markups
): Promise<Estimation> {
  const { data } = await api.post(`/estimations/${id}/calculate`, {
    markups,
  });
  return data.data;
}

// ── Sheet Data ─────────────────────────────────────────────────────

export async function getSheetData(
  id: number,
  sheet: Exclude<SheetTab, "input">
): Promise<unknown> {
  const { data } = await api.get(`/estimations/${id}/${sheet}`);
  return data.data;
}

// ── Design Configurations ──────────────────────────────────────────

export async function getDesignConfigurations(
  category: string
): Promise<DesignConfiguration[]> {
  const { data } = await api.get("/design-configurations", {
    params: { category },
  });
  return data.data;
}

export async function getFreightCodes(): Promise<DesignConfiguration[]> {
  const { data } = await api.get("/freight-codes");
  return data.data;
}

export async function getPaintSystems(): Promise<DesignConfiguration[]> {
  const { data } = await api.get("/paint-systems");
  return data.data;
}

// ── PDF Export ────────────────────────────────────────────────────

export async function exportRecapPdf(id: number): Promise<Blob> {
  const { data } = await api.get(`/estimations/${id}/export/recap`, {
    responseType: "blob",
  });
  return data;
}

export async function exportDetailPdf(id: number): Promise<Blob> {
  const { data } = await api.get(`/estimations/${id}/export/detail`, {
    responseType: "blob",
  });
  return data;
}

export async function exportFcpbsPdf(id: number): Promise<Blob> {
  const { data } = await api.get(`/estimations/${id}/export/fcpbs`, {
    responseType: "blob",
  });
  return data;
}

export async function exportSalPdf(id: number): Promise<Blob> {
  const { data } = await api.get(`/estimations/${id}/export/sal`, {
    responseType: "blob",
  });
  return data;
}

export async function exportBoqPdf(id: number): Promise<Blob> {
  const { data } = await api.get(`/estimations/${id}/export/boq`, {
    responseType: "blob",
  });
  return data;
}

export async function exportJafPdf(id: number): Promise<Blob> {
  const { data } = await api.get(`/estimations/${id}/export/jaf`, {
    responseType: "blob",
  });
  return data;
}

export async function exportRawmatPdf(id: number): Promise<Blob> {
  const { data } = await api.get(`/estimations/${id}/export/rawmat`, {
    responseType: "blob",
  });
  return data;
}

// ── CSV Export ────────────────────────────────────────────────────

export type CsvSheetType =
  | "recap"
  | "detail"
  | "fcpbs"
  | "sal"
  | "boq"
  | "jaf"
  | "rawmat";

export async function exportSheetCsv(
  id: number,
  sheetType: CsvSheetType
): Promise<Blob> {
  const { data } = await api.get(
    `/estimations/${id}/export/${sheetType}/csv`,
    { responseType: "blob" }
  );
  return data;
}

// ── ERP Export ────────────────────────────────────────────────────

export interface ErpExportParams {
  job_number: string;
  building_number: string;
  contract_date: string;
  fiscal_year: number;
}

export async function exportErpCsv(
  id: number,
  params: ErpExportParams
): Promise<Blob> {
  try {
    const { data } = await api.get(`/estimations/${id}/export/erp`, {
      params,
      responseType: "blob",
    });
    return data;
  } catch (err: unknown) {
    // When responseType is "blob", error responses are also blobs.
    // Parse the blob back to JSON to extract the server error message.
    if (err && typeof err === "object" && "response" in err) {
      const axiosErr = err as { response?: { data?: Blob } };
      const blob = axiosErr.response?.data;
      if (blob instanceof Blob) {
        try {
          const json = JSON.parse(await blob.text());
          if (json.message) {
            throw new Error(json.message);
          }
        } catch (parseErr) {
          if (parseErr instanceof Error && parseErr.message) {
            throw parseErr;
          }
        }
      }
    }
    throw err;
  }
}

// ── CSV Import ────────────────────────────────────────────────────

export interface ImportResult {
  message: string;
  data: {
    items: Array<Record<string, unknown>>;
    errors: string[];
    row_count: number;
  };
}

export async function importEstimationCsv(
  id: number,
  file: File,
  options?: { commit?: boolean; merge_strategy?: "append" | "replace" }
): Promise<ImportResult> {
  const formData = new FormData();
  formData.append("file", file);
  if (options?.merge_strategy) {
    formData.append("merge_strategy", options.merge_strategy);
  }

  const params = new URLSearchParams();
  if (options?.commit) {
    params.set("commit", "true");
  }

  const { data } = await api.post(
    `/estimations/${id}/import?${params.toString()}`,
    formData,
    { headers: { "Content-Type": "multipart/form-data" } }
  );
  return data;
}
