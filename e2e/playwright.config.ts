import { defineConfig } from "@playwright/test";
import path from "path";

const authFile = path.join(__dirname, ".auth", "admin.json");

export default defineConfig({
  testDir: "./tests",
  fullyParallel: false,
  retries: 1,
  workers: 1,
  timeout: 30_000,
  expect: {
    timeout: 10_000,
  },
  use: {
    baseURL: process.env.BASE_URL || "https://aurora.acumenus.net",
    screenshot: "only-on-failure",
    trace: "on-first-retry",
    actionTimeout: 10_000,
  },
  projects: [
    {
      name: "setup",
      testMatch: /auth\.setup\.ts/,
    },
    {
      name: "auth-tests",
      testMatch: /auth\.spec\.ts/,
      use: { browserName: "chromium" },
      dependencies: ["setup"],
    },
    {
      name: "chromium",
      testIgnore: /auth\.(spec|setup)\.ts/,
      use: {
        browserName: "chromium",
        storageState: authFile,
      },
      dependencies: ["setup"],
    },
  ],
  reporter: [["html", { open: "never" }], ["list"]],
});
