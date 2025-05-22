import React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { TimeEntryStats } from '@/types';
import { Progress } from '@/components/ui/progress';
import { formatTimeMinutes } from '@/lib/utils';

interface TimeEntriesSummaryProps {
  stats: TimeEntryStats;
  isLoading: boolean;
}

export function TimeEntriesSummary({ stats, isLoading }: TimeEntriesSummaryProps) {
  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Time Entries Summary</CardTitle>
          <CardDescription>Loading summary...</CardDescription>
        </CardHeader>
      </Card>
    );
  }

  if (!stats) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Time Entries Summary</CardTitle>
          <CardDescription>No data available</CardDescription>
        </CardHeader>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Time Entries Summary</CardTitle>
        <CardDescription>Overview of time tracking activities</CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          <div>
            <div className="flex items-center justify-between">
              <div className="text-sm font-medium">Total Time</div>
              <div className="text-sm text-muted-foreground">
                {formatTimeMinutes(stats.total_time || 0)}
              </div>
            </div>
          </div>

          <div>
            <div className="flex items-center justify-between">
              <div className="text-sm font-medium">Billable Time</div>
              <div className="text-sm text-muted-foreground">
                {formatTimeMinutes(stats.total_billable_time || 0)}
              </div>
            </div>
            <Progress
              value={stats.billable_percentage || 0}
              className="h-2 mt-2"
            />
            <div className="text-xs text-muted-foreground mt-1">
              {stats.billable_percentage || 0}% billable
            </div>
          </div>

          <div className="pt-4 border-t">
            <div className="text-sm font-medium mb-2">Count by Status</div>
            <div className="grid grid-cols-2 gap-4">
              <div className="flex flex-col">
                <span className="text-xs text-muted-foreground">Total Entries</span>
                <span className="text-xl font-bold">{stats.total_count || 0}</span>
              </div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
