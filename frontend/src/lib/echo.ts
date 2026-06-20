import Echo from "laravel-echo";
import Pusher from "pusher-js";
import { useAuthStore } from "@/stores/authStore";

/**
 * Laravel Echo (Reverb / Pusher protocol) client.
 *
 * `getEcho()` returns the live instance or null; every consumer hook already
 * guards on null, so the app degrades gracefully (see the polling fallback in
 * useMessages) when realtime is unconfigured or the socket is down.
 */

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type EchoInstance = Echo<any>;

export type RealtimeStatus =
  | "disabled" // VITE_REVERB_APP_KEY not set — realtime off, polling only
  | "connecting"
  | "connected"
  | "disconnected"
  | "unavailable";

let echoInstance: EchoInstance | null = null;
let status: RealtimeStatus = "disabled";
const listeners = new Set<(s: RealtimeStatus) => void>();

function setStatus(next: RealtimeStatus): void {
  status = next;
  listeners.forEach((cb) => cb(next));
}

export function getEcho(): EchoInstance | null {
  return echoInstance;
}

export function setEcho(instance: EchoInstance | null): void {
  echoInstance = instance;
}

export function getRealtimeStatus(): RealtimeStatus {
  return status;
}

export function subscribeRealtimeStatus(
  cb: (s: RealtimeStatus) => void,
): () => void {
  listeners.add(cb);
  cb(status);
  return () => {
    listeners.delete(cb);
  };
}

/**
 * Initialize the Echo client once. Idempotent: returns the existing instance on
 * repeat calls. Returns null when realtime is not configured for this build.
 */
export function initEcho(): EchoInstance | null {
  if (echoInstance) return echoInstance;

  const key = import.meta.env.VITE_REVERB_APP_KEY as string | undefined;
  if (!key) {
    setStatus("disabled");
    return null;
  }

  const scheme =
    (import.meta.env.VITE_REVERB_SCHEME as string | undefined) ?? "https";
  const forceTLS = scheme === "https";
  const port = Number(import.meta.env.VITE_REVERB_PORT ?? (forceTLS ? 443 : 80));

  // laravel-echo's reverb broadcaster needs Pusher on the global scope.
  (window as unknown as { Pusher: typeof Pusher }).Pusher = Pusher;

  setStatus("connecting");

  echoInstance = new Echo({
    broadcaster: "reverb",
    key,
    wsHost: import.meta.env.VITE_REVERB_HOST as string | undefined,
    wsPort: port,
    wssPort: port,
    forceTLS,
    enabledTransports: ["ws", "wss"],
    // Authorize the private/presence handshake with the SPA bearer token,
    // read fresh each request so re-login is picked up without re-init.
    authorizer: (channel: { name: string }) => ({
      authorize: (
        socketId: string,
        callback: (
          error: Error | null,
          data: { auth: string; channel_data?: string } | null,
        ) => void,
      ) => {
        fetch("/broadcasting/auth", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            Authorization: `Bearer ${useAuthStore.getState().token ?? ""}`,
          },
          body: JSON.stringify({
            socket_id: socketId,
            channel_name: channel.name,
          }),
        })
          .then((res) => {
            if (!res.ok) throw new Error(`auth ${res.status}`);
            return res.json();
          })
          .then((data) => callback(null, data))
          .catch((err) =>
            callback(err instanceof Error ? err : new Error(String(err)), null),
          );
      },
    }),
  });

  // Mirror the underlying pusher-js connection state for the UI / fallback.
  const connection = (
    echoInstance.connector as unknown as {
      pusher?: { connection?: { bind: (e: string, cb: () => void) => void } };
    }
  )?.pusher?.connection;

  if (connection) {
    connection.bind("connected", () => setStatus("connected"));
    connection.bind("connecting", () => setStatus("connecting"));
    connection.bind("disconnected", () => setStatus("disconnected"));
    connection.bind("unavailable", () => setStatus("unavailable"));
    connection.bind("failed", () => setStatus("unavailable"));
  }

  return echoInstance;
}

/** Tear the connection down (called on logout). */
export function teardownEcho(): void {
  if (echoInstance) {
    echoInstance.disconnect();
    echoInstance = null;
  }
  setStatus("disabled");
}
