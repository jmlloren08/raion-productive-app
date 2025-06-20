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
        render: (value: any) => value ? new Date(value).toLocaleDateString() : 'Not Available'
    },
    { key: 'person.first_name', label: 'Person' },
    { key: 'creator.first_name', label: 'Creator' },
    {
        key: 'created_at_api',
        label: 'Created At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available'
    },
];

interface Timesheet {
    id: number;
    type: string;
    date: string | null;
    person: {
        first_name: string
    };
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
    data: Timesheet[];
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

interface TimesheetsProps {
    timesheets: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Timesheets',
        href: '/timesheets',
    }
];

export default function Timesheets({ timesheets }: TimesheetsProps) {

    const handlePageChange = (page: number) => {
        router.get('/timesheets', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['timesheets'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Timesheets" />
            <DataTable
                columns={columns}
                data={{
                    data: timesheets?.data?.data ?? [],
                    meta: {
                        current_page: timesheets?.data?.current_page ?? 1,
                        from: timesheets?.data?.from ?? 0,
                        last_page: timesheets?.data?.last_page ?? 1,
                        per_page: timesheets?.data?.per_page ?? 10,
                        to: timesheets?.data?.to ?? 0,
                        total: timesheets?.data?.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}