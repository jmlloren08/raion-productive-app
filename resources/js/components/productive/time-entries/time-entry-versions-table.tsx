import React from 'react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Badge } from '@/components/ui/badge';
import { format } from 'date-fns';
import { TimeEntryVersion } from '@/types';
import { History, Eye } from 'lucide-react';
import { useTimeEntryVersionsStore } from '@/stores/use-time-entry-versions-store';

interface TimeEntryVersionsTableProps {
    onViewVersion: (version: TimeEntryVersion) => void;
    onViewHistory: (timeEntryId: string) => void;
}

export function TimeEntryVersionsTable({ onViewVersion, onViewHistory }: TimeEntryVersionsTableProps) {
    const { timeEntryVersions, isLoading } = useTimeEntryVersionsStore();

    if (isLoading) {
        return (
            <div className="space-y-3">
                <Skeleton className="h-8 w-full" />
                <Skeleton className="h-8 w-full" />
                <Skeleton className="h-8 w-full" />
                <Skeleton className="h-8 w-full" />
                <Skeleton className="h-8 w-full" />
            </div>
        );
    }

    if (timeEntryVersions.length === 0) {
        return (
            <div className="text-center py-10 text-muted-foreground">
                No time entry versions found.
            </div>
        );
    }

    // Event badge color map
    const eventColor = {
        'create': 'bg-green-500',
        'update': 'bg-blue-500',
        'delete': 'bg-red-500',
        'restore': 'bg-yellow-500',
    };

    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Date</TableHead>
                    <TableHead>Event</TableHead>
                    <TableHead>Time Entry ID</TableHead>
                    <TableHead>Changes</TableHead>
                    <TableHead>Actions</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {timeEntryVersions.map((version) => (
                    <TableRow key={version.id}>
                        <TableCell>
                            {version.created_at_api && format(new Date(version.created_at_api), 'MMM d, yyyy h:mm a')}
                        </TableCell>
                        <TableCell>
                            <Badge
                                variant="outline"
                                className={`${eventColor[version.event as keyof typeof eventColor] || 'bg-gray-500'} text-white`}
                            >
                                {version.event}
                            </Badge>
                        </TableCell>
                        <TableCell>{version.item_id}</TableCell>
                        <TableCell>
                            {version.object_changes && Object.keys(version.object_changes).length > 0 ?
                                Object.keys(version.object_changes).length + ' fields changed' :
                                'No changes recorded'}
                        </TableCell>
                        <TableCell>
                            <div className="flex space-x-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => onViewVersion(version)}
                                    title="View Version Details"
                                >
                                    <Eye className="h-4 w-4 mr-1" />
                                    View
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => onViewHistory(version.item_id)}
                                    title="View Time Entry History"
                                >
                                    <History className="h-4 w-4 mr-1" />
                                    History
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}
