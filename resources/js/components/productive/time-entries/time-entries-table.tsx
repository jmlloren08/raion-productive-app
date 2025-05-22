import React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { TimeEntry } from '@/types';
import { formatDistanceToNow } from 'date-fns';
import { formatTimeMinutes } from '@/lib/utils';

interface TimeEntriesTableProps {
  timeEntries: TimeEntry[];
  isLoading: boolean;
}

export function TimeEntriesTable({ timeEntries, isLoading }: TimeEntriesTableProps) {
  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Recent Time Entries</CardTitle>
          <CardDescription>Loading time entries...</CardDescription>
        </CardHeader>
      </Card>
    );
  }

  if (!timeEntries?.length) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Recent Time Entries</CardTitle>
          <CardDescription>No time entries found.</CardDescription>
        </CardHeader>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Recent Time Entries</CardTitle>
        <CardDescription>Recent time tracking activity</CardDescription>
      </CardHeader>
      <CardContent>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Date</TableHead>
              <TableHead>Time</TableHead>
              <TableHead>Billable</TableHead>
              <TableHead>Service</TableHead>
              <TableHead>Person</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {timeEntries.map((entry) => (
              <TableRow key={entry.id}>
                <TableCell>{new Date(entry.date).toLocaleDateString()}</TableCell>
                <TableCell>{formatTimeMinutes(entry.time)}</TableCell>
                <TableCell>{entry.billable_time ? 'Yes' : 'No'}</TableCell>
                <TableCell>{entry.service_id || '—'}</TableCell>
                <TableCell className="max-w-xs truncate">{entry.person_id}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  );
}
