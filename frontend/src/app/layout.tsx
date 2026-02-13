import { Inter } from "next/font/google";
import "./globals.css";
import { AuthProvider } from "@/contexts/AuthContext";
import { BrandingProvider } from "@/contexts/BrandingContext";
import DynamicHead from "@/components/DynamicHead";

const inter = Inter({ subsets: ["latin"] });

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" suppressHydrationWarning>
      <body className={`${inter.className} antialiased`} suppressHydrationWarning>
        <BrandingProvider>
          <DynamicHead />
          <AuthProvider>{children}</AuthProvider>
        </BrandingProvider>
      </body>
    </html>
  );
}
