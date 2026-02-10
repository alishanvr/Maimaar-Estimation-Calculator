import api from "./api";
import type {
  Estimation,
  DesignConfiguration,
  Markups,
  PaginatedResponse,
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
