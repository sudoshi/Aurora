import { useEffect, useSyncExternalStore } from "react";
import { useAuthStore } from "@/stores/authStore";
import {
  initEcho,
  teardownEcho,
  getRealtimeStatus,
  subscribeRealtimeStatus,
  type RealtimeStatus,
} from "./echo";

/**
 * App-shell hook: brings the Echo connection up while authenticated and tears
 * it down on logout. Mount once in the authenticated layout.
 */
export function useRealtimeConnection(): RealtimeStatus {
  const token = useAuthStore((s) => s.token);

  useEffect(() => {
    if (token) {
      initEcho();
    } else {
      teardownEcho();
    }
  }, [token]);

  return useRealtimeStatus();
}

/** Subscribe to the live realtime connection status. */
export function useRealtimeStatus(): RealtimeStatus {
  return useSyncExternalStore(subscribeRealtimeStatus, getRealtimeStatus);
}

/** True when realtime is configured but not currently delivering events. */
export function useRealtimeDegraded(): boolean {
  const status = useRealtimeStatus();
  return status === "disconnected" || status === "unavailable";
}
