import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name' },
    { key: 'company.name', label: 'Company' },
    { key: 'deal.name', label: 'Deal' },
    { key: 'approvalPolicy.name', label: 'Approval Policy' },
];

interface ApprovalPolicyAssignment {
    id: number;
    type: string;
    name: string;
    company: {
        name: string;
    };
    deal: {
        name: string;
    };
    approvalPolicy: {
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
    data: ApprovalPolicyAssignment[];
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

interface ApprovalPolicyAssignmentsProps {
    approvalPolicyAssignments: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Approval Policy Assignments',
        href: '/approval-policy-assignments',
    },
];

export default function ApprovalPolicyAssignments({ approvalPolicyAssignments }: ApprovalPolicyAssignmentsProps) {

    const handlePageChange = (page: number) => {
        router.get('/approval-policy-assignments', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['approvalPolicyAssignments'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Approval Policy Assignments" />
            <DataTable
                columns={columns}
                data={{
                    data: approvalPolicyAssignments?.data?.data ?? [],
                    meta: {
                        current_page: approvalPolicyAssignments?.data?.current_page ?? 1,
                        from: approvalPolicyAssignments?.data?.from ?? 0,
                        last_page: approvalPolicyAssignments?.data?.last_page ?? 1,
                        per_page: approvalPolicyAssignments?.data?.per_page ?? 10,
                        to: approvalPolicyAssignments?.data?.to ?? 0,
                        total: approvalPolicyAssignments?.data?.total ?? 0,
                    },
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}