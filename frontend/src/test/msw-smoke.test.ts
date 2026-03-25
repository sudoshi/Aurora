import { http, HttpResponse } from "msw";
import { server } from "./mocks/server";

describe("MSW smoke tests", () => {
  it("intercepts fetch with inline handler via server.use()", async () => {
    server.use(
      http.get("/api/test", () => {
        return HttpResponse.json({ message: "mocked" });
      }),
    );

    const response = await fetch("/api/test");
    const data = await response.json();

    expect(response.ok).toBe(true);
    expect(data).toEqual({ message: "mocked" });
  });

  it("returns expected structure from default /api/dashboard handler", async () => {
    const response = await fetch("/api/dashboard");
    const data = await response.json();

    expect(response.ok).toBe(true);
    expect(data).toEqual({
      success: true,
      data: { patient_count: 0 },
    });
  });
});
