import api from "./api";
import type { MbsdbProduct, RawMaterial, SsdbProduct } from "@/types";

// ── MBSDB Product Search ────────────────────────────────────────────

export async function searchProducts(
  q: string,
  category?: string
): Promise<MbsdbProduct[]> {
  const params: Record<string, string> = { q };
  if (category) {
    params.category = category;
  }
  const { data } = await api.get("/products/search", { params });
  return data.data;
}

export async function getProductByCode(code: string): Promise<MbsdbProduct> {
  const { data } = await api.get(`/products/${encodeURIComponent(code)}`);
  return data.data;
}

// ── SSDB (Structural Steel) Product Search ──────────────────────────

export async function searchStructuralSteel(
  q: string
): Promise<SsdbProduct[]> {
  const { data } = await api.get("/structural-steel/search", {
    params: { q },
  });
  return data.data;
}

// ── Raw Materials Search ────────────────────────────────────────────

export async function searchRawMaterials(
  q: string
): Promise<RawMaterial[]> {
  const { data } = await api.get("/raw-materials/search", {
    params: { q },
  });
  return data.data;
}
