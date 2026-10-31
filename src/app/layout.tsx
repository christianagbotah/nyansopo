import type { Metadata, Viewport } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import "./globals.css";
import { Toaster } from "@/components/ui/toaster";
import { ThemeProvider } from "@/components/theme-provider";
import { PwaBootstrap } from "@/components/pwa-bootstrap";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "Nyansapo — School Management Cloud for Ghana",
  description:
    "The Wisdom Knot that binds Ghana's private schools. School operations, fees & defaulters, cross-school transfers, attendance, gradebook, report cards, AI insights and GNACOPS association oversight — offline-first on Windows, macOS, iOS, Android and web.",
  keywords: ["Ghana", "private schools", "GNACOPS", "school management", "GES", "Cambridge", "IB", "fees", "defaulters", "transfers"],
  manifest: "/manifest.webmanifest",
  icons: {
    icon: [
      { url: "/icon-192.png", sizes: "192x192", type: "image/png" },
      { url: "/icon-512.png", sizes: "512x512", type: "image/png" },
    ],
    apple: "/apple-touch-icon.png",
  },
  applicationName: "Nyansapo",
  appleWebApp: { capable: true, statusBarStyle: "black-translucent", title: "Nyansapo" },
};

export const viewport: Viewport = {
  themeColor: [
    { media: "(prefers-color-scheme: light)", color: "#0e5f3f" },
    { media: "(prefers-color-scheme: dark)", color: "#131009" },
  ],
  width: "device-width",
  initialScale: 1,
  viewportFit: "cover",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" suppressHydrationWarning>
      <body className={`${geistSans.variable} ${geistMono.variable} antialiased bg-background text-foreground min-h-screen flex flex-col`}>
        <ThemeProvider attribute="class" defaultTheme="light" enableSystem={false} disableTransitionOnChange>
          {children}
          <Toaster />
          <PwaBootstrap />
        </ThemeProvider>
      </body>
    </html>
  );
}
