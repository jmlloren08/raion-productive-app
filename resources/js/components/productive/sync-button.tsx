import { Button } from "@/components/ui/button";
import { useProductiveStore } from "@/stores/use-productive-store";
import { Loader2, RefreshCw } from "lucide-react";
import { useEffect } from "react";
import { formatDistanceToNow } from "date-fns";

export function SyncButton() {
    const { syncStatus, isSyncing, syncError, checkSyncStatus, triggerSync } = useProductiveStore();

    // Check sync status on mount
    useEffect(() => {
        checkSyncStatus();
    }, [checkSyncStatus]);

    const handleSync = async () => {
        await triggerSync();
    };

    return (
        <div className="flex items-center gap-4">
            <Button
                onClick={handleSync}
                disabled={isSyncing}
                variant="outline"
                size="sm"
            >
                {isSyncing ? (
                    <>
                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        Syncing...
                    </>
                ) : (
                    <>
                        <RefreshCw className="mr-2 h-4 w-4" />
                        Sync with Productive
                    </>
                )}
            </Button>
            
            {syncStatus?.last_sync && (
                <span className="text-sm text-muted-foreground">
                    Last synced: {formatDistanceToNow(new Date(syncStatus.last_sync), { addSuffix: true })}
                </span>
            )}
            
            {syncError && (
                <span className="text-sm text-destructive">
                    {syncError}
                </span>
            )}
        </div>
    );
}
