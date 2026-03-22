import { lazy, Suspense } from "react";
import { BrowserRouter, Routes, Route } from "react-router-dom";
import { QueryClientProvider } from "@tanstack/react-query";
import { ReactQueryDevtools } from "@tanstack/react-query-devtools";
import { queryClient } from "@/lib/query-client";
import { ErrorBoundary } from "@/components/ErrorBoundary";
import LoginPage from "@/features/auth/pages/LoginPage";
import RegisterPage from "@/features/auth/pages/RegisterPage";
import PrivateRoute from "@/components/ui/PrivateRoute";
import DashboardLayout from "@/components/layouts/DashboardLayout";

// Lazy-loaded feature pages
const DashboardPage = lazy(() => import("@/features/dashboard/pages/DashboardPage"));
const PatientProfilePage = lazy(() => import("@/features/patient-profile/pages/PatientProfilePage"));
const CommonsPage = lazy(() => import("@/features/commons/pages/CommonsPage"));
const SettingsPage = lazy(() => import("@/features/settings/pages/SettingsPage"));

// Cases
const CaseListPage = lazy(() => import("@/features/cases/pages/CaseListPage"));
const CaseDetailPage = lazy(() => import("@/features/cases/pages/CaseDetailPage"));

// Sessions (Collaboration)
const SessionListPage = lazy(() => import("@/features/collaboration/pages/SessionListPage"));
const SessionDetailPage = lazy(() => import("@/features/collaboration/pages/SessionDetailPage"));

// Decisions
const DecisionDashboardPage = lazy(() => import("@/features/decisions/pages/DecisionDashboardPage"));

// Admin pages
const AdminDashboardPage = lazy(() => import("@/features/administration/pages/AdminDashboardPage"));
const UsersPage = lazy(() => import("@/features/administration/pages/UsersPage"));
const UserAuditPage = lazy(() => import("@/features/administration/pages/UserAuditPage"));
const RolesPage = lazy(() => import("@/features/administration/pages/RolesPage"));
const AiProvidersPage = lazy(() => import("@/features/administration/pages/AiProvidersPage"));
const SystemHealthPage = lazy(() => import("@/features/administration/pages/SystemHealthPage"));

function PageLoader() {
  return (
    <div style={{ display: "flex", alignItems: "center", justifyContent: "center", height: "50vh" }}>
      <div style={{ color: "var(--text-muted)", fontSize: "var(--text-sm)" }}>Loading...</div>
    </div>
  );
}

function NotFound() {
  return (
    <div>
      <h1 style={{ fontSize: "var(--text-2xl)", fontWeight: 600, color: "var(--text-primary)", marginBottom: "var(--space-4)" }}>
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
    <ErrorBoundary>
      <QueryClientProvider client={queryClient}>
        <BrowserRouter>
          <Suspense fallback={<PageLoader />}>
            <Routes>
              {/* Public routes */}
              <Route path="/login" element={<LoginPage />} />
              <Route path="/register" element={<RegisterPage />} />

              {/* Protected routes */}
              <Route
                path="/"
                element={
                  <PrivateRoute>
                    <DashboardLayout />
                  </PrivateRoute>
                }
              >
                {/* Dashboard */}
                <Route index element={<DashboardPage />} />

                {/* Cases */}
                <Route path="cases" element={<CaseListPage />} />
                <Route path="cases/:id" element={<CaseDetailPage />} />

                {/* Sessions */}
                <Route path="sessions" element={<SessionListPage />} />
                <Route path="sessions/:id" element={<SessionDetailPage />} />

                {/* Patient Profiles */}
                <Route path="profiles" element={<PatientProfilePage />} />
                <Route path="profiles/:personId" element={<PatientProfilePage />} />

                {/* Decisions */}
                <Route path="decisions" element={<DecisionDashboardPage />} />

                {/* Commons */}
                <Route path="commons" element={<CommonsPage />} />
                <Route path="commons/:slug" element={<CommonsPage />} />

                {/* Settings */}
                <Route path="settings" element={<SettingsPage />} />

                {/* Admin */}
                <Route path="admin" element={<AdminDashboardPage />} />
                <Route path="admin/users" element={<UsersPage />} />
                <Route path="admin/user-audit" element={<UserAuditPage />} />
                <Route path="admin/roles" element={<RolesPage />} />
                <Route path="admin/ai-providers" element={<AiProvidersPage />} />
                <Route path="admin/system-health" element={<SystemHealthPage />} />

                {/* 404 */}
                <Route path="*" element={<NotFound />} />
              </Route>
            </Routes>
          </Suspense>
        </BrowserRouter>
        <ReactQueryDevtools initialIsOpen={false} />
      </QueryClientProvider>
    </ErrorBoundary>
  );
}
