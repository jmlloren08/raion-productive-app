import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'event_type', label: 'Event' },
    { key: 'object_changes', label: 'Changes', truncate: true },
    {
        key: 'timeEntry.date',
        label: 'Date',
        render: (value: any) => value ? new Date(value).toLocaleDateString() : 'Not Available'
    },
    {
        key: 'timeEntry.time',
        label: 'Time',
    },
    { key: 'item_type', label: 'Item Type' },
    { key: 'creator.first_name', label: 'Creator' },
    {
        key: 'created_at_api',
        label: 'Created At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available'
    }
];

interface TimeEntryVersion {
    id: number;
    type: string;
    event_type: string;
    object_changes: string;
    timeEntry: {
        date: string | null;
        time: string | null;
    };
    item_type: string;
    creator: {
        first_name: string
    };
    created_at_api: string | null;
}

interface PaginationMeta {
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
}

interface LaravelPagination {
    current_page: number;
    data: TimeEntryVersion[];
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
    meta: PaginationMeta;
}

interface TimeEntryVersionsProps {
    timeEntryVersions: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Time Entry Versions',
        href: '/time-entry-versions',
    }
];

export default function TimeEntryVersions({ timeEntryVersions }: TimeEntryVersionsProps) {

    const handlePageChange = (page: number) => {
        router.get('/time-entry-versions', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['timeEntryVersions'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Time Entry Versions" />
            <DataTable
                columns={columns}
                data={{
                    data: timeEntryVersions?.data?.data ?? [],
                    meta: {
                        current_page: timeEntryVersions?.data?.current_page ?? 1,
                        from: timeEntryVersions?.data?.from ?? 1,
                        last_page: timeEntryVersions?.data?.last_page ?? 1,
                        per_page: timeEntryVersions?.data?.per_page ?? 10,
                        to: timeEntryVersions?.data?.to ?? 0,
                        total: timeEntryVersions?.data?.total ?? 0
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}