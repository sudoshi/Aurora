import { useState } from "react";
import {
  Plus, ChevronLeft, ChevronRight, Pencil, Trash2, Circle,
  Search, X, Loader2, UsersRound, ChevronUp, ChevronDown,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { useUsers, useDeleteUser, useAvailableRoles } from "../hooks/useAdminUsers";
import { UserModal } from "../components/UserModal";
import type { User, UserFilters } from "../api/adminApi";

// ── Design tokens ────────────────────────────────────────────────────────────
const ROLE_COLORS: Record<string, string> = {
  "super-admin":      "#00D68F",
  "admin":            "#2DD4BF",
  "researcher":       "#60A5FA",
  "data-steward":     "#A78BFA",
  "clinical-reviewer": "#F59E0B",
  "case-manager":     "#10B981",
  "viewer":           "#7A8298",
};

// ── Sub-components ───────────────────────────────────────────────────────────

function RoleBadge({ role }: { role: string }) {
  const color = ROLE_COLORS[role] ?? "#7A8298";
  return (
    <span
      className="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium"
      style={{ backgroundColor: `${color}15`, color }}
    >
      {role}
    </span>
  );
}

function SortIcon({ active, dir }: { active: boolean; dir: "asc" | "desc" }) {
  if (!active) return <ChevronUp size={12} className="text-[#2A2A60]" />;
  return dir === "asc"
    ? <ChevronUp size={12} className="text-[#2DD4BF]" />
    : <ChevronDown size={12} className="text-[#2DD4BF]" />;
}

function EmptyState({ loading }: { loading: boolean }) {
  return (
    <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-[#2A2A60] bg-[#10102A] py-16">
      <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#16163A]">
        <UsersRound size={24} className="text-[#7A8298]" />
      </div>
      <h3 className="text-lg font-semibold text-[#E8ECF4]">
        {loading ? "Loading..." : "No users found"}
      </h3>
      {!loading && (
        <p className="mt-2 text-sm text-[#7A8298]">Try adjusting your search or filters.</p>
      )}
    </div>
  );
}

// ── Delete confirmation overlay ───────────────────────────────────────────────

function DeleteConfirmModal({
  user,
  isPending,
  onConfirm,
  onCancel,
}: {
  user: User;
  isPending: boolean;
  onConfirm: () => void;
  onCancel: () => void;
}) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      {/* Backdrop */}
      <div
        className="absolute inset-0 bg-black/60 backdrop-blur-sm"
        onClick={onCancel}
      />
      {/* Panel */}
      <div className="relative z-10 w-full max-w-sm rounded-xl border border-[#1C1C48] bg-[#16163A] shadow-xl">
        {/* Header */}
        <div className="flex items-center justify-between border-b border-[#1C1C48] px-5 py-4">
          <h2 className="text-base font-semibold text-[#E8ECF4]">Delete user?</h2>
          <button
            type="button"
            onClick={onCancel}
            className="flex h-7 w-7 items-center justify-center rounded-md text-[#4A5068] transition-colors hover:bg-[#222256] hover:text-[#7A8298]"
          >
            <X size={16} />
          </button>
        </div>
        {/* Body */}
        <div className="px-5 py-4">
          <p className="text-sm text-[#7A8298]">
            <span className="font-semibold text-[#B4BAC8]">{user.name}</span>{" "}
            <span className="font-['IBM_Plex_Mono',monospace] text-xs text-[#4A5068]">
              ({user.email})
            </span>{" "}
            will be permanently deleted and all their API tokens revoked.{" "}
            <span className="text-[#F0607A]">This cannot be undone.</span>
          </p>
        </div>
        {/* Footer */}
        <div className="flex justify-end gap-3 border-t border-[#1C1C48] px-5 py-4">
          <button
            type="button"
            onClick={onCancel}
            className="rounded-lg border border-[#222256] bg-[#10102A] px-4 py-2 text-sm text-[#7A8298] transition-colors hover:border-[#2A2A60] hover:text-[#B4BAC8]"
          >
            Cancel
          </button>
          <button
            type="button"
            disabled={isPending}
            onClick={onConfirm}
            className="rounded-lg bg-[#00D68F] px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-[#B52238] disabled:opacity-50"
          >
            {isPending ? "Deleting..." : "Delete"}
          </button>
        </div>
      </div>
    </div>
  );
}

// ── Main page ────────────────────────────────────────────────────────────────

const SORTABLE: Array<{ key: string; label: string }> = [
  { key: "name", label: "Name" },
  { key: "email", label: "Email" },
  { key: "last_active_at", label: "Last Active" },
  { key: "created_at", label: "Joined" },
];

export default function UsersPage() {
  const [filters, setFilters] = useState<UserFilters>({
    page: 1, per_page: 20, sort_by: "created_at", sort_dir: "desc",
  });
  const [search, setSearch] = useState("");
  const [modalState, setModalState] = useState<{ open: boolean; user: User | null }>({
    open: false, user: null,
  });
  const [deleteConfirm, setDeleteConfirm] = useState<User | null>(null);

  const { data, isLoading } = useUsers({ ...filters, search: search || undefined });
  const { data: roles } = useAvailableRoles();
  const deleteUser = useDeleteUser();

  const handleSort = (key: string) =>
    setFilters((f) => ({
      ...f,
      sort_by: key,
      sort_dir: f.sort_by === key && f.sort_dir === "asc" ? "desc" : "asc",
    }));

  const users = data?.data ?? [];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-[#E8ECF4]">Users</h1>
          <p className="mt-1 text-sm text-[#7A8298]">
            <span className="font-['IBM_Plex_Mono',monospace] text-[#B4BAC8]">
              {data?.total ?? 0}
            </span>{" "}
            total accounts
          </p>
        </div>
        <button
          type="button"
          onClick={() => setModalState({ open: true, user: null })}
          className="inline-flex items-center gap-2 rounded-lg bg-[#2DD4BF] px-4 py-2 text-sm font-semibold text-[#0A0A18] transition-colors hover:bg-[#25B8A5]"
        >
          <Plus size={16} />
          New User
        </button>
      </div>

      {/* Filters */}
      <div className="flex flex-wrap items-center gap-3">
        {/* Search */}
        <div className="relative max-w-xs flex-1">
          <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-[#4A5068]" />
          <input
            type="text"
            value={search}
            onChange={(e) => { setSearch(e.target.value); setFilters((f) => ({ ...f, page: 1 })); }}
            placeholder="Search name or email..."
            className="w-full rounded-lg border border-[#1C1C48] bg-[#10102A] py-2 pl-9 pr-8 text-sm text-[#E8ECF4] placeholder:text-[#4A5068] focus:border-[#2DD4BF] focus:outline-none focus:ring-1 focus:ring-[#2DD4BF]/40 transition-colors"
          />
          {search && (
            <button
              type="button"
              onClick={() => setSearch("")}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-[#4A5068] hover:text-[#7A8298]"
            >
              <X size={12} />
            </button>
          )}
        </div>

        {/* Role filter */}
        <select
          value={filters.role ?? ""}
          onChange={(e) => setFilters((f) => ({ ...f, role: e.target.value || undefined, page: 1 }))}
          className="rounded-lg border border-[#1C1C48] bg-[#10102A] px-3 py-2 text-sm text-[#B4BAC8] focus:border-[#2DD4BF] focus:outline-none transition-colors"
        >
          <option value="">All roles</option>
          {roles?.map((r) => (
            <option key={r.id} value={r.name}>{r.name}</option>
          ))}
        </select>
      </div>

      {/* Table */}
      {isLoading || users.length === 0 ? (
        <EmptyState loading={isLoading} />
      ) : (
        <div className="overflow-hidden rounded-lg border border-[#1C1C48] bg-[#10102A]">
          <table className="w-full">
            <thead>
              <tr className="bg-[#16163A]">
                {SORTABLE.map(({ key, label }) => (
                  <th
                    key={key}
                    className="px-4 py-2.5 text-left"
                  >
                    <button
                      type="button"
                      onClick={() => handleSort(key)}
                      className="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-[#7A8298] transition-colors hover:text-[#B4BAC8]"
                    >
                      {label}
                      <SortIcon
                        active={filters.sort_by === key}
                        dir={filters.sort_dir as "asc" | "desc"}
                      />
                    </button>
                  </th>
                ))}
                <th className="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-[#7A8298]">
                  Roles
                </th>
                <th className="w-20 px-4 py-2.5" />
              </tr>
            </thead>
            <tbody>
              {users.map((user, i) => {
                const activeAt = user.last_active_at;
                const isActive = user.is_active;
                return (
                  <tr
                    key={user.id}
                    className={cn(
                      "border-t border-[#16163A] transition-colors",
                      i % 2 === 0 ? "bg-[#10102A]" : "bg-[#16163A]",
                    )}
                  >
                    {/* Name */}
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-2.5">
                        <div
                          className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold uppercase"
                          style={{ backgroundColor: "#2DD4BF15", color: "#2DD4BF" }}
                        >
                          {user.name.charAt(0)}
                        </div>
                        <span className="text-sm font-medium text-[#B4BAC8]">{user.name}</span>
                      </div>
                    </td>

                    {/* Email */}
                    <td className="px-4 py-3">
                      <span className="font-['IBM_Plex_Mono',monospace] text-xs text-[#7A8298]">
                        {user.email}
                      </span>
                    </td>

                    {/* Last Active */}
                    <td className="px-4 py-3">
                      <span className="inline-flex items-center gap-1.5">
                        <Circle
                          size={7}
                          style={{
                            fill: isActive ? "#2DD4BF" : "#2A2A60",
                            color: isActive ? "#2DD4BF" : "#2A2A60",
                            flexShrink: 0,
                          }}
                        />
                        <span className="font-['IBM_Plex_Mono',monospace] text-xs text-[#4A5068]">
                          {activeAt ? new Date(activeAt).toLocaleString("en-US", {
                            month: "short", day: "numeric",
                            hour: "2-digit", minute: "2-digit",
                          }) : "Never"}
                        </span>
                      </span>
                    </td>

                    {/* Joined */}
                    <td className="px-4 py-3">
                      <span className="font-['IBM_Plex_Mono',monospace] text-xs text-[#4A5068]">
                        {new Date(user.created_at).toLocaleDateString("en-US", {
                          month: "short", day: "numeric", year: "numeric",
                        })}
                      </span>
                    </td>

                    {/* Roles */}
                    <td className="px-4 py-3">
                      <div className="flex flex-wrap gap-1">
                        {user.roles?.length ? (
                          (user.roles as Array<string | { name: string }>).map((r) => {
                            const name = typeof r === "string" ? r : r.name;
                            return <RoleBadge key={name} role={name} />;
                          })
                        ) : (
                          <span className="text-xs text-[#4A5068]">--</span>
                        )}
                      </div>
                    </td>

                    {/* Actions */}
                    <td className="px-4 py-3">
                      <div className="flex items-center justify-end gap-1">
                        <button
                          type="button"
                          onClick={(e) => { e.stopPropagation(); setModalState({ open: true, user }); }}
                          title="Edit user"
                          className="flex h-7 w-7 items-center justify-center rounded-md text-[#4A5068] transition-colors hover:bg-[#1C1C48] hover:text-[#7A8298]"
                        >
                          <Pencil size={14} />
                        </button>
                        <button
                          type="button"
                          onClick={(e) => { e.stopPropagation(); setDeleteConfirm(user); }}
                          title="Delete user"
                          className="flex h-7 w-7 items-center justify-center rounded-md text-[#4A5068] transition-colors hover:bg-[#00D68F15] hover:text-[#F0607A]"
                        >
                          <Trash2 size={14} />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}

      {/* Pagination */}
      {data && data.last_page > 1 && (
        <div className="flex items-center justify-between text-sm text-[#4A5068]">
          <span>
            Page{" "}
            <span className="font-['IBM_Plex_Mono',monospace] text-[#7A8298]">
              {data.current_page}
            </span>{" "}
            of{" "}
            <span className="font-['IBM_Plex_Mono',monospace] text-[#7A8298]">
              {data.last_page}
            </span>
            {" "}&middot;{" "}
            <span className="font-['IBM_Plex_Mono',monospace] text-[#B4BAC8]">
              {(data.total ?? 0).toLocaleString()}
            </span>{" "}
            users
          </span>
          <div className="flex items-center gap-2">
            <button
              type="button"
              disabled={data.current_page === 1}
              onClick={() => setFilters((f) => ({ ...f, page: (f.page ?? 1) - 1 }))}
              className="inline-flex items-center justify-center rounded-lg border border-[#222256] bg-[#10102A] p-1.5 text-[#7A8298] transition-colors hover:border-[#2A2A60] hover:text-[#B4BAC8] disabled:opacity-40 disabled:cursor-not-allowed"
            >
              <ChevronLeft size={16} />
            </button>
            <button
              type="button"
              disabled={data.current_page === data.last_page}
              onClick={() => setFilters((f) => ({ ...f, page: (f.page ?? 1) + 1 }))}
              className="inline-flex items-center justify-center rounded-lg border border-[#222256] bg-[#10102A] p-1.5 text-[#7A8298] transition-colors hover:border-[#2A2A60] hover:text-[#B4BAC8] disabled:opacity-40 disabled:cursor-not-allowed"
            >
              <ChevronRight size={16} />
            </button>
          </div>
        </div>
      )}

      {/* Create / Edit modal */}
      {modalState.open && (
        <UserModal
          user={modalState.user}
          roles={roles ?? []}
          onClose={() => setModalState({ open: false, user: null })}
        />
      )}

      {/* Delete confirmation */}
      {deleteConfirm && (
        <DeleteConfirmModal
          user={deleteConfirm}
          isPending={deleteUser.isPending}
          onConfirm={() =>
            deleteUser.mutate(deleteConfirm.id, { onSuccess: () => setDeleteConfirm(null) })
          }
          onCancel={() => setDeleteConfirm(null)}
        />
      )}

      {/* Loading overlay */}
      {isLoading && users.length > 0 && (
        <div className="flex justify-center">
          <Loader2 size={18} className="animate-spin text-[#4A5068]" />
        </div>
      )}
    </div>
  );
}
