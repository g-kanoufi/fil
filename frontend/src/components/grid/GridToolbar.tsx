import { Button } from '@/components/ui/Button';
import { AiSearchField } from '@/components/search/AiSearchField';
import { ExportCsvButton } from '@/components/export/ExportCsvButton';
import { cn } from '@/lib/cn';
import { card, iconButtonClasses, selectControl, surface } from '@/lib/ui/tokens';
import { apiSortField } from '@/lib/grid/columns';
import type { GridSearchInterpretation } from '@/lib/api/gridSearch';
import type { ColDef } from 'ag-grid-community';

interface GridToolbarProps {
  resource: 'leads' | 'stores' | 'contacts';
  search: string;
  onInterpretation: (result: GridSearchInterpretation) => void;
  sortField: string;
  sortDirection: 'asc' | 'desc';
  onSortChange: (field: string, direction: 'asc' | 'desc') => void;
  columns: ColDef[];
  onExport?: () => void;
  exportDisabled?: boolean;
  exporting?: boolean;
  onAddNew?: () => void;
  addNewLabel?: string;
}

export function GridToolbar({
  resource,
  search,
  onInterpretation,
  sortField,
  sortDirection,
  onSortChange,
  columns,
  onExport,
  exportDisabled,
  exporting,
  onAddNew,
  addNewLabel = 'Add new',
}: GridToolbarProps) {
  const sortableColumns = columns.filter(
    (col) => col.sortable !== false && col.field && apiSortField(resource, col.field),
  );

  return (
    <div className={cn('mb-4 p-4', card)}>
      <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div className="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
          <AiSearchField
            resource={resource}
            value={search}
            onInterpretation={onInterpretation}
            hideLabel
            className="max-w-xl flex-1"
          />

          {sortableColumns.length > 0 ? (
            <div className="flex items-center gap-2">
              <label htmlFor="grid-sort-field" className="text-sm text-muted whitespace-nowrap">
                Sort by
              </label>
              <select
                id="grid-sort-field"
                className={selectControl}
                value={sortField}
                onChange={(event) => onSortChange(event.target.value, sortDirection)}
              >
                {sortableColumns.map((col) => (
                  <option key={col.field} value={col.field}>
                    {col.headerName}
                  </option>
                ))}
              </select>
              <button
                type="button"
                className={cn(iconButtonClasses, 'border border-border px-3 py-2 text-sm')}
                onClick={() => onSortChange(sortField, sortDirection === 'asc' ? 'desc' : 'asc')}
                aria-label="Toggle sort direction"
              >
                {sortDirection === 'asc' ? '↑ Asc' : '↓ Desc'}
              </button>
            </div>
          ) : null}
        </div>

        {onExport ? (
          <ExportCsvButton
            size="md"
            exporting={exporting}
            disabled={exportDisabled}
            onClick={onExport}
          />
        ) : null}

        {onAddNew ? (
          <Button type="button" onClick={onAddNew}>
            {addNewLabel}
          </Button>
        ) : null}
      </div>
    </div>
  );
}

interface RecordCountChipProps {
  total: number;
  loading?: boolean;
  className?: string;
}

export function RecordCountChip({ total, loading, className }: RecordCountChipProps) {
  return (
    <span
      className={cn(surface.recordCountChip, className)}
    >
      {loading ? 'Loading…' : `${total.toLocaleString()} records`}
    </span>
  );
}
