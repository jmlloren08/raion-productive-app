import { DataTable } from '@/components/ui/data-table';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name' },
    { key: 'subsidiary.name', label: 'Subsidiary' },
    { key: 'taxRate.name', label: 'Tax Rate' },
    {
        key: 'created_at_api',
        label: 'Created At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    },
];

interface Company {
    id: number;
    type: string;
    name: string;
    billing_name?: string;
    subsidiary: {
        name: string;
    };
    taxRate: {
        name: string;
    };
    created_at_api?: string;
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
    data: Company[];
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

interface CompaniesProps {
    companies: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Companies',
        href: '/companies',
    },
];

export default function Companies({ companies }: CompaniesProps) {
    
    const handlePageChange = (page: number) => {
        router.get(`/companies`, { page: page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['companies'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Companies" />
            <DataTable
                    columns={columns}
                    data={{
                        data: companies?.data?.data ?? [],
                        meta: {
                            current_page: companies?.data?.current_page ?? 1,
                            from: companies?.data?.from ?? 0,
                            last_page: companies?.data?.last_page ?? 1,
                            per_page: companies?.data?.per_page ?? 10,
                            to: companies?.data?.to ?? 0,
                            total: companies?.data?.total ?? 0
                        }
                    }}
                    onPageChange={handlePageChange}
                />
        </AppLayout>
    );
}
