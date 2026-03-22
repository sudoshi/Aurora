import { User, Calendar, Heart, Globe } from "lucide-react";
import type { ClinicalPatient } from "../types/profile";

interface PatientDemographicsCardProps {
  patient: ClinicalPatient;
}

function computeAge(dob: string | null, deceasedAt: string | null): number | null {
  if (!dob) return null;
  const endDate = deceasedAt ? new Date(deceasedAt) : new Date();
  const birth = new Date(dob);
  let age = endDate.getFullYear() - birth.getFullYear();
  const monthDiff = endDate.getMonth() - birth.getMonth();
  if (monthDiff < 0 || (monthDiff === 0 && endDate.getDate() < birth.getDate())) {
    age--;
  }
  return age;
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

interface FieldProps {
  icon: React.ReactNode;
  label: string;
  value: React.ReactNode;
}

function Field({ icon, label, value }: FieldProps) {
  return (
    <div className="space-y-1">
      <div className="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-[var(--text-ghost)]">
        {icon}
        {label}
      </div>
      <div className="text-sm font-medium text-[var(--text-primary)]">{value}</div>
    </div>
  );
}

export function PatientDemographicsCard({ patient }: PatientDemographicsCardProps) {
  const age = computeAge(patient.date_of_birth, patient.deceased_at);
  const fullName = `${patient.first_name} ${patient.last_name}`.trim();

  return (
    <div className="rounded-lg border border-[var(--border-default)] bg-[var(--surface-raised)] p-5">
      {/* Header */}
      <div className="flex items-center gap-3 mb-5">
        <div className="flex items-center justify-center w-10 h-10 rounded-full bg-[var(--accent-pale)]">
          <User size={18} className="text-[var(--accent)]" />
        </div>
        <div className="flex-1">
          <div className="flex items-center gap-3">
            <h2 className="text-lg font-bold text-[var(--text-primary)]">
              {fullName || `Patient #${patient.id}`}
            </h2>
            {patient.deceased_at && (
              <span className="inline-flex items-center gap-1 rounded-full bg-[var(--critical-bg)] px-2.5 py-0.5 text-[10px] font-semibold text-[var(--critical)] border border-[var(--critical-border)]">
                Deceased
              </span>
            )}
          </div>
          <p className="text-xs text-[var(--text-muted)]">
            MRN: {patient.mrn} &middot; Patient Demographics
          </p>
        </div>
      </div>

      {/* Core demographics */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-5 pb-5 border-b border-[var(--border-default)]">
        <Field
          icon={<Heart size={10} />}
          label="Sex"
          value={patient.sex || "Unknown"}
        />
        <Field
          icon={<Calendar size={10} />}
          label="Age / DOB"
          value={
            age != null
              ? patient.deceased_at
                ? `${age} yrs at death${patient.date_of_birth ? ` (${patient.date_of_birth})` : ""}`
                : `${age} yrs${patient.date_of_birth ? ` (${patient.date_of_birth})` : ""}`
              : patient.date_of_birth ?? "Unknown"
          }
        />
        <Field
          icon={<Globe size={10} />}
          label="Race"
          value={patient.race || "Unknown"}
        />
        <Field
          icon={<Globe size={10} />}
          label="Ethnicity"
          value={patient.ethnicity || "Unknown"}
        />
        {patient.deceased_at && (
          <Field
            icon={<Calendar size={10} />}
            label="Date of Death"
            value={
              <span className="text-[var(--critical)]">
                {formatDate(patient.deceased_at)}
              </span>
            }
          />
        )}
      </div>
    </div>
  );
}
