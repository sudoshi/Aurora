import { renderHook, act } from "@testing-library/react";
import { useProfileStore } from "@/stores/profileStore";
import { resetStores } from "@/test/utils";

afterEach(() => {
  resetStores();
});

describe("profileStore", () => {
  it("has empty recentProfiles initially", () => {
    const { result } = renderHook(() => useProfileStore());

    expect(result.current.recentProfiles).toEqual([]);
  });

  it("addRecentProfile adds a profile with viewedAt timestamp", () => {
    const { result } = renderHook(() => useProfileStore());

    act(() => {
      result.current.addRecentProfile({
        patientId: 1,
        name: "John Doe",
        mrn: "MRN001",
      });
    });

    expect(result.current.recentProfiles).toHaveLength(1);
    expect(result.current.recentProfiles[0].patientId).toBe(1);
    expect(result.current.recentProfiles[0].name).toBe("John Doe");
    expect(typeof result.current.recentProfiles[0].viewedAt).toBe("number");
  });

  it("addRecentProfile deduplicates by patientId", () => {
    const { result } = renderHook(() => useProfileStore());

    act(() => {
      result.current.addRecentProfile({
        patientId: 1,
        name: "John Doe",
        mrn: "MRN001",
      });
    });

    act(() => {
      result.current.addRecentProfile({
        patientId: 1,
        name: "John Doe Updated",
        mrn: "MRN001",
      });
    });

    expect(result.current.recentProfiles).toHaveLength(1);
    expect(result.current.recentProfiles[0].name).toBe("John Doe Updated");
  });

  it("addRecentProfile puts newest first", () => {
    const { result } = renderHook(() => useProfileStore());

    act(() => {
      result.current.addRecentProfile({
        patientId: 1,
        name: "First",
        mrn: "MRN001",
      });
    });

    act(() => {
      result.current.addRecentProfile({
        patientId: 2,
        name: "Second",
        mrn: "MRN002",
      });
    });

    expect(result.current.recentProfiles[0].patientId).toBe(2);
    expect(result.current.recentProfiles[1].patientId).toBe(1);
  });

  it("addRecentProfile caps at 15 entries", () => {
    const { result } = renderHook(() => useProfileStore());

    act(() => {
      for (let i = 1; i <= 16; i++) {
        result.current.addRecentProfile({
          patientId: i,
          name: `Patient ${i}`,
          mrn: `MRN${String(i).padStart(3, "0")}`,
        });
      }
    });

    expect(result.current.recentProfiles).toHaveLength(15);
    // Newest (16) should be first, oldest (1) should be dropped
    expect(result.current.recentProfiles[0].patientId).toBe(16);
    const ids = result.current.recentProfiles.map((p) => p.patientId);
    expect(ids).not.toContain(1);
  });

  it("clearRecentProfiles empties the list", () => {
    const { result } = renderHook(() => useProfileStore());

    act(() => {
      result.current.addRecentProfile({
        patientId: 1,
        name: "John Doe",
        mrn: "MRN001",
      });
    });
    expect(result.current.recentProfiles).toHaveLength(1);

    act(() => {
      result.current.clearRecentProfiles();
    });

    expect(result.current.recentProfiles).toEqual([]);
  });
});
