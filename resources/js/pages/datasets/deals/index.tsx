import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'creator.name', label: 'Creator' },
    { key: 'company.name', label: 'Company', truncate: true },
    { key: 'documentType.name', label: 'Document Type' },
    { key: 'responsible.name', label: 'Responsible' },
    { key: 'dealStatus.name', label: 'Deal Status' },
    { key: 'project.name', label: 'Project', truncate: true },
    { key: 'lostReason.name', label: 'Lost Reason' },
    {
        key: 'contract.ends_on',
        label: 'Contract',
        render: (value: any) => value ? new Date(value).toDateString() : 'Not Available',
    },
    { key: 'contact.name', label: 'Contact' },
    { key: 'subsidiary.name', label: 'Subsidiary', truncate: true },
    { key: 'taxRate.name', label: 'Tax Rate' },
    { key: 'apa.target_type', label: 'Approval Policy Assignments' },
    {
        key: 'created_at_api',
        label: 'Created At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    }
];

interface Deal {
    id: number;
    type: string;
    name: string;
    creator: {
        name: string;
    };
    company: {
        name: string;
    };
    documentType: {
        name: string;
    };
    responsible: {
        name: string;
    };
    dealStatus: {
        name: string;
    };
    project: {
        name: string;
    };
    lostReason: {
        name: string;
    };
    contract: {
        ends_on: string;
    };
    contact: {
        name: string;
    };
    subsidiary: {
        name: string;
    };
    taxRate: {
        name: string;
    };
    apa: {
        target_type: string;
    };
    created_at_api: string;
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
    data: Deal[];
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

interface DealsProps {
    deals: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Deals',
        href: '/deals',
    },
];

export default function Deals({ deals }: DealsProps) {

    const handlePageChange = (page: number) => {
        router.get('/deals', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['deals'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Deals" />
            <DataTable
                columns={columns}
                data={{
                    data: deals?.data?.data ?? [],
                    meta: {
                        current_page: deals?.data?.current_page ?? 1,
                        from: deals?.data?.from ?? 1,
                        last_page: deals?.data?.last_page ?? 1,
                        per_page: deals?.data?.per_page ?? 10,
                        to: deals?.data?.to ?? 10,
                        total: deals?.data?.total ?? 0,
                    },
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}