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
import { TimeEntryVersion } from '@/types';
import { Badge } from '@/components/ui/badge';

interface TimeEntryVersionModalProps {
    version: TimeEntryVersion | null;
    isOpen: boolean;
    onClose: () => void;
}

export function TimeEntryVersionModal({ version, isOpen, onClose }: TimeEntryVersionModalProps) {
    if (!version) return null;

    // Event badge color map
    const eventColor = {
        'create': 'bg-green-500',
        'update': 'bg-blue-500',
        'delete': 'bg-red-500',
        'restore': 'bg-yellow-500',
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Time Entry Version Details</DialogTitle>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <h3 className="text-sm font-medium text-muted-foreground">Version ID</h3>
                            <p>{version.id}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-muted-foreground">Time Entry ID</h3>
                            <p>{version.item_id}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-muted-foreground">Event</h3>
                            <Badge
                                variant="outline"
                                className={`${eventColor[version.event as keyof typeof eventColor] || 'bg-gray-500'} text-white mt-1`}
                            >
                                {version.event}
                            </Badge>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-muted-foreground">Date</h3>
                            <p>
                                {version.created_at_api && format(new Date(version.created_at_api), 'MMM d, yyyy h:mm a')}
                            </p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-muted-foreground">Creator ID</h3>
                            <p>{version.creator_id || 'Not available'}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-muted-foreground">Organization ID</h3>
                            <p>{version.organization_id || 'Not available'}</p>
                        </div>
                    </div>

                    <div>
                        <h3 className="text-sm font-medium text-muted-foreground mb-2">Object Changes</h3>
                        <div className="border rounded-md p-4 overflow-auto max-h-[400px] bg-muted/50">
                            <pre className="text-xs">
                                {JSON.stringify(version.object_changes, null, 2)}
                            </pre>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
