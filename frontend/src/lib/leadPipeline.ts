import type { AppConfig } from '@/types/auth';

export interface PipelinePhase {
  id: number;
  label: string;
  description: string;
}

export function pipelinePhasesFromConfig(appConfig: AppConfig | null): PipelinePhase[] {
  return appConfig?.pipeline?.phases ?? DEFAULT_PIPELINE_PHASES;
}

export function pipelinePhaseLabel(
  phaseId: number,
  appConfig: AppConfig | null,
): string {
  const phases = pipelinePhasesFromConfig(appConfig);
  return phases.find((phase) => phase.id === phaseId)?.label ?? `Phase ${phaseId}`;
}

export function selectablePipelinePhases(appConfig: AppConfig | null): PipelinePhase[] {
  return pipelinePhasesFromConfig(appConfig).filter((phase) => phase.id !== 99);
}

const DEFAULT_PIPELINE_PHASES: PipelinePhase[] = [
  { id: 1, label: 'Intake', description: 'Short form submitted' },
  { id: 2, label: 'Outreach', description: 'Initial contact attempted' },
  { id: 3, label: 'Engaged', description: 'Spoke with prospect' },
  { id: 4, label: 'Qualified', description: 'Long form completed' },
  { id: 5, label: 'FDD Disclosed', description: 'FDD sent to prospect' },
  { id: 6, label: 'FDD Review', description: 'Prospect reviewing FDD materials' },
  { id: 7, label: 'FDD Signed', description: 'FDD receipt signed' },
  { id: 8, label: 'Waiting Period', description: 'Mandatory waiting period in progress' },
  { id: 9, label: 'Ready to Award', description: 'Waiting period complete' },
  { id: 10, label: 'Awarded', description: 'Deal awarded' },
  { id: 99, label: 'Closed', description: 'Inactive or disqualified' },
];
