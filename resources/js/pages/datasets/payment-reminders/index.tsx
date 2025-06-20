import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'subject', label: 'Subject', truncate: true },
    { key: 'creator.first_name', label: 'Creator' },
    { key: 'updater.first_name', label: 'Updater' },
    { key: 'invoice.number', label: 'Invoice'},
    { key: 'prs.name', label: 'Payment Reminder Sequences'},
    {
        key: 'created_at',
        label: 'Created At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    },
];

interface PaymentReminder {
    id: number;
    type: string;
    subject: string;
    creator: {
        first_name: string;
    };
    updater: {
        first_name: string;
    };
    invoice: {
        number: string;
    };
    prs: {
        name: string;
    };
    created_at: string;
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
    data: PaymentReminder[];
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

interface PaymentRemindersProps {
    paymentReminders: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Payment Reminders',
        href: '/payment-reminders',
    },
];

export default function PaymentReminders({ paymentReminders }: PaymentRemindersProps) {

    const handlePageChange = (page: number) => {
        router.get('/payment-reminders', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['paymentReminders'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Payment Reminders" />
            <DataTable
                columns={columns}
                data={{
                    data: paymentReminders?.data?.data ?? [],
                    meta: {
                        current_page: paymentReminders?.data?.current_page ?? 1,
                        from: paymentReminders?.data?.from ?? 0,
                        last_page: paymentReminders?.data?.last_page ?? 1,
                        per_page: paymentReminders?.data?.per_page ?? 10,
                        to: paymentReminders?.data?.to ?? 0,
                        total: paymentReminders?.data?.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}