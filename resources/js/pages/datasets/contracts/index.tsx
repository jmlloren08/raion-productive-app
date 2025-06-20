import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    {
        key: 'ends_on',
        label: 'Ends On',
        render: (value: any) => value ? new Date(value).toDateString() : 'Not Available',
    },
    {
        key: 'starts_on',
        label: 'Starts On',
        render: (value: any) => value ? new Date(value).toDateString() : 'Not Available',
    },
    { key: 'deal.name', label: 'Deal Name' },
];

interface Contract {
    id: number;
    type: string;
    ends_on: string;
    starts_on: string;
    deal: {
        name: string;
    };
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
    data: Contract[];
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

interface ContractsProps {
    contracts: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Contracts',
        href: '/contracts',
    }
];

export default function Contracts({ contracts }: ContractsProps) {

    const handlePageChange = (page: number) => {
        router.get('/contracts', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['contracts'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Contracts" />
            <DataTable
                columns={columns}
                data={{
                    data: contracts?.data?.data ?? [],
                    meta: {
                        current_page: contracts?.data?.current_page ?? 1,
                        from: contracts?.data?.from ?? 0,
                        last_page: contracts?.data?.last_page ?? 1,
                        per_page: contracts?.data?.per_page ?? 10,
                        to: contracts?.data?.to ?? 0,
                        total: contracts?.data?.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}