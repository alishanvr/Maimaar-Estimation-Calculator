"use client";

import { useState, useEffect, useCallback } from "react";
import { useRouter } from "next/navigation";
import { useRealParam } from "@/hooks/useRealParam";
import {
  getProject,
  updateProject,
  getProjectBuildings,
  getProjectHistory,
  duplicateBuilding,
  removeBuildingFromProject,
} from "@/lib/projects";
import { createEstimation } from "@/lib/estimations";
import type {
  Project,
  Estimation,
  ActivityLogEntry,
  ProjectStatus,
  EstimationStatus,
} from "@/types";

const STATUS_OPTIONS: { label: string; value: ProjectStatus }[] = [
  { label: "Draft", value: "draft" },
  { label: "In Progress", value: "in_progress" },
  { label: "Completed", value: "completed" },
  { label: "Archived", value: "archived" },
];

const STATUS_BADGE: Record<ProjectStatus, string> = {
  draft: "bg-gray-100 text-gray-700",
  in_progress: "bg-yellow-100 text-yellow-700",
  completed: "bg-green-100 text-green-700",
  archived: "bg-blue-100 text-blue-700",
};

const EST_STATUS_BADGE: Record<EstimationStatus, string> = {
  draft: "bg-gray-100 text-gray-700",
  calculated: "bg-green-100 text-green-700",
  finalized: "bg-primary/15 text-primary",
};

export default function ProjectDetailClient() {
  const router = useRouter();
  const id = Number(useRealParam("id", 1));

  const [project, setProject] = useState<Project | null>(null);
  const [buildings, setBuildings] = useState<Estimation[]>([]);
  const [history, setHistory] = useState<ActivityLogEntry[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [activeSection, setActiveSection] = useState<
    "buildings" | "history"
  >("buildings");
  const [isEditing, setIsEditing] = useState(false);
  const [editForm, setEditForm] = useState({
    project_name: "",
    customer_name: "",
    location: "",
    description: "",
    status: "draft" as ProjectStatus,
  });

  const fetchProject = useCallback(async () => {
    setIsLoading(true);
    try {
      const [proj, bldgs] = await Promise.all([
        getProject(id),
        getProjectBuildings(id),
      ]);
      setProject(proj);
      setBuildings(bldgs);
      setEditForm({
        project_name: proj.project_name,
        customer_name: proj.customer_name || "",
        location: proj.location || "",
        description: proj.description || "",
        status: proj.status,
      });
    } catch {
      alert("Failed to load project.");
    } finally {
      setIsLoading(false);
    }
  }, [id]);

  const fetchHistory = useCallback(async () => {
    try {
      const data = await getProjectHistory(id);
      setHistory(data);
    } catch {
      setHistory([]);
    }
  }, [id]);

  useEffect(() => {
    fetchProject();
  }, [fetchProject]);

  useEffect(() => {
    if (activeSection === "history") {
      fetchHistory();
    }
  }, [activeSection, fetchHistory]);

  const handleSave = async () => {
    if (!project) return;
    setIsSaving(true);
    try {
      const updated = await updateProject(project.id, editForm);
      setProject(updated);
      setIsEditing(false);
    } catch {
      alert("Failed to update project.");
    } finally {
      setIsSaving(false);
    }
  };

  const handleAddBuilding = async () => {
    if (!project) return;
    try {
      const estimation = await createEstimation({
        project_id: project.id,
        building_name: "New Building",
        project_name: project.project_name,
        customer_name: project.customer_name || undefined,
      });
      router.push(`/estimations/${estimation.id}`);
    } catch {
      alert("Failed to create building.");
    }
  };

  const handleDuplicate = async (estimationId: number) => {
    if (!project) return;
    try {
      await duplicateBuilding(project.id, estimationId);
      fetchProject();
    } catch {
      alert("Failed to duplicate building.");
    }
  };

  const handleRemove = async (estimationId: number) => {
    if (!project) return;
    if (!confirm("Remove this building from the project?")) return;
    try {
      await removeBuildingFromProject(project.id, estimationId);
      fetchProject();
    } catch {
      alert("Failed to remove building.");
    }
  };

  const formatNumber = (value: number | null, decimals = 2): string => {
    if (value === null || value === undefined) return "\u2014";
    return value.toLocaleString("en-US", {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    });
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center p-12">
        <p className="text-gray-400">Loading project...</p>
      </div>
    );
  }

  if (!project) {
    return (
      <div className="flex items-center justify-center p-12">
        <p className="text-red-500">Project not found.</p>
      </div>
    );
  }

  return (
    <div>
      {/* Back button */}
      <button
        onClick={() => router.push("/projects")}
        className="text-sm text-gray-500 hover:text-gray-700 mb-4 transition"
      >
        &larr; Back to Projects
      </button>

      {/* Project Header */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div className="flex items-start justify-between">
          <div className="flex-1">
            {isEditing ? (
              <div className="space-y-3 max-w-lg">
                <div>
                  <label className="block text-xs font-medium text-gray-500 mb-1">
                    Project Name
                  </label>
                  <input
                    type="text"
                    value={editForm.project_name}
                    onChange={(e) =>
                      setEditForm((f) => ({
                        ...f,
                        project_name: e.target.value,
                      }))
                    }
                    className="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg"
                  />
                </div>
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block text-xs font-medium text-gray-500 mb-1">
                      Customer
                    </label>
                    <input
                      type="text"
                      value={editForm.customer_name}
                      onChange={(e) =>
                        setEditForm((f) => ({
                          ...f,
                          customer_name: e.target.value,
                        }))
                      }
                      className="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-medium text-gray-500 mb-1">
                      Location
                    </label>
                    <input
                      type="text"
                      value={editForm.location}
                      onChange={(e) =>
                        setEditForm((f) => ({
                          ...f,
                          location: e.target.value,
                        }))
                      }
                      className="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg"
                    />
                  </div>
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-500 mb-1">
                    Status
                  </label>
                  <select
                    value={editForm.status}
                    onChange={(e) =>
                      setEditForm((f) => ({
                        ...f,
                        status: e.target.value as ProjectStatus,
                      }))
                    }
                    className="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg"
                  >
                    {STATUS_OPTIONS.map((opt) => (
                      <option key={opt.value} value={opt.value}>
                        {opt.label}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-500 mb-1">
                    Description
                  </label>
                  <textarea
                    value={editForm.description}
                    onChange={(e) =>
                      setEditForm((f) => ({
                        ...f,
                        description: e.target.value,
                      }))
                    }
                    rows={2}
                    className="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg"
                  />
                </div>
                <div className="flex gap-2">
                  <button
                    onClick={handleSave}
                    disabled={isSaving}
                    className="bg-primary text-white px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-primary/80 transition disabled:opacity-50"
                  >
                    {isSaving ? "Saving..." : "Save"}
                  </button>
                  <button
                    onClick={() => setIsEditing(false)}
                    className="text-sm text-gray-500 hover:text-gray-700 transition"
                  >
                    Cancel
                  </button>
                </div>
              </div>
            ) : (
              <>
                <div className="flex items-center gap-3 mb-2">
                  <h2 className="text-xl font-bold text-gray-900">
                    {project.project_name}
                  </h2>
                  <span
                    className={`px-2 py-0.5 rounded-full text-xs font-medium ${STATUS_BADGE[project.status]}`}
                  >
                    {project.status.replace("_", " ")}
                  </span>
                </div>
                <div className="flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-500">
                  <span>
                    <span className="font-medium text-gray-600">Project #:</span>{" "}
                    <span className="font-mono">{project.project_number}</span>
                  </span>
                  {project.customer_name && (
                    <span>
                      <span className="font-medium text-gray-600">Customer:</span>{" "}
                      {project.customer_name}
                    </span>
                  )}
                  {project.location && (
                    <span>
                      <span className="font-medium text-gray-600">Location:</span>{" "}
                      {project.location}
                    </span>
                  )}
                </div>
                {project.description && (
                  <p className="text-sm text-gray-500 mt-2">
                    {project.description}
                  </p>
                )}
              </>
            )}
          </div>
          {!isEditing && (
            <button
              onClick={() => setIsEditing(true)}
              className="text-sm text-primary hover:text-primary/80 font-medium transition"
            >
              Edit
            </button>
          )}
        </div>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-3 gap-4 mb-6">
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
          <p className="text-xs font-medium text-gray-500 uppercase tracking-wider">
            Buildings
          </p>
          <p className="text-2xl font-bold text-gray-900 mt-1">
            {project.summary?.building_count ?? 0}
          </p>
        </div>
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
          <p className="text-xs font-medium text-gray-500 uppercase tracking-wider">
            Total Weight (MT)
          </p>
          <p className="text-2xl font-bold text-gray-900 mt-1">
            {formatNumber(project.summary?.total_weight ?? null, 2)}
          </p>
        </div>
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
          <p className="text-xs font-medium text-gray-500 uppercase tracking-wider">
            Total Price (AED)
          </p>
          <p className="text-2xl font-bold text-gray-900 mt-1">
            {formatNumber(project.summary?.total_price ?? null, 0)}
          </p>
        </div>
      </div>

      {/* Section Tabs */}
      <div className="flex gap-1 mb-4 bg-gray-100 rounded-lg p-1 w-fit">
        <button
          onClick={() => setActiveSection("buildings")}
          className={`px-4 py-1.5 text-sm rounded-md font-medium transition ${
            activeSection === "buildings"
              ? "bg-white text-gray-900 shadow-sm"
              : "text-gray-500 hover:text-gray-700"
          }`}
        >
          Buildings
        </button>
        <button
          onClick={() => setActiveSection("history")}
          className={`px-4 py-1.5 text-sm rounded-md font-medium transition ${
            activeSection === "history"
              ? "bg-white text-gray-900 shadow-sm"
              : "text-gray-500 hover:text-gray-700"
          }`}
        >
          History
        </button>
      </div>

      {/* Buildings Section */}
      {activeSection === "buildings" && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200">
            <h3 className="text-sm font-semibold text-gray-900">
              Buildings ({buildings.length})
            </h3>
            <button
              onClick={handleAddBuilding}
              className="bg-primary text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-primary/80 transition"
            >
              + Add Building
            </button>
          </div>

          {buildings.length === 0 ? (
            <div className="p-8 text-center">
              <p className="text-gray-400 text-sm">
                No buildings in this project yet.
              </p>
            </div>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="bg-gray-50 border-b border-gray-200">
                  <th className="text-left px-4 py-2 font-medium text-gray-500">
                    Building
                  </th>
                  <th className="text-left px-4 py-2 font-medium text-gray-500">
                    Quote #
                  </th>
                  <th className="text-left px-4 py-2 font-medium text-gray-500">
                    Status
                  </th>
                  <th className="text-right px-4 py-2 font-medium text-gray-500">
                    Weight (MT)
                  </th>
                  <th className="text-right px-4 py-2 font-medium text-gray-500">
                    Price (AED)
                  </th>
                  <th className="text-right px-4 py-2 font-medium text-gray-500">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {buildings.map((est) => (
                  <tr
                    key={est.id}
                    className="hover:bg-gray-50 cursor-pointer transition"
                    onClick={() => router.push(`/estimations/${est.id}`)}
                  >
                    <td className="px-4 py-2.5 font-medium text-gray-900">
                      {est.building_name || "Untitled"}
                    </td>
                    <td className="px-4 py-2.5 font-mono text-xs text-gray-600">
                      {est.quote_number || "\u2014"}
                    </td>
                    <td className="px-4 py-2.5">
                      <span
                        className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${EST_STATUS_BADGE[est.status]}`}
                      >
                        {est.status}
                      </span>
                    </td>
                    <td className="px-4 py-2.5 text-right font-mono text-xs">
                      {formatNumber(est.total_weight_mt, 2)}
                    </td>
                    <td className="px-4 py-2.5 text-right font-mono text-xs">
                      {formatNumber(est.total_price_aed, 0)}
                    </td>
                    <td className="px-4 py-2.5 text-right">
                      <div className="flex items-center justify-end gap-2">
                        <button
                          onClick={(e) => {
                            e.stopPropagation();
                            handleDuplicate(est.id);
                          }}
                          className="text-primary hover:text-primary/80 text-xs font-medium transition"
                        >
                          Duplicate
                        </button>
                        <button
                          onClick={(e) => {
                            e.stopPropagation();
                            handleRemove(est.id);
                          }}
                          className="text-red-500 hover:text-red-700 text-xs font-medium transition"
                        >
                          Remove
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      )}

      {/* History Section */}
      {activeSection === "history" && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div className="px-4 py-3 border-b border-gray-200">
            <h3 className="text-sm font-semibold text-gray-900">
              Activity History
            </h3>
          </div>
          {history.length === 0 ? (
            <div className="p-8 text-center">
              <p className="text-gray-400 text-sm">No activity recorded yet.</p>
            </div>
          ) : (
            <div className="divide-y divide-gray-100">
              {history.map((entry) => (
                <div key={entry.id} className="px-4 py-3 flex items-start gap-3">
                  <div className="w-2 h-2 bg-primary/40 rounded-full mt-1.5 flex-shrink-0" />
                  <div className="flex-1 min-w-0">
                    <p className="text-sm text-gray-900">{entry.description}</p>
                    <p className="text-xs text-gray-500 mt-0.5">
                      {entry.causer_name && (
                        <span className="font-medium">{entry.causer_name}</span>
                      )}
                      {entry.causer_name && " \u00B7 "}
                      {new Date(entry.created_at).toLocaleString("en-GB", {
                        day: "2-digit",
                        month: "short",
                        year: "numeric",
                        hour: "2-digit",
                        minute: "2-digit",
                      })}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
}
