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
