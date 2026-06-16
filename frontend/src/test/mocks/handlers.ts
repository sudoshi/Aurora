import { http, HttpResponse } from "msw";

export const handlers = [
  http.get("/api/auth/providers", () => {
    return HttpResponse.json({
      oidc_enabled: false,
      oidc_label: "Authentik OpenID Connect",
      oidc_redirect_path: "/api/auth/oidc/redirect",
    });
  }),

  http.post("/api/login", async ({ request }) => {
    const body = (await request.json()) as Record<string, string>;
    if (body.email === "admin@acumenus.net" && body.password === "superuser") {
      return HttpResponse.json({
        access_token: "test-token-abc123",
        user: {
          id: 1,
          name: "Admin User",
          email: "admin@acumenus.net",
          phone: null,
          avatar: null,
          phone_number: null,
          job_title: "Administrator",
          department: null,
          organization: "Acumenus",
          bio: null,
          must_change_password: false,
          is_active: true,
          last_login_at: "2026-03-25T10:00:00Z",
          roles: ["super-admin"],
          permissions: ["*"],
          created_at: "2026-01-01T00:00:00Z",
          updated_at: "2026-03-25T10:00:00Z",
        },
      });
    }
    return HttpResponse.json({ message: "Invalid credentials" }, { status: 401 });
  }),

  http.post("/api/auth/login", async ({ request }) => {
    const body = (await request.json()) as Record<string, string>;
    if (body.email === "admin@acumenus.net" && body.password === "superuser") {
      return HttpResponse.json({
        access_token: "test-token-abc123",
        user: {
          id: 1,
          name: "Admin User",
          email: "admin@acumenus.net",
          phone: null,
          avatar: null,
          phone_number: null,
          job_title: "Administrator",
          department: null,
          organization: "Acumenus",
          bio: null,
          must_change_password: false,
          is_active: true,
          last_login_at: "2026-03-25T10:00:00Z",
          roles: ["super-admin"],
          permissions: ["*"],
          created_at: "2026-01-01T00:00:00Z",
          updated_at: "2026-03-25T10:00:00Z",
        },
      });
    }
    return HttpResponse.json({ message: "Invalid credentials" }, { status: 401 });
  }),

  http.post("/api/auth/oidc/exchange", () => {
    return HttpResponse.json({
      access_token: "test-oidc-token-abc123",
      user: {
        id: 1,
        name: "Admin User",
        email: "admin@acumenus.net",
        phone: null,
        avatar: null,
        phone_number: null,
        job_title: "Administrator",
        department: null,
        organization: "Acumenus",
        bio: null,
        must_change_password: false,
        is_active: true,
        last_login_at: "2026-03-25T10:00:00Z",
        roles: ["super-admin"],
        permissions: ["*"],
        created_at: "2026-01-01T00:00:00Z",
        updated_at: "2026-03-25T10:00:00Z",
      },
    });
  }),

  http.get("/api/patients", () => {
    return HttpResponse.json({
      success: true,
      data: { data: [], total: 0, current_page: 1 },
    });
  }),

  http.get("/api/dashboard", () => {
    return HttpResponse.json({
      success: true,
      data: { patient_count: 0 },
    });
  }),

  http.get("/api/genomics/interactions", () => {
    return HttpResponse.json({
      success: true,
      data: [],
    });
  }),

  http.get("/api/odysseys", () =>
    HttpResponse.json({ success: true, data: [], meta: { total: 0, page: 1, per_page: 25, last_page: 1 } }),
  ),

  http.get("/api/hpo/search", () => HttpResponse.json({ success: true, data: [] })),

  http.get("/api/acmg/criteria", () => HttpResponse.json({ success: true, data: {} })),

  http.get("/api/patients/:id/kb-alerts", () => HttpResponse.json({ success: true, data: [] })),

  http.get("/api/kb-alerts", () =>
    HttpResponse.json({ success: true, data: [], meta: { total: 0, current_page: 1, last_page: 1, per_page: 25 } }),
  ),
];
