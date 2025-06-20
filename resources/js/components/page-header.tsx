import React from 'react';

interface PageHeaderProps {
    title: string;
    description?: string;
}

export function PageHeader({ title, description }: PageHeaderProps) {
    return (
        <div className="flex flex-col gap-1">
            <h1 className="text-2xl font-bold tracking-tight">{title}</h1>
            {description && (
                <p className="text-sm text-muted-foreground">
                    {description}
                </p>
            )}
        </div>
    );
}
