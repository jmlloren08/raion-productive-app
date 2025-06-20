import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'event_type_id', label: 'Event Type' },
    {
        key: 'archived_at',
        label: 'Archived At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    }
];

interface Event {
    id: number;
    type: string;
    name: string;
    event_type_id: number;
    archived_at: string;
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
    data: Event[];
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
}

interface EventsProps {
    events: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Events',
        href: '/events',
    }
];

export default function Events({ events }: EventsProps) {

    const handlePageChange = (page: number) => {
        router.get('/events', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['events'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Events" />
            <DataTable
                columns={columns}
                data={{
                    data: events?.data?.data ?? [],
                    meta: {
                        current_page: events?.data?.current_page ?? 1,
                        from: events?.data?.from ?? 1,
                        last_page: events?.data?.last_page ?? 1,
                        per_page: events?.data?.per_page ?? 10,
                        to: events?.data?.to ?? 0,
                        total: events?.data?.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}