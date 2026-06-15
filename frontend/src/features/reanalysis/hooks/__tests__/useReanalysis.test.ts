import { describe, it, expect, afterEach } from "vitest";
import { waitFor } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderHookWithProviders, resetStores } from "@/test/utils";
import { usePatientKbAlerts } from "../useReanalysis";

afterEach(() => resetStores());

describe("usePatientKbAlerts", () => {
  it("fetches KB-change alerts for a patient", async () => {
    server.use(
      http.get("/api/patients/5/kb-alerts", () =>
        HttpResponse.json({ success: true, data: [{ id: 1, patient_id: 5, severity: "high", from_bucket: "vus", to_bucket: "pathogenic", status: "new" }] }),
      ),
    );
    const { result } = renderHookWithProviders(() => usePatientKbAlerts(5));
    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data![0].severity).toBe("high");
  });
});
