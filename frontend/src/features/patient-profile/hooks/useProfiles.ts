import { useState, useEffect } from "react";
import { useQuery } from "@tanstack/react-query";
import {
  fetchPatientProfile,
  fetchPatientStats,
  searchPatients,
  fetchPatientNotes,
} from "../api/profileApi";

export function usePatientProfile(patientId: number | null) {
  return useQuery({
    queryKey: ["patient-profile", patientId],
    queryFn: () => fetchPatientProfile(patientId!),
    enabled: patientId != null && patientId > 0,
  });
}

export function usePatientStats(patientId: number | null) {
  return useQuery({
    queryKey: ["patient-stats", patientId],
    queryFn: () => fetchPatientStats(patientId!),
    enabled: patientId != null && patientId > 0,
    staleTime: 60_000,
  });
}

export function usePatientSearch(query: string) {
  const [debouncedQuery, setDebouncedQuery] = useState(query);

  useEffect(() => {
    const timer = setTimeout(() => setDebouncedQuery(query), 350);
    return () => clearTimeout(timer);
  }, [query]);

  return useQuery({
    queryKey: ["patient-search", debouncedQuery],
    queryFn: () => searchPatients(debouncedQuery),
    enabled: debouncedQuery.trim().length >= 1,
    staleTime: 30_000,
  });
}

export function usePatientNotes(patientId: number | null, page = 1, perPage = 50) {
  return useQuery({
    queryKey: ["patient-notes", patientId, { page, perPage }],
    queryFn: () => fetchPatientNotes(patientId!, page, perPage),
    enabled: patientId != null && patientId > 0,
  });
}
