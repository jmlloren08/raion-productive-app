import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'serviceType.name', label: 'Service Type', truncate: true },
    { key: 'deal.name', label: 'Deal', truncate: true },
    { key: 'person.first_name', label: 'Person' },
    { key: 'section.name', label: 'Section' },
    {
        key: 'deleted_at_api',
        label: 'Deleted At',
        render: (value: string | null) => value ? new Date(value).toLocaleString() : 'N/A',
    },
];

interface Service {
    id: number;
    type: string;
    name: string;
    serviceType: {
        name: string;
    };
    deal: {
        name: string;
    };
    person: {
        first_name: string;
    };
    section: {
        name: string;
    };
    deleted_at_api: string | null;
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
    data: Service[];
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

interface ServicesProps {
    services: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Services',
        href: '/services',
    }
];

export default function Services({ services }: ServicesProps) {

    const handlePageChange = (page: number) => {
        router.get('/services', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['services'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Services" />
            <DataTable
                columns={columns}
                data={{
                    data: services?.data?.data ?? [],
                    meta: {
                        current_page: services?.data?.current_page ?? 1,
                        from: services?.data?.from ?? 0,
                        last_page: services?.data?.last_page ?? 1,
                        per_page: services?.data?.per_page ?? 10,
                        to: services?.data?.to ?? 0,
                        total: services?.data?.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}