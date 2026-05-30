import { useMemo } from 'react';
import { useTheme } from '@/providers/ThemeProvider';

export type ChartColors = {
  brand: string;
  success: string;
  info: string;
  neutral: string;
  grid: string;
  tick: string;
  tooltipBg: string;
  tooltipBorder: string;
  tooltipText: string;
};

const LIGHT_CHART_COLORS: ChartColors = {
  brand: '#2563eb',
  success: '#059669',
  info: '#0284c7',
  neutral: '#64748b',
  grid: '#e2e8f0',
  tick: '#64748b',
  tooltipBg: '#ffffff',
  tooltipBorder: '#e2e8f0',
  tooltipText: '#0f172a',
};

const DARK_CHART_COLORS: ChartColors = {
  brand: '#60a5fa',
  success: '#34d399',
  info: '#38bdf8',
  neutral: '#94a3b8',
  grid: 'hsl(222 16% 22%)',
  tick: '#94a3b8',
  tooltipBg: 'hsl(222 24% 14%)',
  tooltipBorder: 'hsl(222 16% 22%)',
  tooltipText: 'hsl(210 20% 96%)',
};

/** @deprecated Prefer useChartColors() for theme-aware charts. */
export const CHART_COLORS = LIGHT_CHART_COLORS;

export function useChartColors(): ChartColors {
  const { resolved } = useTheme();

  return useMemo(
    () => (resolved === 'dark' ? DARK_CHART_COLORS : LIGHT_CHART_COLORS),
    [resolved],
  );
}
