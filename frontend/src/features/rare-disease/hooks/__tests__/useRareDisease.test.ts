import { describe, it, expect, afterEach } from "vitest";
import { waitFor, act } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderHookWithProviders, resetStores } from "@/test/utils";
import { useOdyssey, useHpoSearch, useTransitionOdyssey } from "../useRareDisease";

afterEach(() => resetStores());

describe("useOdyssey", () => {
  it("fetches an odyssey detail with allowed_transitions", async () => {
    server.use(
      http.get("/api/odysseys/5", () =>
        HttpResponse.json({
          success: true,
          data: {
            odyssey: { id: 5, title: "Undiagnosed", status: "referral", progress_status: "in_progress" },
            allowed_transitions: ["phenotyping"],
          },
        }),
      ),
    );

    const { result } = renderHookWithProviders(() => useOdyssey(5));
    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data!.odyssey.id).toBe(5);
    expect(result.current.data!.allowed_transitions).toEqual(["phenotyping"]);
  });
});

describe("useHpoSearch", () => {
  it("does not fire when the query is shorter than 2 chars", () => {
    const { result } = renderHookWithProviders(() => useHpoSearch("a"));
    expect(result.current.fetchStatus).toBe("idle");
  });

  it("returns HPO terms for a valid query", async () => {
    server.use(
      http.get("/api/hpo/search", () =>
        HttpResponse.json({ success: true, data: [{ id: "HP:0001250", label: "Seizure", definition: null, synonyms: [] }] }),
      ),
    );
    const { result } = renderHookWithProviders(() => useHpoSearch("seizure"));
    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data![0].id).toBe("HP:0001250");
  });
});

describe("useTransitionOdyssey", () => {
  it("posts a transition", async () => {
    server.use(
      http.post("/api/odysseys/5/transition", () =>
        HttpResponse.json({ success: true, data: { id: 5, status: "phenotyping", progress_status: "in_progress" } }),
      ),
    );
    const { result } = renderHookWithProviders(() => useTransitionOdyssey(5));
    await act(async () => { result.current.mutate({ to_status: "phenotyping" }); });
    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data!.status).toBe("phenotyping");
  });
});
