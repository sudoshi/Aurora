import {
  User,
  Calendar,
  Heart,
  Globe,
} from "lucide-react";
import type { ClinicalPatient, PatientProfile, PatientStats } from "../types/profile";

interface PatientDemographicsCardProps {
  patient: ClinicalPatient;
  profile?: PatientProfile;
  stats?: PatientStats;
  onDrillDown?: (view: string, domain?: string) => void;
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

function formatDob(iso: string): string {
  return new Date(iso).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

export function PatientDemographicsCard({ patient }: PatientDemographicsCardProps) {
  const age = computeAge(patient.date_of_birth, patient.deceased_at);
  const fullName = `${patient.first_name} ${patient.last_name}`.trim();

  return (
    <div className="flex items-center gap-4 flex-wrap rounded-lg border border-[var(--border-default)] bg-[var(--surface-raised)] px-5 py-3">
      {/* Avatar + Name + MRN */}
      <div className="flex items-center gap-3 shrink-0">
        <div className="flex items-center justify-center w-10 h-10 rounded-full bg-[var(--accent-pale)]">
          <User size={18} className="text-[var(--accent)]" />
        </div>
        <div>
          <div className="flex items-center gap-2">
            <h2 className="text-base font-bold text-[var(--text-primary)]">
              {fullName || `Patient #${patient.id}`}
            </h2>
            {patient.deceased_at && (
              <span className="inline-flex items-center gap-1 rounded-full bg-[var(--critical-bg)] px-2 py-0.5 text-[10px] font-semibold text-[var(--critical)] border border-[var(--critical-border)]">
                Deceased
              </span>
            )}
          </div>
          <p className="text-xs text-[var(--text-muted)]">
            MRN: <span className="font-mono">{patient.mrn}</span>
          </p>
        </div>
      </div>

      {/* Demographics badges */}
      <div className="flex items-center gap-4 flex-wrap text-xs text-[var(--text-secondary)] ml-auto">
        {age != null && (
          <span className="inline-flex items-center gap-1">
            <Calendar size={12} className="text-[var(--text-ghost)]" />
            {age} yrs
            {patient.date_of_birth && (
              <span className="text-[var(--text-ghost)]">({formatDob(patient.date_of_birth)})</span>
            )}
          </span>
        )}
        {patient.sex && (
          <span className="inline-flex items-center gap-1">
            <Heart size={12} className="text-[var(--text-ghost)]" />
            {patient.sex}
          </span>
        )}
        {patient.race && (
          <span className="inline-flex items-center gap-1">
            <Globe size={12} className="text-[var(--text-ghost)]" />
            {patient.race}
          </span>
        )}
        {patient.ethnicity && (
          <span className="text-[var(--text-ghost)]">
            {patient.ethnicity}
          </span>
        )}
        {patient.deceased_at && (
          <span className="inline-flex items-center gap-1 text-[var(--critical)]">
            <Calendar size={12} />
            Deceased {formatDate(patient.deceased_at)}
          </span>
        )}
      </div>
    </div>
  );
}
