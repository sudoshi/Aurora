import { configureAxe } from "vitest-axe";
import type { AxeResults, Result } from "axe-core";

interface MatcherResult {
  message(): string;
  pass: boolean;
}

/**
 * Local `toHaveNoViolations` matcher.
 *
 * vitest-axe ships the matcher as a runtime export, but its bundled `.d.ts`
 * re-exports it as type-only under vitest 4, so it cannot be imported as a
 * value in strict mode. This small wrapper formats axe violations into a
 * readable failure message and is wired in via `expect.extend` in setup.ts.
 */
export function toHaveNoViolations(results: AxeResults): MatcherResult {
  if (typeof results?.violations === "undefined") {
    throw new Error("No violations found in aXe results object");
  }

  const violations = results.violations;
  const pass = violations.length === 0;

  const format = (violation: Result): string => {
    const nodes = violation.nodes
      .map((node) => `      ${node.html}\n      ${node.failureSummary ?? ""}`)
      .join("\n");
    return `  [${violation.impact ?? "unknown"}] ${violation.id}: ${violation.help}\n    ${violation.helpUrl}\n${nodes}`;
  };

  const message = (): string =>
    pass
      ? "Expected accessibility violations but found none."
      : `Expected no accessibility violations but found ${violations.length}:\n\n${violations
          .map(format)
          .join("\n\n")}`;

  return { pass, message };
}

declare module "vitest" {
  interface Assertion {
    toHaveNoViolations(): void;
  }
  interface AsymmetricMatchersContaining {
    toHaveNoViolations(): void;
  }
}

/**
 * Shared axe runner for Aurora a11y tests.
 *
 * jsdom has no layout engine, so axe cannot evaluate the `color-contrast` rule
 * (it needs computed geometry/painting). We disable it here to avoid false
 * negatives; contrast must be verified in a live browser / manual pass (W12
 * follow-up). All other rules — ARIA, labels, roles, accessible names, alt
 * text — run normally and catch real DOM-level violations.
 */
export const axe = configureAxe({
  rules: {
    "color-contrast": { enabled: false },
  },
});
