describe("Vitest smoke tests", () => {
  it("performs basic arithmetic", () => {
    expect(1 + 1).toBe(2);
  });

  it("has jsdom environment with DOM APIs", () => {
    const div = document.createElement("div");
    expect(div).toBeDefined();
    expect(div.tagName).toBe("DIV");
  });

  it("supports jest-dom matchers", () => {
    const div = document.createElement("div");
    document.body.appendChild(div);
    expect(div).toBeInTheDocument();
    document.body.removeChild(div);
  });
});
