import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    {
        key: 'date',
        label: 'Date',
        render: (value: any) => value ? new Date(value).toLocaleDateString() : 'Not Available',
    },
    { key: 'time', label: 'Time' },
    { key: 'person.first_name', label: 'Name' },
    { key: 'service.name', label: 'Service', truncate: true },
    { key: 'task.title', label: 'Task', truncate: true },
    { key: 'approver.first_name', label: 'Approver' },
    { key: 'updater.first_name', label: 'Updater' },
    { key: 'rejector.first_name', label: 'Rejector' },
    { key: 'creator.first_name', label: 'Creator' },
    { key: 'lastActor.first_name', label: 'Last Actor' },
    { key: 'personSubsidiary.name', label: 'Person Subsidiary' },
    { key: 'dealSubsidiary.name', label: 'Deal Subsidiary' },
    { key: 'timesheet.name', label: 'Timesheet' },
    {
        key: 'created_at_api',
        label: 'Created At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    }
];

interface TimeEntry {
    id: number;
    type: string;
    date: string | null;
    time: string | null;
    person: {
        first_name: string
    };
    service: {
        name: string
    };
    task: {
        title: string
    };
    approver: {
        first_name: string
    };
    updater: {
        first_name: string
    };
    rejector: {
        first_name: string
    };
    creator: {
        first_name: string
    };
    lastActor: {
        first_name: string
    };
    personSubsidiary: {
        name: string
    };
    dealSubsidiary: {
        name: string
    };
    timesheet: {
        name: string
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
    data: TimeEntry[];
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

interface TimeEntriesProps {
    timeEntries: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Time Entries',
        href: '/time-entries',
    }
];

export default function TimeEntries({ timeEntries }: TimeEntriesProps) {

    const handlePageChange = (page: number) => {
        router.get('/time-entries', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['timeEntries'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Time Entries" />
            <DataTable
                columns={columns}
                data={{
                    data: timeEntries?.data?.data ?? [],
                    meta: {
                        current_page: timeEntries?.data?.current_page ?? 1,
                        from: timeEntries?.data?.from ?? 0,
                        last_page: timeEntries?.data?.last_page ?? 1,
                        per_page: timeEntries?.data?.per_page ?? 10,
                        to: timeEntries?.data?.to ?? 0,
                        total: timeEntries?.data?.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}