import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { render, renderHook } from "@testing-library/react";
import { MemoryRouter } from "react-router-dom";
import type { ReactElement, ReactNode } from "react";
import { useAuthStore } from "@/stores/authStore";
import { useProfileStore } from "@/stores/profileStore";
import { useUiStore } from "@/stores/uiStore";
import { useAbbyStore } from "@/stores/abbyStore";

function createTestQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
        gcTime: 0,
      },
      mutations: {
        retry: false,
      },
    },
  });
}

interface WrapperOptions {
  initialRoute?: string;
}

export function createWrapper(options?: WrapperOptions) {
  const queryClient = createTestQueryClient();
  const initialEntries = options?.initialRoute ? [options.initialRoute] : ["/"];

  return function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={initialEntries}>{children}</MemoryRouter>
      </QueryClientProvider>
    );
  };
}

export function renderWithProviders(
  ui: ReactElement,
  options?: WrapperOptions,
) {
  const wrapper = createWrapper(options);
  return render(ui, { wrapper });
}

export function renderHookWithProviders<TResult>(
  hook: () => TResult,
  options?: WrapperOptions,
) {
  const wrapper = createWrapper(options);
  return renderHook(hook, { wrapper });
}

export function resetStores(): void {
  useAuthStore.setState({ token: null, user: null, isAuthenticated: false });
  useProfileStore.setState({ recentProfiles: [] });
  useUiStore.setState({ commandPaletteOpen: false });
  useAbbyStore.setState({
    panelOpen: false,
    messages: [],
    conversationId: null,
    conversationList: [],
    pageContext: "general",
    isStreaming: false,
    streamingContent: "",
  });
}
