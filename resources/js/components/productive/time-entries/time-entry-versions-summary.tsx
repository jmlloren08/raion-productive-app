import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTimeEntryVersionsStore } from '@/stores/use-time-entry-versions-store';
import { Skeleton } from '@/components/ui/skeleton';
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer
} from 'recharts';

export function TimeEntryVersionsSummary() {
    const { versionStats, versionsByEvent, versionsByDate, isLoading } = useTimeEntryVersionsStore();

    if (isLoading) {
        return (
            <div className="space-y-3">
                <Skeleton className="h-[200px] w-full" />
            </div>
        );
    }

    if (!versionStats) {
        return (
            <div className="text-center py-10 text-muted-foreground">
                No version statistics available.
            </div>
        );
    }

    // Prepare data for the event type chart
    const eventTypeChartData = Object.entries(versionsByEvent).map(([event, data]) => ({
        name: event,
        count: data.count,
    }));

    // Prepare data for the date chart
    const dateChartData = Object.entries(versionsByDate).map(([date, data]) => ({
        date,
        total: data.count,
        ...data.by_event,
    }));

    // Colors for different events
    const eventColors = {
        'create': '#22c55e', // green
        'update': '#3b82f6', // blue
        'delete': '#ef4444', // red
        'restore': '#eab308', // yellow
        'default': '#6b7280', // gray
    };

    return (
        <div className="grid gap-4 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Version Statistics</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl className="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt className="text-muted-foreground">Total Versions</dt>
                            <dd className="text-2xl font-bold">{versionStats.total_count}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Displayed</dt>
                            <dd className="text-2xl font-bold">{versionStats.displayed_count}</dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Versions by Event Type</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="h-[200px]">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={eventTypeChartData} layout="vertical">
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis type="number" />
                                <YAxis dataKey="name" type="category" />
                                <Tooltip />
                                <Bar
                                    dataKey="count"
                                    name="Count"
                                    fill={({ name }) => eventColors[name as keyof typeof eventColors] || eventColors.default}
                                />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </CardContent>
            </Card>

            <Card className="md:col-span-2">
                <CardHeader>
                    <CardTitle>Versions Timeline</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="h-[300px]">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={dateChartData}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="date" />
                                <YAxis />
                                <Tooltip />
                                <Legend />
                                {Object.keys(eventColors).map(eventType => (
                                    dateChartData.some(item => item[eventType] > 0) && (
                                        <Bar
                                            key={eventType}
                                            dataKey={eventType}
                                            name={eventType}
                                            stackId="a"
                                            fill={eventColors[eventType as keyof typeof eventColors]}
                                        />
                                    )
                                ))}
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
