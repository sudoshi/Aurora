import "@testing-library/jest-dom/vitest";
import { expect } from "vitest";
import { toHaveNoViolations } from "./a11y";
import { server } from "./mocks/server";

expect.extend({ toHaveNoViolations });

beforeAll(() => server.listen({ onUnhandledRequest: "warn" }));

afterEach(() => {
  server.resetHandlers();
  localStorage.clear();
  sessionStorage.clear();
});

afterAll(() => server.close());
