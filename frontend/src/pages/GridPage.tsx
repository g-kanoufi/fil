import { useCallback, useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { CreateLeadModal } from '@/components/leads/CreateLeadModal';
import { CreateStoreModal } from '@/components/stores/CreateStoreModal';
import { FddBulkSendBar, FddBulkSendModal } from '@/components/fdd/FddBulkSendModal';
import { GridDetailPanel } from '@/components/detail/GridDetailPanel';
import { PageHeader } from '@/components/ui/PageHeader';
import { AiSearchSummary } from '@/components/search/AiSearchField';
import { FilGrid } from '@/components/grid/FilGrid';
import { GridFilterNav } from '@/components/grid/GridFilterNav';
import { GridToolbar, RecordCountChip } from '@/components/grid/GridToolbar';
import { LeadsTempFilter } from '@/components/grid/LeadsTempFilter';
import { enrichMenusWithAggregations, tempBuckets } from '@/lib/grid/aggregations';
import { applyGridSearchInterpretation, mergeInterpretationFilters } from '@/lib/grid/aiSearch';
import { buildGridFilters } from '@/lib/grid/filters';
import { menusForResource } from '@/lib/grid/menus';
import { apiSortField, gridColumnsFor } from '@/lib/grid/columns';
import { exportGridCsv } from '@/lib/grid/exportCsv';
import { gridExportFilename } from '@/lib/export/filenames';
import { useGridDetailPanel } from '@/lib/grid/useGridDetailPanel';
import { useGridQueryParams } from '@/lib/grid/useGridQueryParams';
import { useAuth } from '@/providers/AuthProvider';
import type { GridSearchInterpretation } from '@/lib/api/gridSearch';
import type { GridQueryResponse } from '@/lib/api/grid';

interface GridPageProps {
  title: string;
  resource: 'leads' | 'stores' | 'contacts';
  embedded?: boolean;
}

export function GridPage({ title, resource, embedded = false }: GridPageProps) {
  const navigate = useNavigate();
  const { appConfig, isUiDisabled, can, user } = useAuth();
  const { panelId, openPanel, closePanel, isOpen: panelOpen } = useGridDetailPanel();
  const hasPanelAccess = user?.user_has_panel_access ?? false;
  const { params, setSearch, setFilter, setLeadTemp, setSort, applyNavigation, queryKey } = useGridQueryParams(resource);

  useEffect(() => {
    if (!hasPanelAccess && panelOpen) {
      closePanel();
    }
  }, [closePanel, hasPanelAccess, panelOpen]);
  const [aggregations, setAggregations] = useState<GridQueryResponse['aggregations']>();
  const [total, setTotal] = useState(0);
  const [exporting, setExporting] = useState(false);
  const [aiInterpretation, setAiInterpretation] = useState<GridSearchInterpretation | null>(null);
  const [createOpen, setCreateOpen] = useState(false);
  const [selectedLeadIds, setSelectedLeadIds] = useState<number[]>([]);
  const [fddBulkOpen, setFddBulkOpen] = useState(false);

  const baseMenus = useMemo(() => menusForResource(appConfig, resource), [appConfig, resource]);
  const menus = useMemo(
    () => enrichMenusWithAggregations(resource, baseMenus, aggregations),
    [aggregations, baseMenus, resource],
  );

  const columnDefs = useMemo(
    () => gridColumnsFor(resource, params.filter),
    [params.filter, resource],
  );

  const filters = useMemo(
    () => mergeInterpretationFilters(buildGridFilters(resource, params, menus, appConfig), aiInterpretation),
    [aiInterpretation, appConfig, menus, params, resource],
  );

  const filtersKey = useMemo(() => JSON.stringify(filters), [filters]);

  const effectiveSort = aiInterpretation?.query.sort?.[0] ?? {
    field: params.sortField,
    direction: params.sortDirection,
  };

  const sortColField = useMemo(() => {
    const match = columnDefs.find((col) => apiSortField(resource, col.field ?? '') === params.sortField);
    return match?.field ?? params.sortField;
  }, [columnDefs, params.sortField, resource]);

  const queryState = useMemo(
    () => ({
      search: params.search.length >= 2 ? params.search : undefined,
      filters,
      sort: [{ field: effectiveSort.field, direction: effectiveSort.direction }],
    }),
    [effectiveSort.direction, effectiveSort.field, filters, params.search],
  );

  const gridQueryKey = useMemo(
    () =>
      JSON.stringify({
        base: queryKey,
        filtersKey,
        aiSort: aiInterpretation?.query.sort?.[0] ?? null,
      }),
    [aiInterpretation, filtersKey, queryKey],
  );

  const uiDomain = resource === 'leads' ? 'leads' : resource === 'stores' ? 'stores' : 'contacts';

  const isMenuDisabled = useCallback(
    (key: string) => isUiDisabled(uiDomain, key),
    [isUiDisabled, uiDomain],
  );

  const canExport = !isUiDisabled('tabs', 'export_csv');
  const canCreateLead = resource === 'leads' && can('leads.manage');
  const canCreateStore = resource === 'stores' && can('stores.manage');
  const canBulkSendFdd = resource === 'leads' && can('fdd.manage');

  const handleExport = useCallback(async () => {
    setExporting(true);
    try {
      await exportGridCsv(
        resource,
        columnDefs,
        queryState,
        gridExportFilename(resource, params.filter, params.subFilter),
      );
    } finally {
      setExporting(false);
    }
  }, [columnDefs, params.filter, params.subFilter, queryState, resource]);

  const handleSortFromToolbar = useCallback(
    (field: string, direction: 'asc' | 'desc') => {
      setAiInterpretation(null);
      const apiField = apiSortField(resource, field) ?? field;
      setSort(apiField, direction);
    },
    [resource, setSort],
  );

  const handleSortFromGrid = useCallback(
    (field: string, direction: 'asc' | 'desc') => {
      setAiInterpretation(null);
      setSort(field, direction);
    },
    [setSort],
  );

  const handleInterpretation = useCallback(
    (result: GridSearchInterpretation) => {
      applyGridSearchInterpretation(result, applyNavigation);
      setAiInterpretation(result);
    },
    [applyNavigation],
  );

  const clearAiSearch = useCallback(() => {
    setAiInterpretation(null);
    setSearch('');
    setFilter('');
    setLeadTemp('');
  }, [setFilter, setLeadTemp, setSearch]);

  const handleFilterSelect = useCallback(
    (filter: string, subFilter?: string) => {
      setAiInterpretation(null);
      setFilter(filter, subFilter);
    },
    [setFilter],
  );

  const handleLeadTempChange = useCallback(
    (value: string) => {
      setAiInterpretation(null);
      setLeadTemp(value);
    },
    [setLeadTemp],
  );

  const handleRowNavigate = useCallback(
    (id: number) => {
      if (hasPanelAccess) {
        openPanel(id);
        return;
      }

      navigate(`/reports/${resource}/${id}`);
    },
    [hasPanelAccess, navigate, openPanel, resource],
  );

  return (
    <>
      {!embedded ? (
        <PageHeader
          title={title}
          description="Search, filter, sort, and scroll to load more records."
        />
      ) : null}

      <GridToolbar
        resource={resource}
        search={params.search}
        onInterpretation={handleInterpretation}
        sortField={sortColField}
        sortDirection={params.sortDirection}
        onSortChange={handleSortFromToolbar}
        columns={columnDefs}
        onExport={canExport ? handleExport : undefined}
        exporting={exporting}
        onAddNew={
          canCreateLead || canCreateStore
            ? () => setCreateOpen(true)
            : undefined
        }
        addNewLabel={resource === 'leads' ? 'Add lead' : 'Add store'}
      />

      {aiInterpretation ? (
        <AiSearchSummary
          summary={aiInterpretation.summary}
          resultCount={aiInterpretation.result_count}
          source={aiInterpretation.source}
          confidence={aiInterpretation.confidence}
          onClear={clearAiSearch}
        />
      ) : null}

      <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
        <RecordCountChip total={total} />
      </div>

      {resource === 'leads' ? (
        <LeadsTempFilter
          total={total}
          active={params.leadtemp}
          buckets={tempBuckets(aggregations)}
          loading={!aggregations}
          onChange={handleLeadTempChange}
        />
      ) : null}

      <GridFilterNav
        resource={resource}
        menus={menus}
        aggregations={aggregations}
        activeFilter={params.filter}
        activeSubFilter={params.subFilter}
        onSelect={handleFilterSelect}
        isDisabled={isMenuDisabled}
      />

      {canBulkSendFdd ? (
        <FddBulkSendBar
          selectedCount={selectedLeadIds.length}
          onSend={() => setFddBulkOpen(true)}
          onClear={() => setSelectedLeadIds([])}
        />
      ) : null}

      <FilGrid
        resource={resource}
        columnDefs={columnDefs}
        queryState={queryState}
        queryKey={gridQueryKey}
        onRowNavigate={handleRowNavigate}
        onAggregations={setAggregations}
        onTotalChange={setTotal}
        onSortChange={handleSortFromGrid}
        enableSelection={canBulkSendFdd}
        onSelectionChange={canBulkSendFdd ? setSelectedLeadIds : undefined}
      />

      {hasPanelAccess ? (
        <GridDetailPanel
          resource={resource}
          recordId={panelId}
          open={panelOpen}
          onClose={closePanel}
        />
      ) : null}

      {resource === 'leads' ? (
        <CreateLeadModal
          open={createOpen && canCreateLead}
          onClose={() => setCreateOpen(false)}
          onCreated={(leadId) => navigate(`/reports/leads/${leadId}`)}
        />
      ) : null}

      {resource === 'stores' ? (
        <CreateStoreModal
          open={createOpen && canCreateStore}
          onClose={() => setCreateOpen(false)}
          onCreated={(storeId) => navigate(`/reports/stores/${storeId}`)}
        />
      ) : null}

      {canBulkSendFdd ? (
        <FddBulkSendModal
          open={fddBulkOpen}
          leadIds={selectedLeadIds}
          onClose={() => {
            setFddBulkOpen(false);
            setSelectedLeadIds([]);
          }}
        />
      ) : null}
    </>
  );
}
