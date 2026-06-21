import { describe, it, expect } from "vitest";
import { render } from "@testing-library/react";
import { axe } from "@/test/a11y";
import { Button } from "../Button";
import { Modal } from "../Modal";
import { DataTable, type Column } from "../DataTable";
import { ResearchUseOnlyNotice } from "../ResearchUseOnlyNotice";

describe("a11y: reusable UI components", () => {
  it("Button has no detectable a11y violations", async () => {
    const { container } = render(<Button variant="primary">Save</Button>);
    expect(await axe(container)).toHaveNoViolations();
  });

  it("Button (icon-only) has an accessible name", async () => {
    const { container } = render(
      <Button icon aria-label="Refresh">
        <svg aria-hidden="true" />
      </Button>,
    );
    expect(await axe(container)).toHaveNoViolations();
  });

  it("Modal (with title) has no detectable a11y violations", async () => {
    const { baseElement } = render(
      <Modal open title="Edit case" onClose={() => {}}>
        <p>Modal body content</p>
      </Modal>,
    );
    // Modal renders into a portal on document.body, so assert on baseElement.
    expect(await axe(baseElement)).toHaveNoViolations();
  });

  it("DataTable has no detectable a11y violations", async () => {
    interface Row {
      id: number;
      name: string;
      mrn: string;
    }
    const columns: Column<Row>[] = [
      { key: "name", header: "Name", sortable: true },
      { key: "mrn", header: "MRN", mono: true },
    ];
    const data: Row[] = [
      { id: 1, name: "Ada Lovelace", mrn: "MRN-001" },
      { id: 2, name: "Alan Turing", mrn: "MRN-002" },
    ];
    const { container } = render(
      <DataTable
        columns={columns}
        data={data}
        rowKey={(row) => row.id}
        sortKey="name"
        sortDir="asc"
        onSort={() => {}}
      />,
    );
    expect(await axe(container)).toHaveNoViolations();
  });

  it("DataTable (empty state) has no detectable a11y violations", async () => {
    const columns: Column<{ id: number }>[] = [
      { key: "id", header: "ID" },
    ];
    const { container } = render(
      <DataTable columns={columns} data={[]} rowKey={(row) => row.id} />,
    );
    expect(await axe(container)).toHaveNoViolations();
  });

  it("ResearchUseOnlyNotice (footer) has no detectable a11y violations", async () => {
    const { container } = render(<ResearchUseOnlyNotice />);
    expect(await axe(container)).toHaveNoViolations();
  });

  it("ResearchUseOnlyNotice (chip) has no detectable a11y violations", async () => {
    const { container } = render(<ResearchUseOnlyNotice variant="chip" />);
    expect(await axe(container)).toHaveNoViolations();
  });
});
