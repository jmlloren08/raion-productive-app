import { Head, Link } from '@inertiajs/react';

export default function NotFound() {
    return (
        <>
            <Head title="404 Not Found" />
            <div className="flex flex-col items-center justify-center min-h-screen bg-gray-50 px-4 sm:px-6 lg:px-8">
                <div className="text-center">
                    <h1 className="text-8xl font-extrabold text-blue-600">404</h1>
                    <div className="mt-2">
                        <p className="text-3xl font-semibold text-gray-900">Page not found</p>
                        <p className="mt-2 text-base text-gray-500">Sorry, we couldn't find the page you're looking for.</p>
                    </div>
                    <div className="mt-6">
                        <Link
                            href="/"
                            className="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150"
                        >
                            Go back home
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
}