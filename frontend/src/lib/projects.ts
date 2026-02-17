import api from "./api";
import type {
  Project,
  Estimation,
  PaginatedResponse,
  ActivityLogEntry,
} from "@/types";

// ── Project CRUD ──────────────────────────────────────────────────

export async function listProjects(params?: {
  page?: number;
  status?: string;
  search?: string;
}): Promise<PaginatedResponse<Project>> {
  const { data } = await api.get("/projects", { params });
  return data;
}

export async function createProject(
  payload: Partial<Project>
): Promise<Project> {
  const { data } = await api.post("/projects", payload);
  return data.data;
}

export async function getProject(id: number): Promise<Project> {
  const { data } = await api.get(`/projects/${id}`);
  return data.data;
}

export async function updateProject(
  id: number,
  payload: Partial<Project>
): Promise<Project> {
  const { data } = await api.put(`/projects/${id}`, payload);
  return data.data;
}

export async function deleteProject(id: number): Promise<void> {
  await api.delete(`/projects/${id}`);
}

// ── Buildings ─────────────────────────────────────────────────────

export async function getProjectBuildings(
  id: number
): Promise<Estimation[]> {
  const { data } = await api.get(`/projects/${id}/buildings`);
  return data.data;
}

export async function addBuildingToProject(
  projectId: number,
  estimationId: number
): Promise<Estimation> {
  const { data } = await api.post(`/projects/${projectId}/buildings`, {
    estimation_id: estimationId,
  });
  return data.data;
}

export async function removeBuildingFromProject(
  projectId: number,
  estimationId: number
): Promise<void> {
  await api.delete(`/projects/${projectId}/buildings/${estimationId}`);
}

export async function duplicateBuilding(
  projectId: number,
  estimationId: number
): Promise<Estimation> {
  const { data } = await api.post(
    `/projects/${projectId}/buildings/${estimationId}/duplicate`
  );
  return data.data;
}

// ── History ───────────────────────────────────────────────────────

export async function getProjectHistory(
  id: number
): Promise<ActivityLogEntry[]> {
  const { data } = await api.get(`/projects/${id}/history`);
  return data.data;
}
