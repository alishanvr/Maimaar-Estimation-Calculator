"use client";

import {
  createContext,
  useContext,
  useState,
  useEffect,
  ReactNode,
} from "react";
import api from "@/lib/api";

export interface BrandingSettings {
  app_name: string;
  company_name: string;
  logo_url: string | null;
  favicon_url: string | null;
  primary_color: string;
}

const DEFAULTS: BrandingSettings = {
  app_name: "Maimaar Estimation Calculator",
  company_name: "Maimaar",
  logo_url: null,
  favicon_url: null,
  primary_color: "#3B82F6",
};

interface BrandingContextType {
  branding: BrandingSettings;
  isLoading: boolean;
}

const BrandingContext = createContext<BrandingContextType | undefined>(
  undefined
);

export function BrandingProvider({ children }: { children: ReactNode }) {
  const [branding, setBranding] = useState<BrandingSettings>(DEFAULTS);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    api
      .get("/app-settings")
      .then((response) => {
        setBranding({ ...DEFAULTS, ...response.data });
      })
      .catch(() => {
        // Keep defaults on failure
      })
      .finally(() => {
        setIsLoading(false);
      });
  }, []);

  return (
    <BrandingContext.Provider value={{ branding, isLoading }}>
      {children}
    </BrandingContext.Provider>
  );
}

export function useBranding(): BrandingContextType {
  const context = useContext(BrandingContext);
  if (context === undefined) {
    throw new Error("useBranding must be used within a BrandingProvider");
  }
  return context;
}
