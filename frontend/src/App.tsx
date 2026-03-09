import { BrowserRouter, Routes, Route } from "react-router-dom";
import { QueryClientProvider } from "@tanstack/react-query";
import { ReactQueryDevtools } from "@tanstack/react-query-devtools";
import { queryClient } from "@/lib/query-client";
import LoginPage from "@/features/auth/pages/LoginPage";
import RegisterPage from "@/features/auth/pages/RegisterPage";
import PrivateRoute from "@/components/ui/PrivateRoute";
import DashboardLayout from "@/components/layouts/DashboardLayout";

function DashboardHome() {
  return (
    <div>
      <h1
        style={{
          fontSize: "var(--text-2xl)",
          fontWeight: 600,
          color: "var(--text-primary)",
          marginBottom: "var(--space-4)",
        }}
      >
        Dashboard
      </h1>
      <p style={{ color: "var(--text-muted)" }}>
        Welcome to Aurora. Select an item from the sidebar to get started.
      </p>
    </div>
  );
}

function NotFound() {
  return (
    <div>
      <h1
        style={{
          fontSize: "var(--text-2xl)",
          fontWeight: 600,
          color: "var(--text-primary)",
          marginBottom: "var(--space-4)",
        }}
      >
        404 — Page Not Found
      </h1>
      <p style={{ color: "var(--text-muted)" }}>
        The page you are looking for does not exist.
      </p>
    </div>
  );
}

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          <Route
            path="/"
            element={
              <PrivateRoute>
                <DashboardLayout />
              </PrivateRoute>
            }
          >
            <Route index element={<DashboardHome />} />
            <Route path="*" element={<NotFound />} />
          </Route>
        </Routes>
      </BrowserRouter>
      <ReactQueryDevtools initialIsOpen={false} />
    </QueryClientProvider>
  );
}
