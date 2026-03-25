import "@testing-library/jest-dom/vitest";
import { server } from "./mocks/server";

beforeAll(() => server.listen({ onUnhandledRequest: "warn" }));

afterEach(() => {
  server.resetHandlers();
  localStorage.clear();
  sessionStorage.clear();
});

afterAll(() => server.close());
