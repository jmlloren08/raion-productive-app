import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'creator.first_name', label: 'Creator' },
    { key: 'updater.first_name', label: 'Updater' },
    { key: 'paymentReminder.name', label: 'Payment Reminder' },
    {
        key: 'created_at',
        label: 'Created At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    },
];

interface PaymentReminderSequence {
    id: number;
    type: string;
    name: string;
    creator: {
        first_name: string;
    };
    updater: {
        first_name: string;
    };
    paymentReminder: {
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
    data: PaymentReminderSequence[];
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

interface PaymentReminderSequencesProps {
    paymentReminderSequences: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Payment Reminder Sequences',
        href: '/payment-reminder-sequences',
    }
];

export default function PaymentReminderSequences({ paymentReminderSequences }: PaymentReminderSequencesProps) {

    const handlePageChange = (page: number) => {
        router.get('/payment-reminder-sequences', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['paymentReminderSequences'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Payment Reminder Sequences" />
            <DataTable
                columns={columns}
                data={{
                    data: paymentReminderSequences?.data?.data ?? [],
                    meta: {
                        current_page: paymentReminderSequences?.meta?.current_page ?? 1,
                        from: paymentReminderSequences?.meta?.from ?? 1,
                        last_page: paymentReminderSequences?.meta?.last_page ?? 1,
                        per_page: paymentReminderSequences?.meta?.per_page ?? 10,
                        to: paymentReminderSequences?.meta?.to ?? 10,
                        total: paymentReminderSequences?.meta?.total ?? 0,
                    }
                }}
            />
        </AppLayout>
    );
}