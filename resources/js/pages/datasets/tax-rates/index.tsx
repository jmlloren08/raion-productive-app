import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'subsidiary.name', label: 'Subsidiary', truncate: true },
    {
        key: 'archived_at',
        label: 'Archived At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    },
];

interface TaxRate {
    id: number;
    type: string;
    name: string;
    subsidiary: {
        name: string;
    };
    archived_at: string | null;
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
    data: TaxRate[];
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

interface TaxRatesProps {
    taxRates: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tax Rates',
        href: '/tax-rates',
    }
];

export default function TaxRates({ taxRates }: TaxRatesProps) {

    const handlePageChange = (page: number) => {
        router.get('/tax-rates', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['taxRates'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tax Rates" />
            <DataTable
                columns={columns}
                data={{
                    data: taxRates?.data?.data ?? [],
                    meta: {
                        current_page: taxRates?.data?.current_page ?? 1,
                        from: taxRates?.data?.from ?? 0,
                        last_page: taxRates?.data?.last_page ?? 1,
                        per_page: taxRates?.data?.per_page ?? 10,
                        to: taxRates?.data?.to ?? 0,
                        total: taxRates?.data?.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}