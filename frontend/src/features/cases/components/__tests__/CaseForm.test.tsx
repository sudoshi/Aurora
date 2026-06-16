import { describe, it, expect, vi } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { CaseForm } from "../CaseForm";

function renderForm() {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  const onSubmit = vi.fn();
  render(
    <QueryClientProvider client={qc}>
      <CaseForm onSubmit={onSubmit} onClose={() => {}} />
    </QueryClientProvider>,
  );
  return { onSubmit };
}

describe("CaseForm template-driven fields", () => {
  it("renders the selected template's structured-data fields", async () => {
    renderForm();
    const tplSelect = await screen.findByLabelText(/board template/i);
    fireEvent.change(tplSelect, { target: { value: "oncology-tumor-board" } });
    expect(await screen.findByLabelText(/primary site/i)).toBeInTheDocument();
  });

  it("submits structured_data and template_id", async () => {
    const { onSubmit } = renderForm();
    fireEvent.change(await screen.findByLabelText(/title/i), { target: { value: "Case A" } });
    fireEvent.change(await screen.findByLabelText(/board template/i), { target: { value: "oncology-tumor-board" } });
    fireEvent.change(await screen.findByLabelText(/primary site/i), { target: { value: "lung" } });
    fireEvent.click(screen.getByRole("button", { name: /create|save/i }));
    await waitFor(() =>
      expect(onSubmit).toHaveBeenCalledWith(
        expect.objectContaining({
          template_id: 1,
          structured_data: expect.objectContaining({ primary_site: "lung" }),
        }),
      ),
    );
  });
});
