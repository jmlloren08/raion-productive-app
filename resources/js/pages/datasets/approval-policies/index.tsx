
import { DataTable } from '@/components/ui/data-table';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name' },
    { key: 'description', label: 'Description', truncate: true },
    { key: 'custom', label: 'Custom' },
    { key: 'default', label: 'Default' },
    {
        key: 'archived_at',
        label: 'Archived At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Archived',
    }
];

interface ApprovalPolicy {
    id: number;
    type: string;
    name: string;
    description: string;
    custom: boolean;
    default: boolean;
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
    data: ApprovalPolicy[];
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

interface ApprovalPoliciesProps {
    approvalPolicies: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Approval Policies',
        href: '/approval-policies',
    }
];

export default function ApprovalPolicies({ approvalPolicies }: ApprovalPoliciesProps) {

    const handlePageChange = (page: number) => {
        router.get(`/approval-policies`, { page: page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['approvalPolicies'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs} >
            <Head title="Approval Policies" />
                <DataTable
                    columns={columns}
                    data={{
                        data: approvalPolicies?.data?.data ?? [],
                        meta: {
                            current_page: approvalPolicies?.data?.current_page ?? 1,
                            from: approvalPolicies?.data?.from ?? 0,
                            last_page: approvalPolicies?.data?.last_page ?? 1,
                            per_page: approvalPolicies?.data?.per_page ?? 10,
                            to: approvalPolicies?.data?.to ?? 0,
                            total: approvalPolicies?.data?.total ?? 0
                        }
                    }}
                    onPageChange={handlePageChange}
                />
        </AppLayout>
    );
}