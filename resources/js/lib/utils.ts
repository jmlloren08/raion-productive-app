import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

/**
 * Format minutes into hours and minutes string (e.g. "2h 30m")
 */
export function formatTimeMinutes(minutes: number): string {
    if (!minutes) return '0m';
    
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = Math.round(minutes % 60);
    
    if (hours === 0) {
        return `${remainingMinutes}m`;
    } else if (remainingMinutes === 0) {
        return `${hours}h`;
    } else {
        return `${hours}h ${remainingMinutes}m`;
    }
}
