import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  fetchPatientFlags,
  createPatientFlag,
  updatePatientFlag,
  deletePatientFlag,
  fetchPatientTasks,
  createPatientTask,
  updatePatientTask,
  deletePatientTask,
  fetchPatientCollaboration,
  fetchPatientDecisions,
} from '../api/collaborationApi';
import type { ClinicalDomain } from '../types/collaboration';

// ── Flags ────────────────────────────────────────────────────────────

export function usePatientFlags(patientId: number | undefined, domain?: ClinicalDomain) {
  return useQuery({
    queryKey: ['patient-flags', patientId, domain],
    queryFn: () => fetchPatientFlags(patientId!, domain, false),
    enabled: !!patientId,
    staleTime: 30_000,
  });
}

export function useCreateFlag(patientId: number | undefined) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: Parameters<typeof createPatientFlag>[1]) =>
      createPatientFlag(patientId!, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['patient-flags', patientId] });
      qc.invalidateQueries({ queryKey: ['patient-collaboration', patientId] });
    },
  });
}

export function useUpdateFlag(patientId: number | undefined) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ flagId, data }: { flagId: number; data: Parameters<typeof updatePatientFlag>[1] }) =>
      updatePatientFlag(flagId, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['patient-flags', patientId] });
      qc.invalidateQueries({ queryKey: ['patient-collaboration', patientId] });
    },
  });
}

export function useDeleteFlag(patientId: number | undefined) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (flagId: number) => deletePatientFlag(flagId),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['patient-flags', patientId] });
      qc.invalidateQueries({ queryKey: ['patient-collaboration', patientId] });
    },
  });
}

// ── Tasks ────────────────────────────────────────────────────────────

export function usePatientTasks(patientId: number | undefined, domain?: ClinicalDomain) {
  return useQuery({
    queryKey: ['patient-tasks', patientId, domain],
    queryFn: () => fetchPatientTasks(patientId!, domain),
    enabled: !!patientId,
    staleTime: 30_000,
  });
}

export function useCreateTask(patientId: number | undefined) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: Parameters<typeof createPatientTask>[1]) =>
      createPatientTask(patientId!, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['patient-tasks', patientId] });
      qc.invalidateQueries({ queryKey: ['patient-collaboration', patientId] });
    },
  });
}

export function useUpdateTask(patientId: number | undefined) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ taskId, data }: { taskId: number; data: Parameters<typeof updatePatientTask>[1] }) =>
      updatePatientTask(taskId, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['patient-tasks', patientId] });
      qc.invalidateQueries({ queryKey: ['patient-collaboration', patientId] });
    },
  });
}

export function useDeleteTask(patientId: number | undefined) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (taskId: number) => deletePatientTask(taskId),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['patient-tasks', patientId] });
      qc.invalidateQueries({ queryKey: ['patient-collaboration', patientId] });
    },
  });
}

// ── Follow-ups (standalone query for Briefing) ──────────────────────

export function usePatientFollowUps(patientId: number | undefined) {
  return useQuery({
    queryKey: ['patient-follow-ups', patientId],
    queryFn: async () => {
      const collab = await fetchPatientCollaboration(patientId!);
      return collab.follow_ups;
    },
    enabled: !!patientId,
    staleTime: 30_000,
  });
}

// ── Collaboration Aggregate ──────────────────────────────────────────

export function usePatientCollaboration(patientId: number | undefined, domain?: ClinicalDomain) {
  return useQuery({
    queryKey: ['patient-collaboration', patientId, domain],
    queryFn: () => fetchPatientCollaboration(patientId!, domain),
    enabled: !!patientId,
    staleTime: 15_000,
  });
}

// ── Decisions ────────────────────────────────────────────────────────

export function usePatientDecisions(patientId: number | undefined) {
  return useQuery({
    queryKey: ['patient-decisions', patientId],
    queryFn: () => fetchPatientDecisions(patientId!),
    enabled: !!patientId,
    staleTime: 30_000,
  });
}
