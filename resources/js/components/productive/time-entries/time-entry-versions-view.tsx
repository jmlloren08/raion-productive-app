import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DateRangePicker } from '@/components/date-range-picker';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Search, RefreshCw } from 'lucide-react';
import { TimeEntryVersionsTable } from './time-entry-versions-table';
import { TimeEntryVersionsSummary } from './time-entry-versions-summary';
import { TimeEntryVersionModal } from './time-entry-version-modal';
import { TimeEntryHistoryModal } from './time-entry-history-modal';
import { useTimeEntryVersionsStore } from '@/stores/use-time-entry-versions-store';
import { TimeEntryVersion } from '@/types';

export function TimeEntryVersionsView() {
    const {
        fetchTimeEntryVersions,
        setTimeEntryFilter,
        setEventFilter,
        setDateFilter,
        clearFilters,
        timeEntryId,
        event,
        dateFrom,
        dateTo,
        isLoading
    } = useTimeEntryVersionsStore();

    const [localTimeEntryId, setLocalTimeEntryId] = useState(timeEntryId || '');
    const [selectedVersion, setSelectedVersion] = useState<TimeEntryVersion | null>(null);
    const [isVersionModalOpen, setIsVersionModalOpen] = useState(false);
    const [isHistoryModalOpen, setIsHistoryModalOpen] = useState(false);
    const [selectedTimeEntryId, setSelectedTimeEntryId] = useState<string | null>(null);

    useEffect(() => {
        fetchTimeEntryVersions();
    }, [fetchTimeEntryVersions]);

    const handleApplyFilters = () => {
        setTimeEntryFilter(localTimeEntryId || null);
        fetchTimeEntryVersions();
    };

    const handleResetFilters = () => {
        setLocalTimeEntryId('');
        clearFilters();
        fetchTimeEntryVersions();
    };

    const handleDateRangeChange = (range: { from?: Date; to?: Date }) => {
        const fromStr = range.from ? range.from.toISOString().split('T')[0] : null;
        const toStr = range.to ? range.to.toISOString().split('T')[0] : null;
        setDateFilter(fromStr, toStr);
        fetchTimeEntryVersions();
    };

    const handleEventChange = (value: string) => {
        setEventFilter(value === 'all' ? null : value);
        fetchTimeEntryVersions();
    };

    const handleViewVersion = (version: TimeEntryVersion) => {
        setSelectedVersion(version);
        setIsVersionModalOpen(true);
    };

    const handleViewHistory = (timeEntryId: string) => {
        setSelectedTimeEntryId(timeEntryId);
        setIsHistoryModalOpen(true);
    };

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Time Entry Versions</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-6">
                        <div>
                            <label className="text-sm font-medium mb-1 block">Time Entry ID</label>
                            <div className="flex">
                                <Input
                                    placeholder="Filter by time entry ID"
                                    value={localTimeEntryId}
                                    onChange={(e) => setLocalTimeEntryId(e.target.value)}
                                />
                            </div>
                        </div>

                        <div>
                            <label className="text-sm font-medium mb-1 block">Event Type</label>
                            <Select
                                value={event || 'all'}
                                onValueChange={handleEventChange}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select event type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Events</SelectItem>
                                    <SelectItem value="create">Create</SelectItem>
                                    <SelectItem value="update">Update</SelectItem>
                                    <SelectItem value="delete">Delete</SelectItem>
                                    <SelectItem value="restore">Restore</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="md:col-span-2">
                            <label className="text-sm font-medium mb-1 block">Date Range</label>
                            <DateRangePicker
                                from={dateFrom ? new Date(dateFrom) : undefined}
                                to={dateTo ? new Date(dateTo) : undefined}
                                onUpdate={handleDateRangeChange}
                            />
                        </div>
                    </div>

                    <div className="flex justify-between mb-6">
                        <Button onClick={handleApplyFilters} disabled={isLoading}>
                            <Search className="h-4 w-4 mr-2" />
                            Apply Filters
                        </Button>
                        <Button variant="outline" onClick={handleResetFilters} disabled={isLoading}>
                            <RefreshCw className="h-4 w-4 mr-2" />
                            Reset Filters
                        </Button>
                    </div>

                    <TimeEntryVersionsTable
                        onViewVersion={handleViewVersion}
                        onViewHistory={handleViewHistory}
                    />
                </CardContent>
            </Card>

            <TimeEntryVersionsSummary />

            {/* Modals */}
            <TimeEntryVersionModal
                version={selectedVersion}
                isOpen={isVersionModalOpen}
                onClose={() => setIsVersionModalOpen(false)}
            />

            <TimeEntryHistoryModal
                timeEntryId={selectedTimeEntryId || ''}
                isOpen={isHistoryModalOpen}
                onClose={() => setIsHistoryModalOpen(false)}
            />
        </div>
    );
}
