import React from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { format } from 'date-fns';
import { TimeEntryVersionTimeline } from '@/types';
import { useTimeEntryVersionsStore } from '@/stores/use-time-entry-versions-store';
import { Skeleton } from '@/components/ui/skeleton';
import { Badge } from '@/components/ui/badge';

interface TimeEntryHistoryModalProps {
    timeEntryId: string;
    isOpen: boolean;
    onClose: () => void;
}

export function TimeEntryHistoryModal({ timeEntryId, isOpen, onClose }: TimeEntryHistoryModalProps) {
    const { timeEntryHistory, isLoadingHistory, fetchTimeEntryHistory } = useTimeEntryVersionsStore();

    React.useEffect(() => {
        if (isOpen && timeEntryId) {
            fetchTimeEntryHistory(timeEntryId);
        }
    }, [isOpen, timeEntryId, fetchTimeEntryHistory]);

    // Event badge color map
    const eventColor = {
        'create': 'bg-green-500',
        'update': 'bg-blue-500',
        'delete': 'bg-red-500',
        'restore': 'bg-yellow-500',
    };

    const renderChanges = (changes: Record<string, any>) => {
        if (!changes || Object.keys(changes).length === 0) {
            return <div className="text-muted-foreground">No changes recorded</div>;
        }

        return (
            <div className="space-y-2">
                {Object.entries(changes).map(([field, values]) => {
                    // values is typically an array with [old_value, new_value]
                    const [oldValue, newValue] = Array.isArray(values) ? values : [null, values];

                    return (
                        <div key={field} className="border-b pb-2">
                            <div className="font-medium">{field}</div>
                            <div className="grid grid-cols-2 gap-2 mt-1">
                                <div className="text-sm">
                                    <span className="text-muted-foreground">Old: </span>
                                    <span className="font-mono bg-muted px-1 py-0.5 rounded">
                                        {oldValue === null || oldValue === undefined
                                            ? 'null'
                                            : String(oldValue)}
                                    </span>
                                </div>
                                <div className="text-sm">
                                    <span className="text-muted-foreground">New: </span>
                                    <span className="font-mono bg-muted px-1 py-0.5 rounded">
                                        {newValue === null || newValue === undefined
                                            ? 'null'
                                            : String(newValue)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        );
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-4xl max-h-screen overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Time Entry History (ID: {timeEntryId})</DialogTitle>
                </DialogHeader>

                {isLoadingHistory ? (
                    <div className="space-y-3">
                        <Skeleton className="h-[300px] w-full" />
                    </div>
                ) : (
                    <div className="space-y-6">
                        {timeEntryHistory.length === 0 ? (
                            <div className="text-center py-10 text-muted-foreground">
                                No history found for this time entry.
                            </div>
                        ) : (
                            <div className="space-y-6">
                                {timeEntryHistory.map((version, index) => (
                                    <div key={version.id} className="border rounded-lg p-4 space-y-3">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center space-x-2">
                                                <Badge
                                                    variant="outline"
                                                    className={`${eventColor[version.event as keyof typeof eventColor] || 'bg-gray-500'} text-white`}
                                                >
                                                    {version.event}
                                                </Badge>
                                                <span className="text-sm text-muted-foreground">
                                                    {version.date && format(new Date(version.date), 'MMM d, yyyy h:mm a')}
                                                </span>
                                            </div>
                                            <div className="text-sm text-muted-foreground">
                                                {version.creator_id ? `By User: ${version.creator_id}` : 'System generated'}
                                            </div>
                                        </div>

                                        <div className="mt-2">
                                            <h4 className="text-sm font-medium mb-1">Changes:</h4>
                                            {renderChanges(version.changes)}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
